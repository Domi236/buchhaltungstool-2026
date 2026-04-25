<?php
// datei: /inc/api-endpoints-remote.php (auf buchhaltung.buehnenbau.at)

add_action('rest_api_init', function () {
    register_rest_route('pc-accounting/v1', '/create-invoice', [
        'methods' => 'POST',
        'callback' => 'pc_generate_sliced_invoice',
        'permission_callback' => '__return_true' // Basic Auth läuft über .htaccess
    ]);
});

function pc_generate_sliced_invoice($request) {
    $params = $request->get_json_params();
    $client_id = isset($params['client_id']) ? intval($params['client_id']) : 0;

    if (!$client_id) {
        return new WP_Error('no_client', 'Keine Kunden-ID übergeben.', ['status' => 400]);
    }

    $prefix = get_field('client_kuerzel', $client_id);
    if (empty($prefix)) {
        return new WP_Error('no_prefix', 'Für diesen Kunden wurde kein Kürzel hinterlegt.', ['status' => 400]);
    }

    // 1. HIGHEST NUMBER FINDEN (Extrem tolerant)
    global $wpdb;
    $search_string = "%{$prefix}%"; // Sucht einfach alles, wo "AE" drinsteht
    $existing_titles = $wpdb->get_col($wpdb->prepare("
        SELECT post_title FROM {$wpdb->posts} 
        WHERE post_type = 'sliced_invoice' 
        AND post_title LIKE %s
    ", $search_string));

    $highest_number = 0;
    foreach ($existing_titles as $title) {
        // Sucht nach dem Kürzel und schnappt sich die ERSTEN Ziffern danach
        // Egal ob "RE-AE-14", "Rechnung AE 0014" oder "RE-AE-014"
        if (preg_match('/' . preg_quote($prefix, '/') . '[^\d]*(\d+)/i', $title, $matches)) {
            $num = intval($matches[1]);
            if ($num > $highest_number) {
                $highest_number = $num;
            }
        }
    }

    // 2. Nächste Nummer formatieren
    $next_number = $highest_number + 1;
    $formatted_number = sprintf('%02d', $next_number); // Macht aus 15 eine 15, und aus 3 eine 03
    $invoice_title = "RE-{$prefix}-{$formatted_number}";

    // 3. Referenz & Terms
    $referenz = substr(str_shuffle("01234567890123456789"), 0, 14);
    $terms_content = '<p style="text-align: left;">Rechnungs-Nr. ' . $invoice_title . '<br>Referenz: ' . $referenz . '</p><p>Es gelten unsere <a href="https://partycrowd.at/agbs" target="_blank">Allgemeinen Geschäftsbedingungen</a>.</p>';

    // 4. Rechnung anlegen
    $new_invoice_id = wp_insert_post([
        'post_type'   => 'sliced_invoice',
        'post_title'  => $invoice_title,
        'post_status' => 'draft'
    ]);

    if (!is_wp_error($new_invoice_id)) {
        // --- 5. SLICED INVOICES BOXEN BEFÜLLEN ---
        // Wir setzen alle erdenklichen Keys, die Sliced Invoices nutzt
        update_post_meta($new_invoice_id, '_sliced_invoice_prefix', "RE-{$prefix}-");
        update_post_meta($new_invoice_id, '_sliced_invoice_number', $formatted_number);
        update_post_meta($new_invoice_id, '_prefix', "RE-{$prefix}-");
        update_post_meta($new_invoice_id, '_number', $formatted_number);
        update_post_meta($new_invoice_id, '_sliced_invoice_terms', $terms_content);
        // NEU: Zahlungsart "Bank" für diese Rechnung aktivieren
        update_post_meta($new_invoice_id, '_sliced_payment_bank', 'on');

        // --- ACF ---
        update_field('client_invoice_relationship_sliced', [$client_id], $new_invoice_id);

        // --- 6. DER HOLZHAMMER ---
        // Sliced Invoices hat jetzt vermutlich beim Speichern den Titel überschrieben.
        // Wir schreiben unseren korrekten Titel hart in die Datenbank zurück:
        $wpdb->update(
            $wpdb->posts,
            ['post_title' => $invoice_title, 'post_name' => sanitize_title($invoice_title)],
            ['ID' => $new_invoice_id]
        );

        return rest_ensure_response([
            'status' => 'success',
            'invoice_id' => $new_invoice_id,
            'invoice_title' => $invoice_title
        ]);
    }

    return new WP_Error('create_failed', 'Rechnung konnte nicht erstellt werden.', ['status' => 500]);
}
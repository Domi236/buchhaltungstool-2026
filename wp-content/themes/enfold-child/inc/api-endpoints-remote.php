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


// =========================================================================
// GET /wp-json/partycrowd/v1/quote/{id}
// =========================================================================
add_action('rest_api_init', function () {
    register_rest_route('partycrowd/v1', '/quote/(?P<id>\d+)', [
        'methods'             => 'GET',
        'callback'            => 'get_interactive_quote_data',
        'permission_callback' => '__return_true',
        'args'                => ['id' => ['sanitize_callback' => 'absint']],
    ]);
});

function get_interactive_quote_data(WP_REST_Request $request) {
    $id   = (int) $request['id'];
    $post = get_post($id);

    if (!$post || $post->post_type !== 'sliced_invoice') {
        return new WP_Error('not_found', 'Kein Angebot mit dieser ID gefunden.', ['status' => 404]);
    }

    $is_quote = function_exists('get_field')
        ? get_field('is_quote', $id)
        : get_post_meta($id, 'is_quote', true);
    if (!$is_quote) {
        return new WP_Error('not_a_quote', 'Dies ist kein Angebot.', ['status' => 404]);
    }

    // ── Native Items ──────────────────────────────────────────────────────
    $raw_items    = maybe_unserialize(get_post_meta($id, '_sliced_items', true));
    $raw_items    = is_array($raw_items) ? $raw_items : [];
    $invoice_data = [];
    foreach ($raw_items as $i => $item) {
        $qty    = (float) ($item['qty']    ?? 0);
        $amount = (float) ($item['amount'] ?? 0);
        $invoice_data[] = [
            'index'       => $i,
            'title'       => sanitize_text_field($item['title']       ?? ''),
            'description' => wp_strip_all_tags($item['description']   ?? ''),
            'qty'         => $qty,
            'unit_amount' => $amount,
            'line_total'  => round($qty * $amount, 2),
            'taxable'     => ($item['taxable'] ?? '') === 'on',
        ];
    }

    // ── Interaktive Optionen ──────────────────────────────────────────────
    $interactive_options = function_exists('get_field')
        ? (get_field('interactive_options', $id) ?: [])
        : [];

    // ── Totals ────────────────────────────────────────────────────────────
    $total = get_post_meta($id, '_sliced_total', true);
    $tax   = get_post_meta($id, '_sliced_tax',   true);

    // ── Client Info ───────────────────────────────────────────────────────
    $client_info = [];
    if (function_exists('get_field')) {
        $client_rel = get_field('client_invoice_relationship_sliced', $id);
        $client_id  = is_array($client_rel) ? ($client_rel[0] ?? null) : $client_rel;

        if ($client_id) {
            $address_rows   = get_field('clients_company_adress', $client_id) ?: [];
            $address_parsed = [];
            foreach ($address_rows as $row) {
                $address_parsed[] = [
                    'street'      => $row['clients_company_street']      ?? '',
                    'postal_code' => $row['clients_company_postal_code'] ?? '',
                    'town'        => $row['clients_company_town']        ?? '',
                ];
            }
            $client_info = [
                'company_name' => get_field('clients_company_name',     $client_id) ?? '',
                'mail'         => get_field('clients_mail',              $client_id) ?? '',
                'uid_number'   => get_field('clients_uid_number',        $client_id) ?? '',
                'agent'        => get_field('clients_customer_agent',    $client_id) ?? '',
                'address'      => $address_parsed,
            ];
        }
    }

    // ── Sender Info ───────────────────────────────────────────────────────
    $sender_info           = [];
    $sender_profile_name   = get_post_meta($id, 'invoice_sender_profile', true);

    if (function_exists('get_field') && $sender_profile_name) {
        $all_profiles = get_field('sender_profiles', 'option') ?: [];
        foreach ($all_profiles as $profile) {
            if (($profile['company_name'] ?? '') === $sender_profile_name) {
                $logo_url    = '';
                if (!empty($profile['logo']) && is_array($profile['logo'])) {
                    $logo_url = $profile['logo']['url'] ?? '';
                } elseif (!empty($profile['logo'])) {
                    $logo_url = (string) $profile['logo'];
                }
                $downloads_array = [];
                $agb_repeater    = $profile['agb_downloads'] ?? [];
                if (is_array($agb_repeater)) {
                    foreach ($agb_repeater as $dl_row) {
                        $file = $dl_row['agb_download_file'] ?? [];
                        if (!empty($file['url'])) {
                            $downloads_array[] = [
                                'url'   => $file['url'],
                                'title' => !empty($file['title']) ? $file['title'] : ($file['filename'] ?? basename($file['url'])),
                            ];
                        }
                    }
                }
                $sender_info = [
                    'company_name'         => $profile['company_name']          ?? '',
                    'logo'                 => $logo_url,
                    'address'              => $profile['address']               ?? '',
                    'mail'                 => $profile['pc_mail']               ?? '',
                    'website'              => $profile['website']               ?? '',
                    'phone'                => !empty($profile['phone']) ? $profile['phone'] : '06602325524',
                    'quote_agb_phrases'    => $profile['quote_agb_phrases']     ?? '',
                    'deposit_template_1'   => $profile['quote_deposit_template_1'] ?? '',
                    'deposit_template_2'   => $profile['quote_deposit_template_2'] ?? '',
                    'deposit_template_3'   => $profile['quote_deposit_template_3'] ?? '',
                    'agb_downloads'        => $downloads_array,
                ];
                break;
            }
        }
    }

    // ── Terms Info ────────────────────────────────────────────────────────
    $terms_info = [];
    if (function_exists('get_field')) {
        $deposit_type = get_field('deposit_terms_type', $id);
        $terms_text   = '';

        if ($deposit_type === 'custom') {
            $terms_text = get_field('custom_deposit_terms', $id) ?? '';
        } elseif (in_array($deposit_type, ['template_1', 'template_2', 'template_3'], true)) {
            $tpl_key    = 'deposit_' . str_replace('template_', 'template_', $deposit_type);
            $terms_text = $sender_info[$tpl_key] ?? '';
        }

        $terms_info = [
            'type' => $deposit_type ?? '',
            'text' => $terms_text,
        ];
    }

    // ── Meta ─────────────────────────────────────────────────────────────
    $due_timestamp = get_post_meta($id, '_sliced_invoice_due', true);
    $meta_info = [
        'quote_number'        => function_exists('get_field') ? (get_field('quote_number', $id) ?? '') : '',
        'created_date'        => get_the_date('d.m.Y', $id),
        'due_date'            => $due_timestamp ? date('d.m.Y', (int) $due_timestamp) : '',
        'discount_name'       => function_exists('get_field') ? (get_field('discount_name', $id) ?: 'Skonto') : 'Skonto',
        'discount_percentage' => function_exists('get_field') ? floatval(get_field('discount_percentage', $id)) : 0.0,
        'show_skonto_option'  => function_exists('get_field') ? (bool) get_field('show_skonto_option', $id) : false,
        'client_data_missing' => function_exists('get_field') ? (bool) get_field('client_data_missing', $id) : false,
    ];

    return new WP_REST_Response([
        'success'             => true,
        'invoice_data'        => $invoice_data,
        'interactive_options' => $interactive_options,
        'totals'              => [
            'total' => $total,
            'tax'   => $tax,
        ],
        'client'              => $client_info,
        'sender'              => $sender_info,
        'terms'               => $terms_info,
        'meta'                => $meta_info,
    ], 200);
}


// =========================================================================
// POST /wp-json/partycrowd/v1/quote/accept
// =========================================================================
add_action('rest_api_init', function () {
    register_rest_route('partycrowd/v1', '/quote/accept', [
        'methods'             => 'POST',
        'callback'            => 'partycrowd_accept_quote',
        'permission_callback' => '__return_true',
    ]);
});

function partycrowd_accept_quote(WP_REST_Request $request) {
    $params       = $request->get_json_params();
    $quote_id     = isset($params['quote_id'])     ? intval($params['quote_id'])                   : 0;
    $quote_number = isset($params['quote_number']) ? sanitize_text_field($params['quote_number'])  : '';
    $pdf_base64   = isset($params['pdf_base64'])   ? sanitize_text_field($params['pdf_base64'])    : '';
    $client_name  = isset($params['client_name'])  ? sanitize_text_field($params['client_name'])   : 'Unbekannter Kunde';
    $sender_name  = isset($params['sender_name'])  ? sanitize_text_field($params['sender_name'])   : 'Auftragnehmer';
    $company_name = isset($params['company_name']) ? sanitize_text_field($params['company_name'])  : '';
    $client_agent = isset($params['client_agent']) ? sanitize_text_field($params['client_agent'])  : '';
    $address      = isset($params['address'])      ? sanitize_text_field($params['address'])       : '';
    $zip_city     = isset($params['zip_city'])     ? sanitize_text_field($params['zip_city'])      : '';
    $uid          = isset($params['uid'])          ? sanitize_text_field($params['uid'])           : '';
    $fn           = isset($params['fn'])           ? sanitize_text_field($params['fn'])            : '';

    if (!$quote_id || empty($pdf_base64)) {
        return new WP_Error('missing_params', 'Pflichtfelder fehlen (quote_id, pdf_base64).', ['status' => 400]);
    }

    $pdf_base64  = preg_replace('#^data:application/\w+;base64,#i', '', $pdf_base64);
    $pdf_decoded = base64_decode($pdf_base64);
    if ($pdf_decoded === false || strlen($pdf_decoded) < 100) {
        return new WP_Error('invalid_pdf', 'Ungültiger PDF-Inhalt.', ['status' => 400]);
    }

    $upload_dir = wp_upload_dir();
    $target_dir = $upload_dir['basedir'] . '/angebote-angenommen';
    if (!file_exists($target_dir)) {
        wp_mkdir_p($target_dir);
    }
    $file_path = $target_dir . '/Angebot_' . sanitize_file_name($quote_number) . '_' . time() . '.pdf';
    file_put_contents($file_path, $pdf_decoded);

    $display_name = !empty($company_name) ? $company_name : (!empty($client_agent) ? $client_agent : $client_name);
    $label        = !empty($quote_number) ? $quote_number : (string) $quote_id;

    $client_details = '';
    if ($company_name) $client_details .= 'Firma:            ' . $company_name . "\n";
    if ($client_agent) $client_details .= 'Ansprechperson:   ' . $client_agent . "\n";
    if ($address)      $client_details .= 'Straße:           ' . $address      . "\n";
    if ($zip_city)     $client_details .= 'PLZ / Ort:        ' . $zip_city     . "\n";
    if ($uid)          $client_details .= 'UID-Nummer:       ' . $uid          . "\n";
    if ($fn)           $client_details .= 'Firmenbuchnummer: ' . $fn           . "\n";

    $to      = 'office@partycrowd.at';
    $subject = '[' . $sender_name . '] Angebot wurde angenommen';
    $message = 'Das Angebot Nr. ' . $label . ' von ' . $display_name . ' wurde angenommen.' . "\n\n" .
               ( $client_details ? "Kundendaten (vom Kunden ergänzt):\n" . $client_details . "\n" : '' ) .
               'Das unterzeichnete Angebot ist als PDF-Anhang beigefügt.';

    $sent = wp_mail($to, $subject, $message, [], [$file_path]);

    if (!$sent) {
        return new WP_Error('mail_failed', 'E-Mail konnte nicht gesendet werden.', ['status' => 500]);
    }

    return new WP_REST_Response(['success' => true], 200);
}
<?php

// 1. Globale Rechnungsnummer automatisch generieren
add_action( 'save_post_sliced_invoice', 'partycrowd_generate_global_invoice_number', 10, 3 );
function partycrowd_generate_global_invoice_number( $post_id, $post, $update ) {
    // Nicht bei Revisionen oder Auto-Drafts ausführen
    if ( wp_is_post_revision( $post_id ) || $post->post_status == 'auto-draft' ) return;

    $global_number = get_post_meta( $post_id, '_global_invoice_number', true );

    // Wenn noch keine globale Nummer existiert, eine neue vergeben
    if ( empty( $global_number ) ) {
        // Aktuellen Zählerstand holen, Startwert ist 613920
        $counter = get_option( 'partycrowd_global_invoice_counter', 613920 );

        // Nummer im Post Meta speichern
        update_post_meta( $post_id, '_global_invoice_number', $counter );

        // Zähler um 1 erhöhen und für die nächste Rechnung speichern
        update_option( 'partycrowd_global_invoice_counter', $counter + 1 );
    }
}

// 2. Spalten-Setup für alle potenziellen Post-Type-Namen registrieren
$partycrowd_post_types = array( 'sliced_invoice', 'rechnung', 'rechnungen', 'invoice' );

foreach ( $partycrowd_post_types as $pt ) {
    add_filter( "manage_{$pt}_posts_columns", 'partycrowd_custom_invoice_columns', 9999 );
    add_filter( "manage_edit-{$pt}_columns", 'partycrowd_custom_invoice_columns', 9999 );
    add_action( "manage_{$pt}_posts_custom_column", 'partycrowd_custom_invoice_columns_content', 9999, 2 );
}

function partycrowd_custom_invoice_columns( $columns ) {
    $new_columns = array();
    $column_added = false;

    // Wir bauen die Tabelle neu auf und mogeln unsere Spalte direkt nach dem "Title" rein
    foreach ( $columns as $key => $title ) {
        $new_columns[$key] = $title;

        if ( $key === 'title' && ! $column_added ) {
            $new_columns['custom_number_ref'] = 'RE-Nr. & Ref';
            $column_added = true;
        }
    }

    // FALLBACK: Falls sie immer noch fehlt, zwingen wir sie ans Ende
    if ( ! isset( $new_columns['custom_number_ref'] ) ) {
        $new_columns['custom_number_ref'] = 'RE-Nr. & Ref (Fallback)';
    }

    // Alte Nummern-Spalten von Sliced Invoices (falls vorhanden) rauswerfen
    if ( isset( $new_columns['number'] ) ) unset( $new_columns['number'] );
    if ( isset( $new_columns['sliced_invoice_number'] ) ) unset( $new_columns['sliced_invoice_number'] );

    return $new_columns;
}

// 3. Inhalt der neuen Kombi-Spalte befüllen
function partycrowd_custom_invoice_columns_content( $column_name, $post_id ) {
    if ( $column_name === 'custom_number_ref' ) {
        $global_num = get_post_meta( $post_id, '_global_invoice_number', true );
        $old_prefix = get_post_meta( $post_id, '_sliced_invoice_prefix', true );
        $old_num    = get_post_meta( $post_id, '_sliced_invoice_number', true );
        $old_format = $old_prefix . $old_num;

        if ( ! empty( $global_num ) ) {
            // Neues System
            echo '<strong style="color:#0073aa; font-size:14px;">RE-' . esc_html( $global_num ) . '</strong><br>';
            echo '<span style="color:#666; font-size:12px;">Ref: ' . esc_html( $old_format ) . '</span>';
        } else {
            // Altes System
            echo '<strong>' . esc_html( $old_format ) . '</strong>';
        }
    }
}
// Backend-Metaboxen von Sliced Invoices aufräumen (CMB2 Fix & Save Fix)
add_action('admin_head', 'partycrowd_clean_sliced_meta_box');
function partycrowd_clean_sliced_meta_box() {
    global $typenow;

    // Prüfen, ob wir uns bei den Rechnungen befinden
    $post_types = array('sliced_invoice', 'rechnung', 'rechnungen', 'invoice');
    if ( in_array($typenow, $post_types) ) {
        ?>
        <style>
            /* 1. Client-Block verstecken (Browser-Safe Methode!) */
            /* Statt display:none schieben wir es aus dem sichtbaren Bereich */
            .cmb2-id--sliced-client {
                position: absolute !important;
                left: -9999px !important;
                height: 0 !important;
                overflow: hidden !important;
            }

            /* 2. Rote Duplikat-Warnungen auf CSS-Ebene killen */
            .sliced-duplicate-warning,
            .cmb2-id--sliced-invoice-number .sliced-error {
                display: none !important;
            }
        </style>

        <script>
            jQuery(document).ready(function($){

                // 3. HTML5 Validation Error Fix:
                // Wir entfernen das Pflichtfeld-Attribut vom unsichtbaren Dropdown
                var $clientDropdown = $('#_sliced_client');
                $clientDropdown.removeAttr('required');

                // Wir wählen heimlich den ersten verfügbaren Kunden in der Liste aus,
                // damit die PHP-Speicherlogik im Hintergrund nicht meckert.
                if ($clientDropdown.val() === '') {
                    $clientDropdown.find('option:eq(1)').prop('selected', true);
                }

                // 4. Label über die exakte "for"-ID ansprechen und Text ändern
                var invoiceLabel = $('label[for="_sliced_invoice_number"]');
                if (invoiceLabel.length) {
                    invoiceLabel.html('<strong>Referenz (altes Kürzel)</strong>');
                }

                // 5. Fallback: AJAX Warnungen ausblenden
                $(document).ajaxComplete(function() {
                    $('.sliced-duplicate-warning').hide();
                    $('span:contains("duplicate invoice number")').hide();
                });
            });
        </script>
        <?php
    }
}
<?php
if ( ! defined('ABSPATH') ) { exit; }

// =========================================================================
// 0. CLEANUP-TOOL FÜR ALTE RECHNUNGEN (RÜCKGÄNGIG MACHEN)
// =========================================================================
add_action('admin_init', 'partycrowd_cleanup_old_invoices_fix');
function partycrowd_cleanup_old_invoices_fix() {
    if (isset($_GET['fix_old_invoices']) && current_user_can('manage_options')) {
        $threshold_timestamp = strtotime('2026-04-23 00:00:00');
        $invoices = get_posts([
            'post_type' => 'sliced_invoice',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        
        $fixed_count = 0;
        foreach ($invoices as $inv) {
            $created_meta = get_post_meta($inv->ID, '_sliced_invoice_created', true);
            $invoice_timestamp = $created_meta ? (int)$created_meta : strtotime($inv->post_date);
            
            // Wenn die Rechnung VOR dem Stichtag liegt...
            if ($invoice_timestamp < $threshold_timestamp) {
                $wrong_number = get_post_meta($inv->ID, '_global_invoice_number', true);
                if (!empty($wrong_number)) {
                    // ...löschen wir die falsch vergebene globale Nummer restlos!
                    delete_post_meta($inv->ID, '_global_invoice_number');
                    $fixed_count++;
                }
            }
        }
        
        add_action('admin_notices', function() use ($fixed_count) {
            echo "<div class='notice notice-success is-dismissible'><p><strong>Sauber!</strong> Es wurden <strong>{$fixed_count}</strong> alte Rechnungen gefunden und von der falschen globalen Rechnungsnummer befreit.</p></div>";
        });
    }
}


// =========================================================================
// 1. GLOBALE RECHNUNGSNUMMER INTELLIGENT VERWALTEN (MIT STICHTAG)
// =========================================================================
add_action('acf/save_post', 'partycrowd_manage_invoice_number_on_save', 20);
function partycrowd_manage_invoice_number_on_save($post_id) {
    if (get_post_type($post_id) !== 'sliced_invoice') return;
    if (wp_is_post_revision($post_id) || get_post_status($post_id) === 'auto-draft') return;

    // STICHTAGS-PRÜFUNG (23.04.2026)
    $threshold_timestamp = strtotime('2026-04-23 00:00:00');
    $created_meta = get_post_meta($post_id, '_sliced_invoice_created', true);
    $invoice_timestamp = $created_meta ? (int)$created_meta : strtotime(get_the_date('Y-m-d H:i:s', $post_id));

    if ($invoice_timestamp < $threshold_timestamp) {
        // Alte Rechnung! Sicherheitshalber nochmals Nummer killen, falls was durchrutscht
        delete_post_meta($post_id, '_global_invoice_number');
        return; 
    }

    $is_quote = get_field('is_quote', $post_id);
    $global_number = get_post_meta($post_id, '_global_invoice_number', true);
    $counter = (int) get_option('partycrowd_global_invoice_counter', 613927);

    if ($is_quote) {
        // ES IST EIN ANGEBOT
        if (!empty($global_number)) {
            // Zähler zurückdrehen, falls es die zuletzt vergebene Nummer war
            if ((int)$global_number === $counter - 1) {
                update_option('partycrowd_global_invoice_counter', (int)$global_number);
            }
            delete_post_meta($post_id, '_global_invoice_number');
        }
    } else {
        // ES IST EINE NEUE RECHNUNG
        if (empty($global_number)) {
            update_post_meta($post_id, '_global_invoice_number', $counter);
            update_option('partycrowd_global_invoice_counter', $counter + 1);
        }
    }
}

// Zähler zurücksetzen bei Papierkorb/Löschen
add_action('wp_trash_post', 'partycrowd_recycle_invoice_number_on_trash');
add_action('before_delete_post', 'partycrowd_recycle_invoice_number_on_trash');
function partycrowd_recycle_invoice_number_on_trash($post_id) {
    if (get_post_type($post_id) !== 'sliced_invoice') return;
    $global_number = get_post_meta($post_id, '_global_invoice_number', true);
    if (!empty($global_number)) {
        $counter = (int) get_option('partycrowd_global_invoice_counter', 613927);
        if ((int)$global_number === $counter - 1) {
            update_option('partycrowd_global_invoice_counter', (int)$global_number);
            delete_post_meta($post_id, '_global_invoice_number');
        }
    }
}

// =========================================================================
// 2. DUPLIKAT-SCHUTZ & RECHNUNGSNUMMER-SICHERHEITSNETZ
// =========================================================================

// A. Den gefährlichen "Duplicate" Button von Sliced Invoices ausblenden
add_filter( 'post_row_actions', 'partycrowd_remove_duplicate_action', 10, 2 );
function partycrowd_remove_duplicate_action( $actions, $post ) {
    if ( $post->post_type === 'sliced_invoice' ) {
        if ( isset( $actions['duplicate'] ) ) { unset( $actions['duplicate'] ); }
        if ( isset( $actions['sliced_duplicate'] ) ) { unset( $actions['sliced_duplicate'] ); }
    }
    return $actions;
}

// B. Verhindern, dass Kopier-Plugins die Meta-Nummer mitschleifen
add_filter('duplicate_post_excludelist_filter', 'partycrowd_exclude_meta_from_copy');
function partycrowd_exclude_meta_from_copy($meta_excludelist) {
    $meta_excludelist[] = '_global_invoice_number';
    return $meta_excludelist;
}

// C. Hardcore-SQL-Prüfung beim Speichern
add_action('save_post_sliced_invoice', 'partycrowd_prevent_duplicate_invoice_numbers', 10, 3);
function partycrowd_prevent_duplicate_invoice_numbers($post_id, $post, $update) {
    if (wp_is_post_revision($post_id) || $post->post_status === 'auto-draft' || $post->post_status === 'trash') return;
    
    // Stichtag prüfen! Alte Rechnungen überspringen wir auch hier.
    $threshold_timestamp = strtotime('2026-04-23 00:00:00');
    $created_meta = get_post_meta($post_id, '_sliced_invoice_created', true);
    $invoice_timestamp = $created_meta ? (int)$created_meta : strtotime($post->post_date);
    if ($invoice_timestamp < $threshold_timestamp) return;

    $global_number = get_post_meta($post_id, '_global_invoice_number', true);
    if (!empty($global_number)) {
        global $wpdb;
        $conflict = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta 
             INNER JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->postmeta.post_id
             WHERE meta_key = '_global_invoice_number' AND meta_value = %s AND post_id != %d AND post_status != 'trash'",
            $global_number, $post_id
        ));
        
        if ($conflict) {
            delete_post_meta($post_id, '_global_invoice_number');
            $is_quote = get_post_meta($post_id, 'is_quote', true) == '1';
            if (!$is_quote) {
                $counter = (int) get_option('partycrowd_global_invoice_counter', 613927);
                update_post_meta($post_id, '_global_invoice_number', $counter);
                update_option('partycrowd_global_invoice_counter', $counter + 1);
            }
        }
    }
}

// D. Prüfung beim Wiederherstellen aus dem Papierkorb (Untrash)
add_action('untrash_post', 'partycrowd_check_invoice_number_on_restore');
function partycrowd_check_invoice_number_on_restore($post_id) {
    if (get_post_type($post_id) !== 'sliced_invoice') return;
    
    // Stichtag prüfen
    $threshold_timestamp = strtotime('2026-04-23 00:00:00');
    $created_meta = get_post_meta($post_id, '_sliced_invoice_created', true);
    $invoice_timestamp = $created_meta ? (int)$created_meta : strtotime(get_the_date('Y-m-d H:i:s', $post_id));
    if ($invoice_timestamp < $threshold_timestamp) return;

    $global_number = get_post_meta($post_id, '_global_invoice_number', true);
    if (!empty($global_number)) {
        global $wpdb;
        $conflict = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta 
             INNER JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->postmeta.post_id
             WHERE meta_key = '_global_invoice_number' AND meta_value = %s AND post_id != %d AND post_status != 'trash'",
            $global_number, $post_id
        ));
        
        if ($conflict) {
            delete_post_meta($post_id, '_global_invoice_number');
            $is_quote = get_post_meta($post_id, 'is_quote', true) == '1';
            if (!$is_quote) {
                $counter = (int) get_option('partycrowd_global_invoice_counter', 613927);
                update_post_meta($post_id, '_global_invoice_number', $counter);
                update_option('partycrowd_global_invoice_counter', $counter + 1);
            }
        }
    }
}

// =========================================================================
// 3. ADMIN LISTEN-ANSICHT & CLEANUP
// =========================================================================
$partycrowd_post_types = array( 'sliced_invoice', 'rechnung', 'rechnungen', 'invoice' );

foreach ( $partycrowd_post_types as $pt ) {
    add_filter( "manage_{$pt}_posts_columns", 'partycrowd_custom_invoice_columns', 9999 );
    add_filter( "manage_edit-{$pt}_columns", 'partycrowd_custom_invoice_columns', 9999 );
    add_action( "manage_{$pt}_posts_custom_column", 'partycrowd_custom_invoice_columns_content', 9999, 2 );
}

function partycrowd_custom_invoice_columns( $columns ) {
    $new_columns = array();
    $column_added = false;

    foreach ( $columns as $key => $title ) {
        $new_columns[$key] = $title;
        if ( $key === 'title' && ! $column_added ) {
            $new_columns['custom_number_ref'] = 'RE-Nr. & Ref';
            $column_added = true;
        }
    }

    if ( ! isset( $new_columns['custom_number_ref'] ) ) {
        $new_columns['custom_number_ref'] = 'RE-Nr. & Ref (Fallback)';
    }

    if ( isset( $new_columns['number'] ) ) unset( $new_columns['number'] );
    if ( isset( $new_columns['sliced_invoice_number'] ) ) unset( $new_columns['sliced_invoice_number'] );

    return $new_columns;
}

function partycrowd_custom_invoice_columns_content( $column_name, $post_id ) {
    if ( $column_name === 'custom_number_ref' ) {
        $global_num = get_post_meta( $post_id, '_global_invoice_number', true );
        $old_prefix = get_post_meta( $post_id, '_sliced_invoice_prefix', true );
        $old_num    = get_post_meta( $post_id, '_sliced_invoice_number', true );
        $old_format = $old_prefix . $old_num;

        if ( ! empty( $global_num ) ) {
            echo '<strong style="color:#0073aa; font-size:14px;">RE-' . esc_html( $global_num ) . '</strong><br>';
            echo '<span style="color:#666; font-size:12px;">Ref: ' . esc_html( $old_format ) . '</span>';
        } else {
            echo '<strong>' . esc_html( $old_format ) . '</strong>';
        }
    }
}

add_action('admin_head', 'partycrowd_clean_sliced_meta_box');
function partycrowd_clean_sliced_meta_box() {
    global $typenow;

    $post_types = array('sliced_invoice', 'rechnung', 'rechnungen', 'invoice');
    if ( in_array($typenow, $post_types) ) {
        ?>
        <style>
            .cmb2-id--sliced-client {
                position: absolute !important;
                left: -9999px !important;
                height: 0 !important;
                overflow: hidden !important;
            }

            .sliced-duplicate-warning,
            .cmb2-id--sliced-invoice-number .sliced-error {
                display: none !important;
            }
        </style>

        <script>
            jQuery(document).ready(function($){
                // Initialer Clean-up
                $('#_sliced_line_items input, #_sliced_line_items textarea').removeAttr('required');

                var $clientDropdown = $('#_sliced_client');
                $clientDropdown.removeAttr('required');

                jQuery('#sliced-items-wrap input, #sliced-items-wrap textarea, .sliced-item input').removeAttr('required');

                if ($clientDropdown.val() === '') {
                    $clientDropdown.find('option:eq(1)').prop('selected', true);
                }

                var invoiceLabel = $('label[for="_sliced_invoice_number"]');
                if (invoiceLabel.length) {
                    invoiceLabel.html('<strong>Referenz (altes Kürzel)</strong>');
                }

                // Holzhammer: required beim Submit garantiert entfernen
                $('form#post').on('submit', function() {
                    $('#_sliced_line_items input, #_sliced_line_items textarea').removeAttr('required');
                });

                // Dynamische Zeilen: neue CMB2-Rows säubern
                $('#_sliced_line_items').on('click', '.cmb-add-group-row', function() {
                    setTimeout(function() {
                        $('#_sliced_line_items input, #_sliced_line_items textarea').removeAttr('required');
                    }, 500);
                });

                $(document).ajaxComplete(function() {
                    $('.sliced-duplicate-warning').hide();
                    $('span:contains("duplicate invoice number")').hide();
                });
            });
        </script>
        <?php
    }
}

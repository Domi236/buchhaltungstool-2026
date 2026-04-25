<?php
// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }


function get_partycrowd_sender_data() {
global $post;
$post_id = isset($post->ID) ? $post->ID : get_the_ID();

// 1. RAW-Daten aus der Datenbank holen
$selected_profile = get_post_meta($post_id, 'invoice_sender_profile', true);

if ( empty($selected_profile) ) {
die("FEHLER: Kein Absender-Profil für Rechnungs-ID $post_id ausgewählt!");
}

$found = false;
$data = array();

// 2. Wir nutzen get_field statt have_rows, da have_rows im PDF-Kontext oft versagt!
$repeater_data = get_field('sender_profiles', 'option');

// Sicherheitscheck: Gibt es überhaupt Daten?
if ( !$repeater_data || !is_array($repeater_data) ) {
die("FEHLER: Konnte die Optionen-Seite nicht laden! Das ACF-Array ist leer.");
}

$debug_found_names = array(); // Sammelt alle Namen, falls es doch crasht

// 3. Array direkt durchsuchen
foreach ( $repeater_data as $row ) {
$current_company = isset($row['company_name']) ? $row['company_name'] : '';
$debug_found_names[] = $current_company;

// Kugelsicherer Vergleich: Alles klein, keine Leerzeichen vorne/hinten
if ( strtolower(trim($current_company)) === strtolower(trim($selected_profile)) ) {
$data = array(
'name'         => $current_company,
'website'      => isset($row['website']) ? $row['website'] : '',
'address'      => isset($row['address']) ? $row['address'] : '',
'extra_info'   => isset($row['extra_info']) ? $row['extra_info'] : '',
'mail'         => isset($row['pc_mail']) ? $row['pc_mail'] : '',
'logo'         => isset($row['logo']) ? $row['logo'] : '',
'phone'        => '06602325524'
);
$found = true;
break;
}
}

// 4. Wenn Profil nicht gefunden wurde
if (!$found) {
$all_names = implode(', ', $debug_found_names);
die("FEHLER: Das Profil '$selected_profile' wurde nicht gefunden! Das System sieht aktuell nur diese Profile: [ $all_names ].");
}

// Domain säubern
$data['clean_domain'] = str_replace(array('http://', 'https://', 'www.'), '', $data['website']);
$data['clean_domain'] = rtrim($data['clean_domain'], '/');

return $data;
}

function sliced_display_business_new() {
    $profile = get_partycrowd_sender_data();
    if (!$profile) return; // Sollte durch das 'die' oben eh nie erreicht werden
    ?>
    <a target="_blank" href="<?php echo esc_url( $profile['website'] ); ?>">
        <img class="logo sliced-business-logo" src="<?php echo esc_url( $profile['logo'] ); ?>">
    </a>
    <?php
}
/**
 * NEUE FUNKTION: Zeigt die Anschrift
 */
function sliced_display_from_address_new() {
    $profile = get_partycrowd_sender_data();
    ?>
    <div class="from"><strong><?php _e( 'VON:', 'sliced-invoices' ) ?></strong></div>
    <div class="wrapper">
        <div class="name">
            <a target="_blank" href="<?php echo esc_url( $profile['website'] ); ?>"><?php echo esc_html( $profile['name'] ); ?></a>
        </div>
        <?php
        echo $profile['address'] ? '<div class="address">' . wpautop( wp_kses_post( $profile['address'] ) ) . '</div>' : '';
        echo $profile['extra_info'] ? '<div class="extra_info">' . wpautop( wp_kses_post( $profile['extra_info'] ) ) . '</div>' : '';
        ?>
    </div>
    <?php
}


	function sliced_display_to_address_new() {

		$output = '<div class="to"><strong>' . __( 'Rechnungsempfänger:', 'sliced-invoices' ) . '</strong></div>';
		$output .= '<div class="wrapper">';
        $client_data = get_field('client_invoice_relationship_sliced');
        if ($client_data):
            foreach ($client_data as $client_data_ID):
                $output .= '<p>'. get_field('clients_company_name', $client_data_ID).'</p>';
                if(get_field('clients_customer_agent', $client_data_ID)):
                    $output .=  get_field('clients_customer_agent', $client_data_ID) . '<br>';
                endif;
                if (have_rows('clients_company_adress', $client_data_ID)):
                    while (have_rows('clients_company_adress', $client_data_ID)): the_row();
                        $output .=  get_sub_field('clients_company_street') . ',<br>';
                        if(get_sub_field('clients_company_country', $client_data_ID)) {
                            $output .= get_sub_field('clients_company_country') . '<br>';
                        }
                        if(get_sub_field('clients_company_postal_code', $client_data_ID)) {
                            $output .= get_sub_field('clients_company_postal_code');
                        }
                        if(get_sub_field('clients_company_town', $client_data_ID)) {
                            $output .= get_sub_field('clients_company_town'). '<br>';
                        }

                    endwhile;
                endif;
                if (get_field('clients_tax_number', $client_data_ID)):
                $output .=  '<p>Steuer-Nr.: '. get_field('clients_tax_number', $client_data_ID).'</p>';
                endif;
                if (get_field('clients_uid_number', $client_data_ID)):
                $output .=  '<p>USt.-ID: '. get_field('clients_uid_number', $client_data_ID).'</p>';
                endif;
                if (get_field('clients_company_book_number', $client_data_ID)):
                    $output .=  '<p>Firmenbuchnummer: '. get_field('clients_company_book_number', $client_data_ID).'</p>';
                endif;
                $output .=  '<p>'. get_field('clients_mail', $client_data_ID).'</p><br>';
            endforeach;
        endif;

		echo $output;
	}



	function sliced_display_invoice_details_new() {

		$translate = get_option( 'sliced_translate' );

		?>

			<table class="table table-bordered table-sm">

                <?php if( sliced_get_invoice_number() ) : ?>
                    <tr>
                        <td><?php printf( esc_html_x( 'Nummer', 'invoice number', 'sliced-invoices' ), sliced_get_invoice_label() ); ?></td>
                        <td>
                            <?php
                            // Prüfen ob eine globale Nummer existiert
                            $global_num = get_post_meta( get_the_ID(), '_global_invoice_number', true );

                            if ( $global_num ) {
                                // Neues System: Globale Nummer anzeigen
                                echo 'RE-' . esc_html( $global_num );
                            } else {
                                // Fallback für alte Rechnungen: Altes System anzeigen
                                echo esc_html( sliced_get_invoice_prefix() ); ?><?php echo esc_html( sliced_get_invoice_number() ); ?><?php echo esc_html( sliced_get_invoice_suffix() );
                            }
                            ?>
                        </td>
                    </tr>
                <?php endif; ?>

				<?php if( sliced_get_invoice_order_number() ) : ?>
					<tr>
						<td><?php _e( 'Order Number', 'sliced-invoices' ) ?></td>
						<td><?php echo esc_html( sliced_get_invoice_order_number() ); ?></td>
					</tr>
				<?php endif; ?>

				<?php if( sliced_get_invoice_created() ) : ?>
					<tr>
						<td><?php printf( esc_html_x( 'Datum', 'invoice date', 'sliced-invoices' ), sliced_get_invoice_label() ); ?></td>
						<td><?php echo Sliced_Shared::get_local_date_i18n_from_timestamp( sliced_get_invoice_created() ); ?></td>
					</tr>
				<?php endif; ?>

				<?php if( sliced_get_invoice_due() ) : ?>
					<tr>
						<td><?php _e( 'Fälligkeitsdatum', 'sliced-invoices' ) ?></td>
						<td><?php echo Sliced_Shared::get_local_date_i18n_from_timestamp( sliced_get_invoice_due() ); ?></td>
					</tr>
				<?php endif; ?>

					<tr class="table-active">
						<td><strong><?php echo ( isset( $translate['total_due'] ) ? $translate['total_due'] : __( 'Total Due', 'sliced-invoices') ); ?></strong></td>
						<td><strong><?php echo sliced_get_invoice_total_due(); ?></strong></td>
					</tr>

			</table>

		<?php
	}

	function sliced_display_line_items_new() {
	
		$shared = new Sliced_Shared;
		$translate = get_option( 'sliced_translate' );

		$output = '<table class="table table-sm table-bordered table-striped">
			<thead>
				<tr>
				    <th class="#" style="width: 20px !important; max-width: 20px !important; text-align: center;"><strong>#</strong></th>
                    <th class="service" style="width: 55% !important;"><strong>' . ( isset( $translate['service'] ) ? $translate['service'] : __( 'Service', 'sliced-invoices') ) . '</strong></th>
					<th class="qty"><strong>' . ( isset( $translate['hrs_qty'] ) ? $translate['hrs_qty'] : __( 'Hrs/Qty', 'sliced-invoices') ) . '</strong></th>
					<th class="rate"><strong>' . ( isset( $translate['rate_price'] ) ? $translate['rate_price'] : __( 'Rate/Price', 'sliced-invoices') ) . '</strong></th>';
					if ( sliced_hide_adjust_field() === false ) {
						$output .= '<th class="adjust"><strong>' . ( isset( $translate['adjust'] ) ? $translate['adjust'] : __( 'Adjust', 'sliced-invoices') ) . '</strong></th>';
					}
					$output .= '<th class="total"><strong>' . ( isset( $translate['sub_total'] ) ? $translate['sub_total'] : __( 'Sub Total', 'sliced-invoices') ) . '</strong></th>
				</tr>
			</thead>
			<tbody>';

			$count = 0;
			$items = sliced_get_invoice_line_items(); // gets quote and invoice
            $postion = 1;
			if( !empty( $items ) && !empty( $items[0] ) ) :

				foreach ( $items[0] as $item ) {
					
					$class = ( $count % 2 == 0 ) ? 'even' : 'odd';
					
					$qty = isset( $item['qty'] ) ? $item['qty'] : 0;
					$amt = isset( $item['amount'] ) ? $shared->get_raw_number( $item['amount'] ) : 0;
					$tax = isset( $item['tax'] ) ? $shared->get_raw_number( $item['tax'] ) : '0.00';
					$line_total = $shared->get_line_item_sub_total( $shared->get_raw_number( $qty ), $amt, $tax );
					$output .= '<tr class="row_' . $class . ' sliced-item">
                        <td class="counter"  style="width: 20px !important; max-width: 20px !important; text-align: center;">'. $postion++ .'</td>
						<td class="service">' . ( isset( $item['title'] ) ? esc_html__( $item['title'] ) : '' ).
                        '<td class="qty">' . $qty . '</td>';
					if ( isset( $item['description'] ) ) {
						$output .= '<br /><span class="description">' . wpautop( wp_kses_post( $item['description'] ) ) . '</span>';
					}
					$output .= '</td>
						<td class="rate">' . $shared->get_formatted_currency( $amt ) . '</td>';
					if ( sliced_hide_adjust_field() === false) {
						$output .= '<td class="adjust">' . sprintf( __( '%s%%' ), $tax ) . '</td>';
					}
					$output .= '<td class="total">' . $shared->get_formatted_currency( $line_total ) . '</td>
						</tr>';
					
					$count++;
				}
			
			endif;

			$output .= '</tbody></table>';

			$output = apply_filters( 'sliced_invoice_line_items_output', $output );

		echo $output;

	}



	function sliced_display_invoice_totals_new() {
	
		$translate = get_option( 'sliced_translate' );

		ob_start();
		
		do_action( 'sliced_invoice_before_totals_table' ); 
		
		// need to fix this up
		if( function_exists('sliced_woocommerce_get_order_id') ) {
			$order_id = sliced_woocommerce_get_order_id( get_the_ID() );
			if ( $order_id ) {
				$output = ob_get_clean();
				echo $output;
				return;
			}
		}
		?>

		<table class="table table-sm table-bordered">
			<tbody>
				<?php do_action( 'sliced_invoice_before_totals' ); ?>
				<tr class="row-sub-total">
					<td class="rate"><?php echo ( isset( $translate['sub_total'] ) ? $translate['sub_total'] : __( 'Sub Total', 'sliced-invoices') ); ?></td>
					<td class="total"><?php _e( sliced_get_invoice_sub_total() ) ?></td>
				</tr>
				<?php do_action( 'sliced_invoice_after_sub_total' ); ?>
				<tr class="row-tax">
					<td class="rate"><?php _e( sliced_get_tax_name() ) ?></td>
					<td class="total"><?php _e( sliced_get_invoice_tax() ) ?></td>
				</tr>
				<?php do_action( 'sliced_invoice_after_tax' ); ?>
				<?php 
				$totals = Sliced_Shared::get_totals( get_the_id() );
				/*
				if ( $totals['payments'] || $totals['discount'] ) {
					$total = Sliced_Shared::get_formatted_currency( $totals['total'] );
					?>
					<tr class="row-total">
						<td class="rate"><strong><?php _e( 'Total', 'sliced-invoices' ) ?></strong></td>
						<td class="total"><?php echo esc_html( $total ) ?></td>
					</tr>
					<?php
				}
				*/
				if ( $totals['discounts'] ) {
					$discount = Sliced_Shared::get_formatted_currency( $totals['discounts'] );
					?>
					<tr class="row-discount">
						<td class="rate"><?php echo ( isset( $translate['discount'] ) ? $translate['discount'] : __( 'Discount', 'sliced-invoices') ); ?></td>
						<td class="total"><span style="color:red;">-<?php echo esc_html( $discount ) ?></span></td>
					</tr>
					<?php
				}

				if ( $totals['payments'] ) {
					$paid = Sliced_Shared::get_formatted_currency( $totals['payments'] );
					?>
					<tr class="row-paid">
						<td class="rate"><?php _e( 'Paid', 'sliced-invoices' ) ?></td>
						<td class="total"><span style="color:red;">-<?php echo esc_html( $paid ) ?></span></td>
					</tr>
					<?php
				}
				
				if ( $totals['payments'] || $totals['discounts'] ) {
					$total_due = Sliced_Shared::get_formatted_currency( $totals['total_due'] );
					?>
					<tr class="table-active row-total">
						<td class="rate"><strong><?php echo ( isset( $translate['total_due'] ) ? $translate['total_due'] : __( 'Total Due', 'sliced-invoices') ); ?></strong></td>
						<td class="total"><strong><?php echo esc_html( $total_due ) ?></strong></td>
					</tr>
					<?php
				} else {
					?>
					<tr class="table-active row-total">
						<td class="rate"><strong><?php echo ( isset( $translate['total_due'] ) ? $translate['total_due'] : __( 'Total Due', 'sliced-invoices') ); ?></strong></td>
						<td class="total"><strong><?php _e( sliced_get_invoice_total_due() ) ?></strong></td>
					</tr>
				<?php
				}
				?>
				<?php do_action( 'sliced_invoice_after_totals' ); ?>
			</tbody>

		</table>

		<?php do_action( 'sliced_invoice_after_totals_table' );

		$output = ob_get_clean();
		
		echo apply_filters( 'sliced_invoice_totals_output', $output );
	}


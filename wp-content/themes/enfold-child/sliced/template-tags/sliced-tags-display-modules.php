<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sender-Profildaten aus ACF Option »sender_profiles« inkl. Angebots-Templates pro Profil.
 */
function get_partycrowd_sender_data() {
	global $post;
	$post_id = isset( $post->ID ) ? $post->ID : get_the_ID();

	$selected_profile = get_post_meta( $post_id, 'invoice_sender_profile', true );

	if ( empty( $selected_profile ) ) {
		die( "FEHLER: Kein Absender-Profil für Rechnungs-ID $post_id ausgewählt!" );
	}

	$found               = false;
	$data                = [];
	$repeater_data       = get_field( 'sender_profiles', 'option' );
	$debug_found_names = [];

	if ( ! $repeater_data || ! is_array( $repeater_data ) ) {
		die( 'FEHLER: Konnte die Optionen-Seite nicht laden! Das ACF-Array ist leer.' );
	}

	foreach ( $repeater_data as $row ) {
		$current_company = isset( $row['company_name'] ) ? $row['company_name'] : '';
		$debug_found_names[] = $current_company;

		if ( strtolower( trim( $current_company ) ) === strtolower( trim( $selected_profile ) ) ) {
			$data = [
				'name'       => $current_company,
				'website'    => isset( $row['website'] ) ? $row['website'] : '',
				'address'    => isset( $row['address'] ) ? $row['address'] : '',
				'extra_info' => isset( $row['extra_info'] ) ? $row['extra_info'] : '',
				'mail'       => isset( $row['pc_mail'] ) ? $row['pc_mail'] : '',
				'logo'       => isset( $row['logo'] ) ? $row['logo'] : '',
				'phone'      => '06602325524',
				// Angebots-Vorlagen
				'deposit_1'   => isset( $row['quote_deposit_template_1'] ) ? $row['quote_deposit_template_1'] : '',
				'deposit_2'   => isset( $row['quote_deposit_template_2'] ) ? $row['quote_deposit_template_2'] : '',
				'deposit_3'   => isset( $row['quote_deposit_template_3'] ) ? $row['quote_deposit_template_3'] : '',
				'agb_phrases' => isset( $row['quote_agb_phrases'] ) ? $row['quote_agb_phrases'] : '',
			];
			$found = true;
			break;
		}
	}

	if ( ! $found ) {
		$all_names = implode( ', ', $debug_found_names );
		die( "FEHLER: Das Profil '$selected_profile' wurde nicht gefunden! Das System sieht aktuell nur diese Profile: [ $all_names ]." );
	}

	$data['clean_domain'] = str_replace( [ 'http://', 'https://', 'www.' ], '', $data['website'] );
	$data['clean_domain'] = rtrim( $data['clean_domain'], '/' );

	return $data;
}

/**
 * Bild-URL falls ACF Bildfeld Array liefert.
 *
 * @param mixed $acf_image URL-String oder ACF-Bildfeld-Array.
 * @return string
 */
function sliced_pc_resolve_image_url( $acf_image ) {
	if ( is_array( $acf_image ) ) {
		return isset( $acf_image['url'] ) ? (string) $acf_image['url'] : '';
	}
	return is_string( $acf_image ) ? $acf_image : '';
}

function sliced_display_business_new() {
	$profile = get_partycrowd_sender_data();
	if ( ! $profile ) {
		return;
	}
	$logo_url = sliced_pc_resolve_image_url( isset( $profile['logo'] ) ? $profile['logo'] : '' );
	?>
	<a target="_blank" href="<?php echo esc_url( $profile['website'] ); ?>">
		<img class="logo sliced-business-logo" src="<?php echo esc_url( $logo_url ); ?>" alt="">
	</a>
	<?php
}

function sliced_display_from_address_new() {
	$profile = get_partycrowd_sender_data();
	?>
	<div class="from"><strong><?php esc_html_e( 'VON:', 'sliced-invoices' ); ?></strong></div>
	<div class="wrapper">
		<div class="name">
			<a target="_blank" href="<?php echo esc_url( $profile['website'] ); ?>"><?php echo esc_html( $profile['name'] ); ?></a>
		</div>
		<?php
		echo ! empty( $profile['address'] ) ? '<div class="address">' . wpautop( wp_kses_post( $profile['address'] ) ) . '</div>' : '';
		echo ! empty( $profile['extra_info'] ) ? '<div class="extra_info">' . wpautop( wp_kses_post( $profile['extra_info'] ) ) . '</div>' : '';
		?>
	</div>
	<?php
}

function sliced_display_to_address_new() {
	if ( get_field( 'is_quote' ) && get_field( 'hide_recipient' ) ) {
		return;
	}

	$is_quote = (bool) get_field( 'is_quote' );
	$headline = $is_quote
		? __( 'Angebotsempfänger:', 'sliced-invoices' )
		: __( 'Rechnungsempfänger:', 'sliced-invoices' );

	$output  = '<div class="to"><strong>' . esc_html( $headline ) . '</strong></div>';
	$output .= '<div class="wrapper">';
	$client_data = get_field( 'client_invoice_relationship_sliced' );

	if ( $client_data ) {
		foreach ( $client_data as $client_data_ID ) {
			$output .= '<p>' . esc_html( (string) get_field( 'clients_company_name', $client_data_ID ) ) . '</p>';
			if ( get_field( 'clients_customer_agent', $client_data_ID ) ) {
				$output .= esc_html( (string) get_field( 'clients_customer_agent', $client_data_ID ) ) . '<br>';
			}
			if ( have_rows( 'clients_company_adress', $client_data_ID ) ) {
				while ( have_rows( 'clients_company_adress', $client_data_ID ) ) {
					the_row();
					$street = get_sub_field( 'clients_company_street' );
					if ( ! empty( $street ) ) {
						$output .= esc_html( (string) $street ) . ',<br>';
					}
					$country = get_sub_field( 'clients_company_country' );
					if ( ! empty( $country ) ) {
						$output .= esc_html( (string) $country ) . '<br>';
					}
					$plz = get_sub_field( 'clients_company_postal_code' );
					$ort = get_sub_field( 'clients_company_town' );
					if ( ! empty( $plz ) || ! empty( $ort ) ) {
						$output .= esc_html( trim( (string) $plz . ' ' . (string) $ort ) ) . '<br>';
					}
				}
			}
			if ( get_field( 'clients_tax_number', $client_data_ID ) ) {
				$output .= '<p>' . sprintf(
					/* translators: tax number */
					esc_html__( 'Steuer-Nr.: %s', 'sliced-invoices' ),
					esc_html( (string) get_field( 'clients_tax_number', $client_data_ID ) )
				) . '</p>';
			}
			if ( get_field( 'clients_uid_number', $client_data_ID ) ) {
				$output .= '<p>' . sprintf(
					/* translators: VAT ID */
					esc_html__( 'USt.-ID: %s', 'sliced-invoices' ),
					esc_html( (string) get_field( 'clients_uid_number', $client_data_ID ) )
				) . '</p>';
			}
			if ( get_field( 'clients_company_book_number', $client_data_ID ) ) {
				$output .= '<p>' . sprintf(
					/* translators: company register number */
					esc_html__( 'Firmenbuchnummer: %s', 'sliced-invoices' ),
					esc_html( (string) get_field( 'clients_company_book_number', $client_data_ID ) )
				) . '</p>';
			}
			if ( get_field( 'clients_mail', $client_data_ID ) ) {
				$output .= '<p>' . esc_html( (string) get_field( 'clients_mail', $client_data_ID ) ) . '</p><br>';
			}
		}
	}

	$output .= '</div>';

	echo $output;
}

function sliced_display_invoice_details_new() {
	$translate  = get_option( 'sliced_translate' );
	$is_quote   = (bool) get_field( 'is_quote' );
	$post_id    = get_the_ID();
	?>

	<table class="table table-bordered table-sm">
		<?php if ( sliced_get_invoice_number() || $is_quote ) : ?>
			<tr>
				<td>
					<?php
					if ( $is_quote ) {
						echo esc_html__( 'Angebotsnummer', 'sliced-invoices' );
					} else {
						printf( esc_html_x( 'Nummer', 'invoice number', 'sliced-invoices' ), sliced_get_invoice_label() );
					}
					?>
				</td>
				<td>
					<?php
					if ( $is_quote ) {
						$qn = get_field( 'quote_number' );
						echo esc_html( is_scalar( $qn ) ? (string) $qn : '' );
					} else {
						$global_num = get_post_meta( $post_id, '_global_invoice_number', true );
						if ( $global_num ) {
							echo 'RE-' . esc_html( (string) $global_num );
						} else {
							echo esc_html( sliced_get_invoice_prefix() );
							echo esc_html( sliced_get_invoice_number() );
							echo esc_html( sliced_get_invoice_suffix() );
						}
					}
					?>
				</td>
			</tr>
		<?php endif; ?>

		<?php if ( sliced_get_invoice_order_number() ) : ?>
			<tr>
				<td><?php esc_html_e( 'Order Number', 'sliced-invoices' ); ?></td>
				<td><?php echo esc_html( sliced_get_invoice_order_number() ); ?></td>
			</tr>
		<?php endif; ?>

		<?php if ( sliced_get_invoice_created() ) : ?>
			<tr>
				<td>
					<?php
					if ( $is_quote ) {
						echo esc_html__( 'Ausstellungsdatum', 'sliced-invoices' );
					} else {
						printf( esc_html_x( 'Datum', 'invoice date', 'sliced-invoices' ), sliced_get_invoice_label() );
					}
					?>
				</td>
				<td><?php echo Sliced_Shared::get_local_date_i18n_from_timestamp( sliced_get_invoice_created() ); ?></td>
			</tr>
		<?php endif; ?>

		<?php if ( sliced_get_invoice_due() ) : ?>
			<tr>
				<td>
					<?php
					echo $is_quote
						? esc_html__( 'Gültigkeitsdatum', 'sliced-invoices' )
						: esc_html__( 'Fälligkeitsdatum', 'sliced-invoices' );
					?>
				</td>
				<td><?php echo Sliced_Shared::get_local_date_i18n_from_timestamp( sliced_get_invoice_due() ); ?></td>
			</tr>
		<?php endif; ?>

		<tr class="table-active">
			<td><strong><?php echo esc_html( isset( $translate['total_due'] ) ? $translate['total_due'] : __( 'Total Due', 'sliced-invoices' ) ); ?></strong></td>
			<td><strong><?php echo sliced_get_invoice_total_due(); ?></strong></td>
		</tr>
	</table>
	<?php
}

function sliced_display_line_items_new() {
	$shared = new Sliced_Shared();

	$translate = get_option( 'sliced_translate' );

	$output = '<table class="table table-sm table-bordered table-striped">
        <thead>
            <tr>
                <th class="#" style="width: 20px !important; max-width: 20px !important; text-align: center;"><strong>#</strong></th>
                <th class="service" style="width: 55% !important;"><strong>' . esc_html( isset( $translate['service'] ) ? $translate['service'] : __( 'Service', 'sliced-invoices' ) ) . '</strong></th>
                <th class="qty"><strong>' . esc_html( isset( $translate['hrs_qty'] ) ? $translate['hrs_qty'] : __( 'Hrs/Qty', 'sliced-invoices' ) ) . '</strong></th>
                <th class="rate"><strong>' . esc_html( isset( $translate['rate_price'] ) ? $translate['rate_price'] : __( 'Rate/Price', 'sliced-invoices' ) ) . '</strong></th>';

	if ( sliced_hide_adjust_field() === false ) {
		$output .= '<th class="adjust"><strong>' . esc_html( isset( $translate['adjust'] ) ? $translate['adjust'] : __( 'Adjust', 'sliced-invoices' ) ) . '</strong></th>';
	}
	$output .= '<th class="total"><strong>' . esc_html( isset( $translate['sub_total'] ) ? $translate['sub_total'] : __( 'Sub Total', 'sliced-invoices' ) ) . '</strong></th>
            </tr>
        </thead>
        <tbody>';

	$count   = 0;
	$items   = sliced_get_invoice_line_items();
	$postion = 1;

	if ( ! empty( $items ) && ! empty( $items[0] ) ) {
		foreach ( $items[0] as $item ) {
			$class = ( $count % 2 === 0 ) ? 'even' : 'odd';

			$qty   = isset( $item['qty'] ) ? $item['qty'] : 0;
			$amt   = isset( $item['amount'] ) ? $shared->get_raw_number( $item['amount'] ) : 0;
			$tax   = isset( $item['tax'] ) ? $shared->get_raw_number( $item['tax'] ) : '0.00';
			$line_total = $shared->get_line_item_sub_total( $shared->get_raw_number( $qty ), $amt, $tax );

			$title_esc = isset( $item['title'] ) ? esc_html( $item['title'] ) : '';

			$output .= '<tr class="row_' . esc_attr( $class ) . ' sliced-item">
                    <td class="counter" style="width: 20px !important; max-width: 20px !important; text-align: center;">' . intval( $postion ) . '</td>
                    <td class="service">' . $title_esc;

			if ( isset( $item['description'] ) ) {
				$output .= '<br /><span class="description">' . wpautop( wp_kses_post( $item['description'] ) ) . '</span>';
			}

			$output .= '</td>
                    <td class="qty">' . esc_html( (string) $qty ) . '</td>
                    <td class="rate">' . esc_html( $shared->get_formatted_currency( $amt ) ) . '</td>';

			if ( sliced_hide_adjust_field() === false ) {
				$output .= '<td class="adjust">' . esc_html( sprintf( __( '%s%%', 'sliced-invoices' ), $tax ) ) . '</td>';
			}

			$output .= '<td class="total">' . esc_html( $shared->get_formatted_currency( $line_total ) ) . '</td>
                    </tr>';

			$postion++;
			$count++;
		}
	}

	$output .= '</tbody></table>';
	$output  = apply_filters( 'sliced_invoice_line_items_output', $output );
	echo $output;
}

function sliced_display_invoice_totals_new() {
	$translate = get_option( 'sliced_translate' );

	ob_start();
	do_action( 'sliced_invoice_before_totals_table' );

	if ( function_exists( 'sliced_woocommerce_get_order_id' ) ) {
		$order_id = sliced_woocommerce_get_order_id( get_the_ID() );
		if ( $order_id ) {
			echo ob_get_clean();
			return;
		}
	}
	?>

	<table class="table table-sm table-bordered">
		<tbody>
			<?php do_action( 'sliced_invoice_before_totals' ); ?>
			<tr class="row-sub-total">
				<td class="rate"><?php echo esc_html( isset( $translate['sub_total'] ) ? $translate['sub_total'] : __( 'Sub Total', 'sliced-invoices' ) ); ?></td>
				<td class="total"><?php echo sliced_get_invoice_sub_total(); ?></td>
			</tr>
			<?php do_action( 'sliced_invoice_after_sub_total' ); ?>
			<tr class="row-tax">
				<td class="rate"><?php echo sliced_get_tax_name(); ?></td>
				<td class="total"><?php echo sliced_get_invoice_tax(); ?></td>
			</tr>
			<?php do_action( 'sliced_invoice_after_tax' ); ?>
			<?php
			$totals = Sliced_Shared::get_totals( get_the_ID() );
			if ( $totals['discounts'] ) {
				$discount = Sliced_Shared::get_formatted_currency( $totals['discounts'] );
				?>
				<tr class="row-discount">
					<td class="rate"><?php echo esc_html( isset( $translate['discount'] ) ? $translate['discount'] : __( 'Discount', 'sliced-invoices' ) ); ?></td>
					<td class="total"><span style="color:red;">-<?php echo esc_html( $discount ); ?></span></td>
				</tr>
				<?php
			}

			if ( $totals['payments'] ) {
				$paid = Sliced_Shared::get_formatted_currency( $totals['payments'] );
				?>
				<tr class="row-paid">
					<td class="rate"><?php esc_html_e( 'Paid', 'sliced-invoices' ); ?></td>
					<td class="total"><span style="color:red;">-<?php echo esc_html( $paid ); ?></span></td>
				</tr>
				<?php
			}

			if ( $totals['payments'] || $totals['discounts'] ) {
				$total_due = Sliced_Shared::get_formatted_currency( $totals['total_due'] );
				?>
				<tr class="table-active row-total">
					<td class="rate"><strong><?php echo esc_html( isset( $translate['total_due'] ) ? $translate['total_due'] : __( 'Total Due', 'sliced-invoices' ) ); ?></strong></td>
					<td class="total"><strong><?php echo esc_html( $total_due ); ?></strong></td>
				</tr>
				<?php
			} else {
				?>
				<tr class="table-active row-total">
					<td class="rate"><strong><?php echo esc_html( isset( $translate['total_due'] ) ? $translate['total_due'] : __( 'Total Due', 'sliced-invoices' ) ); ?></strong></td>
					<td class="total"><strong><?php echo sliced_get_invoice_total_due(); ?></strong></td>
				</tr>
				<?php
			}
			?>
			<?php do_action( 'sliced_invoice_after_totals' ); ?>
		</tbody>
	</table>
	<?php
	do_action( 'sliced_invoice_after_totals_table' );

	$output = ob_get_clean();
	echo apply_filters( 'sliced_invoice_totals_output', $output );
}

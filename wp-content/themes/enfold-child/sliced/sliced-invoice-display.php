<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hinweis: Themes können diese Datei unter themes/…/sliced/ überschreiben.
 */

do_action( 'sliced_before_invoice_display' );

$is_quote = (bool) get_field( 'is_quote' );

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<title><?php wp_title(); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow">

	<?php do_action( 'sliced_head' ); ?>
	<?php do_action( 'sliced_invoice_head' ); ?>
</head>

<?php do_action( 'sliced_invoice_before_body' ); ?>

<body class="body sliced-invoice">
	<button type="button" id="clean-pdf-btn" class="no-print" style="padding: 12px 25px; background: #0073aa; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin: 16px;">
		<?php echo esc_html( $is_quote ? __( 'Angebot', 'sliced-invoices' ) : __( 'Rechnung', 'sliced-invoices' ) ); ?> <?php esc_html_e( 'normal als A4 PDF speichern', 'sliced-invoices' ); ?>
	</button>

	<script>
		document.addEventListener('DOMContentLoaded', function() {
			var btn = document.getElementById('clean-pdf-btn');
			if (!btn) return;
			btn.addEventListener('click', function() {
				var printStyle = document.createElement('style');
				printStyle.innerHTML =
					'@media print {' +
						'@page { size: A4 !important; margin: 0 !important; }' +
						'header:not(.invoice-header), footer:not(.invoice-footer), nav, .site-header, .site-footer, #wpadminbar, .no-print, .print-only {' +
							'display: none !important;' +
						'}' +
						'body, html { background: white !important; padding: 0 !important; margin: 0 !important; height: max-content !important; }' +
						'.sliced-invoice, #sliced-invoice-container, .invoice-template, .sliced-wrap {' +
							'width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0.5cm !important;' +
							'box-sizing: border-box !important; box-shadow: none !important;' +
						'}' +
						'.sliced-upper { margin-top: 0 !important; padding-top: 10px !important; }' +
						'table { width: 100% !important; font-size: 12px !important; }' +
						'body { font-size: 12px !important; line-height: 1.4 !important; }' +
						'.invoice-logo img, .logo img, #logo img, header img, .sliced-invoice-logo img, .sliced-business-logo {' +
							'max-width: 120px !important; max-height: 80px !important; width: auto !important; height: auto !important;' +
						'}' +
						'.row.sliced-payments { margin-top: 0px !important; margin-bottom: 10px !important; padding: 10px 30px !important; }' +
						'.row.sliced-payments p:last-child { margin: 0 !important; }' +
						'.generic, .bank { padding: 5px 10px !important; margin-bottom: 0px !important; }' +
						'.row.sliced-footer { margin-top: 0px !important; margin-bottom: 0px !important; }' +
						'.terms-text { margin-bottom: 0px !important; }' +
						'htmlpagefooter, .footer-text { margin-top: 10px !important; margin-bottom: 0 !important; padding-top: 0 !important; padding-bottom: 0 !important; }' +
						'hr { margin-top: 0px !important; margin-bottom: 0px !important; }' +
					'}';
				document.head.appendChild(printStyle);
				window.print();
				setTimeout(function() {
					if (printStyle.parentNode) printStyle.parentNode.removeChild(printStyle);
				}, 1000);
			});
		});
	</script>

	<div class="container sliced-wrap">

	<?php if ( $watermark = sliced_get_invoice_watermark() ) : ?>
		<div class="watermark no-print"><p><?php echo esc_html( $watermark ); ?></p></div>
	<?php endif; ?>

		<htmlpageheader name="sliced-pdf-header">
			<div class="row sliced-header">
				<div class="col-xs-12 col-sm-6 sliced-business">
					<?php sliced_display_business_new(); ?>
				</div>
				<div class="col-xs-12 col-sm-6 sliced-title">
					<h2><?php echo $is_quote ? esc_html__( 'Angebot', 'sliced-invoices' ) : esc_html( sliced_get_invoice_label() ); ?></h2>
				</div>
			</div>
		</htmlpageheader>

		<div class="row sliced-upper">
			<div class="col-xs-12 col-sm-6 sliced-from-address sliced-address">
				<?php sliced_display_from_address_new(); ?>
			</div>
			<div class="col-xs-12 col-sm-5 sliced-details">
				<?php sliced_display_invoice_details_new(); ?>
			</div>
		</div>

		<div class="row sliced-middle">
			<div class="col-xs-12 col-sm-6 sliced-to-address sliced-address">
				<?php sliced_display_to_address_new(); ?>
			</div>
		</div>

		<?php if ( sliced_get_invoice_description() ) : ?>
			<div class="row sliced-lower">
				<div class="col-sm-12 sliced-description">
					<?php echo wpautop( sliced_get_invoice_description() ); ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="row sliced-items">
			<div class="col-sm-12 sliced-line-items">
				<div class="table-responsive">
					<?php sliced_display_line_items_new(); ?>
				</div>
			</div>
		</div>
		<div class="row sliced-items">
			<div class="col-xs-12 col-sm-5 sliced-totals">
				<?php sliced_display_invoice_totals_new(); ?>
			</div>
		</div>

		<?php if ( $is_quote ) : ?>
			<div class="row sliced-payments">
				<div class="col-sm-12">
					<div class="generic" style="background:#f9f9f9; padding:15px; border:1px solid #eee;">
						<?php
						$deposit_type = get_field( 'deposit_terms_type' );
						$deposit_text = '';
						$profile        = get_partycrowd_sender_data();

						if ( 'template_1' === $deposit_type ) {
							$deposit_text = isset( $profile['deposit_1'] ) ? $profile['deposit_1'] : '';
						} elseif ( 'template_2' === $deposit_type ) {
							$deposit_text = isset( $profile['deposit_2'] ) ? $profile['deposit_2'] : '';
						} elseif ( 'template_3' === $deposit_type ) {
							$deposit_text = isset( $profile['deposit_3'] ) ? $profile['deposit_3'] : '';
						} else {
							$cust = get_field( 'custom_deposit_terms' );
							$deposit_text = is_string( $cust ) ? $cust : '';
						}

						echo wpautop( wp_kses_post( $deposit_text ) );
						?>
					</div>
				</div>
			</div>
		<?php elseif ( sliced_is_payment_method( 'generic' ) || sliced_is_payment_method( 'bank' ) ) : ?>
			<div class="row sliced-payments">
				<div class="col-sm-12">
					<?php if ( sliced_is_payment_method( 'generic' ) ) : ?>
						<div class="generic"><?php echo wpautop( sliced_get_business_generic_payment() ); ?></div>
					<?php endif; ?>
					<?php if ( sliced_is_payment_method( 'bank' ) ) : ?>
						<div class="bank"><?php echo wpautop( sliced_get_business_bank() ); ?></div>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>

		<htmlpagefooter name="sliced-pdf-footer">
			<div class="row sliced-footer">
				<div class="col-sm-12">
					<?php
					$profile = get_partycrowd_sender_data();

					if ( $is_quote ) {
						if ( ! empty( $profile['agb_phrases'] ) ) {
							echo '<div style="text-align: center; margin-bottom: 5px; font-size: 11px;">' . wpautop( wp_kses_post( $profile['agb_phrases'] ) ) . '</div>';
						}
					} else {
						$global_num = get_post_meta( get_the_ID(), '_global_invoice_number', true );
						if ( $global_num ) {
							$old_num_format = sliced_get_invoice_prefix() . sliced_get_invoice_number() . sliced_get_invoice_suffix();
							echo '<p style="text-align: center; margin-bottom: 5px; font-weight: bold;">Rechnungs-Nr. RE-' . esc_html( $global_num ) . ', Referenz: ' . esc_html( $old_num_format ) . '</p>';
						}
					}

					$footer_domain = ! empty( $profile['clean_domain'] ) ? $profile['clean_domain'] : 'partycrowd.at';
					$footer_mail    = ! empty( $profile['mail'] ) ? $profile['mail'] : 'office@' . $footer_domain;
					$footer_phone   = isset( $profile['phone'] ) ? $profile['phone'] : '';

					$dynamic_footer = 'Website: ' . esc_html( $footer_domain ) . ' &ndash; Email: ' . esc_html( $footer_mail ) . ' &ndash; Telefon: ' . esc_html( $footer_phone );
					?>

					<div class="footer-text" style="text-align: center;">
						<?php echo $dynamic_footer; ?>
					</div>

					<div class="print-only"><?php esc_html_e( 'Page', 'sliced-invoices' ); ?> {PAGENO}/{nbpg}</div>
				</div>
			</div>
		</htmlpagefooter>

	</div>

	<?php do_action( 'sliced_invoice_footer' ); ?>
	<?php do_action( 'sliced_template_footer' ); ?>
</body>
</html>

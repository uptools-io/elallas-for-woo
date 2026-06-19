<?php
/**
 * Withdrawal statement — standalone printable PDF document.
 *
 * @package LightweightPlugins\Elallas
 *
 * @var \LightweightPlugins\Elallas\Models\WithdrawalCase|null $case  Withdrawal case.
 * @var array<int, \LightweightPlugins\Elallas\Models\CaseItem> $items Withdrawn line items.
 * @var \WC_Order|null                                          $order Source order (may be null).
 */

defined( 'ABSPATH' ) || exit;

use LightweightPlugins\Elallas\Options;
use LightweightPlugins\Elallas\Data\DefaultTexts;

$elallas_case          = ( isset( $case ) && $case ) ? $case : null;
$elallas_items         = ( isset( $items ) && is_array( $items ) ) ? $items : [];
$elallas_order         = ( isset( $order ) && $order instanceof \WC_Order ) ? $order : null;
$elallas_merchant      = get_bloginfo( 'name' );
$elallas_merchant_url  = home_url();
$elallas_case_number   = $elallas_case ? (string) $elallas_case->case_number : '';
$elallas_case_id       = $elallas_case ? (int) $elallas_case->id : 0;
$elallas_submitted     = $elallas_case ? (string) $elallas_case->submitted_at : '';
$elallas_order_number  = $elallas_order ? (string) $elallas_order->get_order_number() : '';
$elallas_order_date    = ( $elallas_order && $elallas_order->get_date_created() ) ? $elallas_order->get_date_created()->date( 'Y-m-d H:i' ) : '';
$elallas_customer_name = $elallas_order ? trim( $elallas_order->get_formatted_billing_full_name() ) : '';
$elallas_customer_mail = $elallas_order ? (string) $elallas_order->get_billing_email() : '';
$elallas_declaration   = (string) Options::get( 'legal_declaration' );
$elallas_declaration   = '' !== $elallas_declaration ? $elallas_declaration : DefaultTexts::declaration();
$elallas_generated     = current_time( 'Y-m-d H:i:s' );
$elallas_hash          = '________________________________________________________________';
$elallas_bank          = ( $elallas_case && ! empty( $elallas_case->bank_account_encrypted ) )
	? \LightweightPlugins\Elallas\Security\Encryption::decrypt( (string) $elallas_case->bank_account_encrypted )
	: '';
?>
<!DOCTYPE html>
<html lang="hu">
<head>
	<meta charset="utf-8">
	<title><?php echo esc_html__( 'Elállási nyilatkozat', 'elallas-for-woo' ); ?></title>
	<style>
		body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1d2327; line-height: 1.5; }
		h1 { font-size: 18px; margin: 0 0 4px; }
		h2 { font-size: 13px; margin: 18px 0 6px; border-bottom: 1px solid #c3c4c7; padding-bottom: 3px; }
		.elallas-meta { color: #50575e; font-size: 10px; margin: 0 0 12px; }
		table.elallas-fields { width: 100%; border-collapse: collapse; }
		table.elallas-fields td { padding: 3px 6px; vertical-align: top; }
		table.elallas-fields td.label { width: 35%; color: #50575e; }
		table.elallas-items { width: 100%; border-collapse: collapse; margin-top: 6px; }
		table.elallas-items th, table.elallas-items td { border: 1px solid #c3c4c7; padding: 5px 6px; text-align: left; }
		table.elallas-items th { background: #f0f0f1; }
		.elallas-declaration { margin: 12px 0; padding: 10px; border: 1px solid #c3c4c7; background: #f6f7f7; }
		.elallas-footer { margin-top: 18px; font-size: 9px; color: #787c82; border-top: 1px solid #c3c4c7; padding-top: 6px; }
		.elallas-footer code { font-size: 9px; word-break: break-all; }
	</style>
</head>
<body>
	<h1><?php echo esc_html__( 'Elállási nyilatkozat', 'elallas-for-woo' ); ?></h1>
	<p class="elallas-meta">
		<?php echo esc_html( $elallas_merchant ); ?> &mdash;
		<?php echo esc_html( $elallas_merchant_url ); ?>
	</p>

	<h2><?php echo esc_html__( 'Ügy adatai', 'elallas-for-woo' ); ?></h2>
	<table class="elallas-fields">
		<tr>
			<td class="label"><?php echo esc_html__( 'Ügyazonosító', 'elallas-for-woo' ); ?></td>
			<td><?php echo esc_html( $elallas_case_number ); ?></td>
		</tr>
		<tr>
			<td class="label"><?php echo esc_html__( 'Beérkezés időpontja', 'elallas-for-woo' ); ?></td>
			<td><?php echo esc_html( $elallas_submitted ); ?></td>
		</tr>
	</table>

	<h2><?php echo esc_html__( 'Fogyasztó és rendelés', 'elallas-for-woo' ); ?></h2>
	<table class="elallas-fields">
		<tr>
			<td class="label"><?php echo esc_html__( 'Fogyasztó neve', 'elallas-for-woo' ); ?></td>
			<td><?php echo esc_html( $elallas_customer_name ); ?></td>
		</tr>
		<tr>
			<td class="label"><?php echo esc_html__( 'E-mail cím', 'elallas-for-woo' ); ?></td>
			<td><?php echo esc_html( $elallas_customer_mail ); ?></td>
		</tr>
		<tr>
			<td class="label"><?php echo esc_html__( 'Rendelésszám', 'elallas-for-woo' ); ?></td>
			<td><?php echo esc_html( $elallas_order_number ); ?></td>
		</tr>
		<tr>
			<td class="label"><?php echo esc_html__( 'Rendelés dátuma', 'elallas-for-woo' ); ?></td>
			<td><?php echo esc_html( $elallas_order_date ); ?></td>
		</tr>
		<?php if ( '' !== $elallas_bank ) : ?>
		<tr>
			<td class="label"><?php echo esc_html__( 'Visszatérítési bankszámla', 'elallas-for-woo' ); ?></td>
			<td><?php echo esc_html( $elallas_bank ); ?></td>
		</tr>
		<?php endif; ?>
	</table>

	<h2><?php echo esc_html__( 'Érintett termékek', 'elallas-for-woo' ); ?></h2>
	<table class="elallas-items">
		<thead>
			<tr>
				<th><?php echo esc_html__( 'Termék', 'elallas-for-woo' ); ?></th>
				<th><?php echo esc_html__( 'Cikkszám', 'elallas-for-woo' ); ?></th>
				<th><?php echo esc_html__( 'Mennyiség', 'elallas-for-woo' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $elallas_items ) ) : ?>
				<tr>
					<td colspan="3"><?php echo esc_html__( 'Nincs megadott tétel.', 'elallas-for-woo' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $elallas_items as $elallas_item ) : ?>
					<tr>
						<td><?php echo esc_html( (string) $elallas_item->product_name_snapshot ); ?></td>
						<td><?php echo esc_html( (string) $elallas_item->sku_snapshot ); ?></td>
						<td><?php echo esc_html( (string) $elallas_item->qty_withdrawn ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<h2><?php echo esc_html__( 'Nyilatkozat', 'elallas-for-woo' ); ?></h2>
	<div class="elallas-declaration">
		<?php echo wp_kses_post( wpautop( $elallas_declaration ) ); ?>
	</div>

	<div class="elallas-footer">
		<p>
			<?php echo esc_html__( 'Ügy azonosító (belső):', 'elallas-for-woo' ); ?>
			<?php echo esc_html( (string) $elallas_case_id ); ?>
		</p>
		<p>
			<?php echo esc_html__( 'Dokumentum lenyomata (SHA-256):', 'elallas-for-woo' ); ?>
			<code><?php echo esc_html( $elallas_hash ); ?></code>
		</p>
		<p>
			<?php echo esc_html__( 'Dokumentum generálva:', 'elallas-for-woo' ); ?>
			<?php echo esc_html( $elallas_generated ); ?>
		</p>
	</div>
</body>
</html>

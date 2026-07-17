<?php
/**
 * Admin notification email (HTML).
 *
 * @package LightweightPlugins\Elallas
 *
 * @var \LightweightPlugins\Elallas\Models\WithdrawalCase     $case
 * @var array<int, \LightweightPlugins\Elallas\Models\CaseItem> $items
 * @var string                                                $email_heading
 * @var bool                                                  $sent_to_admin
 * @var bool                                                  $plain_text
 * @var \WC_Email                                             $email
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$elallas_order_url = admin_url( 'post.php?post=' . $case->order_id . '&action=edit' );
$elallas_case_url  = admin_url( 'admin.php?page=elallas-for-woo&view=case&case_id=' . $case->id );

$elallas_excepted = array_filter( $items, static fn ( $elallas_i ) => 'excepted' === $elallas_i->eligibility_flag );

do_action( 'woocommerce_email_header', $email_heading, $email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core email template hook.
?>

<p><?php esc_html_e( 'Új elállási nyilatkozat érkezett az alábbi rendeléshez.', 'elallas-for-woo' ); ?></p>

<?php if ( ! empty( $elallas_excepted ) ) : ?>
<div style="border:2px solid #b32d2e; background:#fcf0f1; padding:12px 16px; margin-bottom:20px;">
	<p style="margin:0 0 8px; color:#b32d2e; font-weight:bold;">
		<?php esc_html_e( '⚠ Figyelem: a nyilatkozat elállásból kizártként megjelölt terméke(ke)t tartalmaz. Kérjük, fokozott figyelemmel ellenőrizze a rendelést.', 'elallas-for-woo' ); ?>
	</p>
	<ul style="margin:0; padding-left:20px;">
		<?php foreach ( $elallas_excepted as $elallas_ex ) : ?>
		<li>
			<?php echo esc_html( $elallas_ex->product_name_snapshot ); ?>
			<?php if ( '' !== $elallas_ex->eligibility_note ) : ?>
				&mdash; <em><?php echo esc_html( $elallas_ex->eligibility_note ); ?></em>
			<?php endif; ?>
		</li>
		<?php endforeach; ?>
	</ul>
</div>
<?php endif; ?>

<table cellspacing="0" cellpadding="6" border="1" style="width: 100%; border: 1px solid #e5e5e5; margin-bottom: 20px;">
	<tr>
		<th scope="row" style="text-align: left; padding: 12px;"><?php esc_html_e( 'Ügyazonosító', 'elallas-for-woo' ); ?></th>
		<td style="padding: 12px;"><?php echo esc_html( $case->case_number ); ?></td>
	</tr>
	<tr>
		<th scope="row" style="text-align: left; padding: 12px;"><?php esc_html_e( 'Rendelés', 'elallas-for-woo' ); ?></th>
		<td style="padding: 12px;">
			<a href="<?php echo esc_url( $elallas_order_url ); ?>">#<?php echo esc_html( $case->order_number ); ?></a>
		</td>
	</tr>
	<tr>
		<th scope="row" style="text-align: left; padding: 12px;"><?php esc_html_e( 'Beérkezés időpontja', 'elallas-for-woo' ); ?></th>
		<td style="padding: 12px;"><?php echo esc_html( (string) $case->submitted_at ); ?></td>
	</tr>
	<tr>
		<th scope="row" style="text-align: left; padding: 12px;"><?php esc_html_e( 'Határidő státusza', 'elallas-for-woo' ); ?></th>
		<td style="padding: 12px;"><?php echo esc_html( $case->deadline_label() ); ?></td>
	</tr>
</table>

<?php if ( ! empty( $items ) ) : ?>
<h3><?php esc_html_e( 'Az elállással érintett termékek', 'elallas-for-woo' ); ?></h3>
<table cellspacing="0" cellpadding="6" border="1" style="width: 100%; border: 1px solid #e5e5e5; margin-bottom: 20px;">
	<thead>
		<tr>
			<th scope="col" style="text-align: left; padding: 12px;"><?php esc_html_e( 'Termék', 'elallas-for-woo' ); ?></th>
			<th scope="col" style="text-align: left; padding: 12px;"><?php esc_html_e( 'Mennyiség', 'elallas-for-woo' ); ?></th>
			<th scope="col" style="text-align: left; padding: 12px;"><?php esc_html_e( 'Jogosultság', 'elallas-for-woo' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $items as $elallas_item ) : ?>
		<tr>
			<td style="padding: 12px;"><?php echo esc_html( $elallas_item->product_name_snapshot ); ?></td>
			<td style="padding: 12px;"><?php echo esc_html( (string) $elallas_item->qty_withdrawn ); ?></td>
			<td style="padding: 12px;<?php echo $elallas_item->is_excepted() ? ' color:#b32d2e; font-weight:600;' : ''; ?>">
				<?php
				echo esc_html( $elallas_item->eligibility_label() );
				if ( $elallas_item->is_excepted() && '' !== $elallas_item->eligibility_note ) {
					echo '<br /><span style="font-weight:400; color:#50575e;">' . esc_html( $elallas_item->eligibility_note ) . '</span>';
				}
				?>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php endif; ?>

<p>
	<a href="<?php echo esc_url( $elallas_case_url ); ?>"><?php esc_html_e( 'Ügy megnyitása az adminban', 'elallas-for-woo' ); ?></a>
</p>

<?php
do_action( 'woocommerce_email_footer', $email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core email template hook.

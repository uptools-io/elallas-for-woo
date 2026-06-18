<?php
/**
 * Customer confirmation email (HTML).
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

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p><?php esc_html_e( 'Tisztelt Vásárlónk!', 'elallas-for-woo' ); ?></p>

<p>
	<?php
	printf(
		/* translators: 1: case number, 2: order number */
		esc_html__( 'Megerősítjük, hogy elállási nyilatkozatát tartós adathordozón rögzítettük. Ügyazonosító: %1$s, rendelés: #%2$s.', 'elallas-for-woo' ),
		'<strong>' . esc_html( $case->case_number ) . '</strong>',
		esc_html( $case->order_number )
	);
	?>
</p>

<table cellspacing="0" cellpadding="6" border="1" style="width: 100%; border: 1px solid #e5e5e5; margin-bottom: 20px;">
	<tr>
		<th scope="row" style="text-align: left; padding: 12px;"><?php esc_html_e( 'Ügyazonosító', 'elallas-for-woo' ); ?></th>
		<td style="padding: 12px;"><?php echo esc_html( $case->case_number ); ?></td>
	</tr>
	<tr>
		<th scope="row" style="text-align: left; padding: 12px;"><?php esc_html_e( 'Beérkezés időpontja', 'elallas-for-woo' ); ?></th>
		<td style="padding: 12px;"><?php echo esc_html( (string) $case->submitted_at ); ?></td>
	</tr>
	<tr>
		<th scope="row" style="text-align: left; padding: 12px;"><?php esc_html_e( 'Rendelésszám', 'elallas-for-woo' ); ?></th>
		<td style="padding: 12px;"><?php echo esc_html( $case->order_number ); ?></td>
	</tr>
</table>

<?php if ( ! empty( $items ) ) : ?>
<h3><?php esc_html_e( 'Az elállással érintett termékek', 'elallas-for-woo' ); ?></h3>
<table cellspacing="0" cellpadding="6" border="1" style="width: 100%; border: 1px solid #e5e5e5; margin-bottom: 20px;">
	<thead>
		<tr>
			<th scope="col" style="text-align: left; padding: 12px;"><?php esc_html_e( 'Termék', 'elallas-for-woo' ); ?></th>
			<th scope="col" style="text-align: left; padding: 12px;"><?php esc_html_e( 'Mennyiség', 'elallas-for-woo' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $items as $elallas_item ) : ?>
		<tr>
			<td style="padding: 12px;"><?php echo esc_html( $elallas_item->product_name_snapshot ); ?></td>
			<td style="padding: 12px;"><?php echo esc_html( (string) $elallas_item->qty_withdrawn ); ?></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php endif; ?>

<p>
	<?php
	printf(
		/* translators: %s: shop name */
		esc_html__( 'A nyilatkozatot a következő kereskedő felé tette meg: %s.', 'elallas-for-woo' ),
		'<strong>' . esc_html( get_bloginfo( 'name' ) ) . '</strong>'
	);
	?>
</p>

<p><?php esc_html_e( 'Az elállás feldolgozásáról és a visszatérítésről a kereskedő külön értesíti Önt. A vételárat legkésőbb az elállás kézhezvételétől számított 14 napon belül visszatérítjük.', 'elallas-for-woo' ); ?></p>

<p><?php esc_html_e( 'Kérjük, őrizze meg ezt az e-mailt, amely az elállás visszaigazolásaként szolgál.', 'elallas-for-woo' ); ?></p>

<?php
do_action( 'woocommerce_email_footer', $email );

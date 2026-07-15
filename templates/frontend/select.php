<?php
/**
 * Step 2: select items to withdraw.
 *
 * @package LightweightPlugins\Elallas
 *
 * @var \WC_Order $order           Order object.
 * @var string    $order_number    Carried order number.
 * @var string    $email           Carried email.
 * @var array     $items           Order line items.
 * @var string    $deadline_status Deadline status flag.
 * @var string    $form_action     Form action URL.
 * @var string    $nonce_field     Nonce field markup.
 * @var string    $honeypot        Honeypot field markup.
 * @var string    $error           Optional error message.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$elallas_error = isset( $error ) ? (string) $error : '';
?>
<h2><?php esc_html_e( 'Elállással érintett termékek kiválasztása', 'elallas-for-woo' ); ?></h2>

<ul class="elallas-order-summary">
	<li><strong><?php esc_html_e( 'Rendelés:', 'elallas-for-woo' ); ?></strong> <?php echo esc_html( $order->get_order_number() ); ?></li>
	<li><strong><?php esc_html_e( 'Rendelés dátuma:', 'elallas-for-woo' ); ?></strong> <?php echo esc_html( $order->get_date_created() ? wc_format_datetime( $order->get_date_created() ) : '—' ); ?></li>
	<li><strong><?php esc_html_e( 'Fizetési mód:', 'elallas-for-woo' ); ?></strong> <?php echo esc_html( $order->get_payment_method_title() ); ?></li>
	<li><strong><?php esc_html_e( 'Határidő státusz:', 'elallas-for-woo' ); ?></strong> <?php echo esc_html( \LightweightPlugins\Elallas\Models\DeadlineStatus::label( $deadline_status ) ); ?></li>
</ul>

<?php if ( '' !== $elallas_error ) : ?>
	<div class="elallas-notice elallas-notice-error"><?php echo esc_html( $elallas_error ); ?></div>
<?php endif; ?>

<form class="elallas-step-form" method="post" action="<?php echo esc_url( $form_action ); ?>">
	<?php
	echo $nonce_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $honeypot;    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
	<input type="hidden" name="elallas_step" value="select" />
	<input type="hidden" name="order_number" value="<?php echo esc_attr( $order_number ); ?>" />
	<input type="hidden" name="email" value="<?php echo esc_attr( $email ); ?>" />

	<p class="elallas-hint"><?php esc_html_e( 'Teljes elálláshoz hagyja minden terméknél a teljes mennyiséget. Részleges elálláshoz csökkentse a mennyiséget, a kihagyott termékeknél állítsa 0-ra.', 'elallas-for-woo' ); ?></p>

	<?php
	$elallas_has_excluded = false;
	foreach ( $items as $elallas_check ) {
		if ( ! empty( $elallas_check['excluded'] ) ) {
			$elallas_has_excluded = true;
			break;
		}
	}
	?>
	<?php if ( $elallas_has_excluded ) : ?>
		<p class="elallas-hint elallas-hint-excluded"><?php esc_html_e( 'A megjelölt termék(ek)re nem gyakorolható elállás, ezért nem választhatók ki. A rendelés többi termékére az elállás továbbra is kezdeményezhető.', 'elallas-for-woo' ); ?></p>
	<?php endif; ?>

	<table class="elallas-items">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Termék', 'elallas-for-woo' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Rendelt', 'elallas-for-woo' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Elállás mennyiség', 'elallas-for-woo' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $items as $elallas_item ) : ?>
				<?php $elallas_excluded = ! empty( $elallas_item['excluded'] ); ?>
				<tr class="<?php echo $elallas_excluded ? 'elallas-item-excluded' : ''; ?>">
					<th scope="row">
						<?php echo esc_html( $elallas_item['product_name_snapshot'] ); ?>
						<?php if ( '' !== $elallas_item['sku_snapshot'] ) : ?>
							<span class="elallas-sku">(<?php echo esc_html( $elallas_item['sku_snapshot'] ); ?>)</span>
						<?php endif; ?>
						<?php if ( $elallas_excluded ) : ?>
							<span class="elallas-badge elallas-badge-excluded"><?php esc_html_e( 'Elállásból kizárva', 'elallas-for-woo' ); ?></span>
							<?php if ( '' !== trim( (string) ( $elallas_item['exclusion_reason'] ?? '' ) ) ) : ?>
								<span class="elallas-excluded-reason"><?php echo esc_html( (string) $elallas_item['exclusion_reason'] ); ?></span>
							<?php endif; ?>
						<?php endif; ?>
					</th>
					<td><?php echo esc_html( (string) $elallas_item['qty_ordered'] ); ?></td>
					<td>
						<?php if ( $elallas_excluded ) : ?>
							<span class="elallas-not-available"><span class="screen-reader-text"><?php esc_html_e( 'Nem választható', 'elallas-for-woo' ); ?></span><span aria-hidden="true">—</span></span>
						<?php else : ?>
							<input
								type="number"
								id="elallas-qty-<?php echo esc_attr( (string) $elallas_item['order_item_id'] ); ?>"
								aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name. */ __( 'Elállás mennyiség – %s', 'elallas-for-woo' ), $elallas_item['product_name_snapshot'] ) ); ?>"
								name="items[<?php echo esc_attr( (string) $elallas_item['order_item_id'] ); ?>]"
								value="<?php echo esc_attr( (string) $elallas_item['qty_ordered'] ); ?>"
								min="0"
								max="<?php echo esc_attr( (string) $elallas_item['qty_ordered'] ); ?>"
								step="1"
							/>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<p class="elallas-actions">
		<button type="submit" class="button elallas-button"><?php esc_html_e( 'Tovább a megerősítéshez', 'elallas-for-woo' ); ?></button>
	</p>
</form>

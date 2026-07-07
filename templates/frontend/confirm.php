<?php
/**
 * Step 3: confirmation + consent.
 *
 * @package LightweightPlugins\Elallas
 *
 * @var \WC_Order $order           Order object.
 * @var string    $order_number    Carried order number.
 * @var string    $email           Carried email.
 * @var array     $rows            Selected snapshot rows.
 * @var array     $selected        Map order_item_id => qty.
 * @var string    $withdrawal_type full|partial.
 * @var string    $deadline_status Deadline status flag.
 * @var string    $declaration     Declaration text.
 * @var string    $confirm_label   Translated confirm-button label.
 * @var string    $form_action     Form action URL.
 * @var string    $nonce_field     Nonce field markup.
 * @var string    $honeypot        Honeypot field markup.
 * @var string    $error           Optional error message.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$elallas_error         = isset( $error ) ? (string) $error : '';
$elallas_confirm_label = ( isset( $confirm_label ) && '' !== (string) $confirm_label )
	? (string) $confirm_label
	: __( 'Elállás megerősítése', 'elallas-for-woo' );
?>
<h2><?php esc_html_e( 'Elállási nyilatkozat megerősítése', 'elallas-for-woo' ); ?></h2>

<p><?php esc_html_e( 'Ön az alábbi rendelésre vonatkozóan elállási nyilatkozatot kíván tenni.', 'elallas-for-woo' ); ?></p>

<ul class="elallas-order-summary">
	<li><strong><?php esc_html_e( 'Rendelés:', 'elallas-for-woo' ); ?></strong> <?php echo esc_html( $order->get_order_number() ); ?></li>
	<li><strong><?php esc_html_e( 'Típus:', 'elallas-for-woo' ); ?></strong>
		<?php echo 'full' === $withdrawal_type ? esc_html__( 'Teljes elállás', 'elallas-for-woo' ) : esc_html__( 'Részleges elállás', 'elallas-for-woo' ); ?>
	</li>
</ul>

<table class="elallas-items">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Termék', 'elallas-for-woo' ); ?></th>
			<th><?php esc_html_e( 'Mennyiség', 'elallas-for-woo' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $rows as $elallas_row ) : ?>
			<tr>
				<td><?php echo esc_html( $elallas_row['product_name_snapshot'] ); ?></td>
				<td><?php echo esc_html( (string) $elallas_row['qty_withdrawn'] ); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<blockquote class="elallas-declaration"><?php echo esc_html( $declaration ); ?></blockquote>

<?php if ( '' !== $elallas_error ) : ?>
	<div class="elallas-notice elallas-notice-error"><?php echo esc_html( $elallas_error ); ?></div>
<?php endif; ?>

<form class="elallas-step-form" method="post" action="<?php echo esc_url( $form_action ); ?>">
	<?php
	echo $nonce_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $honeypot;    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
	<input type="hidden" name="elallas_step" value="confirm" />
	<input type="hidden" name="order_number" value="<?php echo esc_attr( $order_number ); ?>" />
	<input type="hidden" name="email" value="<?php echo esc_attr( $email ); ?>" />
	<?php foreach ( (array) $selected as $elallas_item_id => $elallas_qty ) : ?>
		<input type="hidden" name="items[<?php echo esc_attr( (string) $elallas_item_id ); ?>]" value="<?php echo esc_attr( (string) $elallas_qty ); ?>" />
	<?php endforeach; ?>

	<p class="elallas-field">
		<label for="elallas-bank"><?php esc_html_e( 'Bankszámlaszám / IBAN (opcionális)', 'elallas-for-woo' ); ?></label>
		<input type="text" id="elallas-bank" name="bank_account" value="" autocomplete="off" />
		<span class="elallas-hint"><?php esc_html_e( 'Ha megadod, erre a számlaszámra utaljuk vissza az összeget.', 'elallas-for-woo' ); ?></span>
	</p>

	<p class="elallas-field">
		<label for="elallas-note"><?php esc_html_e( 'Megjegyzés (opcionális)', 'elallas-for-woo' ); ?></label>
		<textarea id="elallas-note" name="customer_note" rows="3"></textarea>
	</p>

	<p class="elallas-consent">
		<label><input type="checkbox" name="consent_data" value="1" required /> <?php esc_html_e( 'Kijelentem, hogy a megadott adatok valósak.', 'elallas-for-woo' ); ?></label>
	</p>
	<p class="elallas-consent">
		<label><input type="checkbox" name="consent_intent" value="1" required /> <?php esc_html_e( 'Tudomásul veszem, hogy a nyilatkozat beküldésével elállási igényt jelentek be.', 'elallas-for-woo' ); ?></label>
	</p>
	<p class="elallas-consent">
		<label><input type="checkbox" name="consent_processing" value="1" required /> <?php esc_html_e( 'Elfogadom, hogy a webshop a nyilatkozat feldolgozásához kezelje az itt megadott adatokat.', 'elallas-for-woo' ); ?></label>
	</p>

	<p class="elallas-actions">
		<button type="submit" class="button elallas-button elallas-confirm"><?php echo esc_html( $elallas_confirm_label ); ?></button>
	</p>
</form>

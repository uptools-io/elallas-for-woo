<?php
/**
 * Step 1: order identification form.
 *
 * @package LightweightPlugins\Elallas
 *
 * @var string             $form_action   Form action URL.
 * @var string             $nonce_field   Nonce field markup.
 * @var string             $honeypot      Honeypot field markup.
 * @var string             $error         Optional error message.
 * @var string             $prefill_order Pre-filled order number (from ?order= or selection).
 * @var string             $prefill_email Pre-filled email (logged-in user).
 * @var array<int,array>   $user_orders   Logged-in user's eligible orders (number, date).
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$elallas_error    = isset( $error ) ? (string) $error : '';
$elallas_p_order  = isset( $prefill_order ) ? (string) $prefill_order : '';
$elallas_p_email  = isset( $prefill_email ) ? (string) $prefill_email : '';
$elallas_orders   = ( isset( $user_orders ) && is_array( $user_orders ) ) ? $user_orders : [];
?>
<div class="elallas-intro">
	<h2><?php esc_html_e( 'Online elállási nyilatkozat', 'elallas-for-woo' ); ?></h2>
	<p><?php esc_html_e( 'Az alábbi űrlapon online bejelentheti elállási szándékát egy korábbi rendeléssel kapcsolatban. A rendelés azonosításához adja meg a rendelési számát és a rendeléshez tartozó e-mail címet.', 'elallas-for-woo' ); ?></p>
</div>

<?php if ( '' !== $elallas_error ) : ?>
	<div class="elallas-notice elallas-notice-error"><?php echo esc_html( $elallas_error ); ?></div>
<?php endif; ?>

<form class="elallas-step-form" method="post" action="<?php echo esc_url( $form_action ); ?>">
	<?php
	echo $nonce_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $honeypot;    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
	<input type="hidden" name="elallas_step" value="identify" />

	<p class="elallas-field">
		<label for="elallas-order-number"><?php esc_html_e( 'Rendelési szám', 'elallas-for-woo' ); ?> <span class="required">*</span></label>
		<?php if ( ! empty( $elallas_orders ) ) : ?>
			<select id="elallas-order-number" name="order_number" required>
				<option value=""><?php esc_html_e( '— Válassz a rendeléseid közül —', 'elallas-for-woo' ); ?></option>
				<?php foreach ( $elallas_orders as $elallas_o ) : ?>
					<option value="<?php echo esc_attr( (string) $elallas_o['number'] ); ?>" <?php selected( $elallas_p_order, (string) $elallas_o['number'] ); ?>>
						#<?php echo esc_html( (string) $elallas_o['number'] ); ?><?php echo '' !== $elallas_o['date'] ? ' — ' . esc_html( (string) $elallas_o['date'] ) : ''; ?>
					</option>
				<?php endforeach; ?>
			</select>
		<?php else : ?>
			<input type="text" id="elallas-order-number" name="order_number" required value="<?php echo esc_attr( $elallas_p_order ); ?>" autocomplete="off" />
		<?php endif; ?>
	</p>

	<p class="elallas-field">
		<label for="elallas-email"><?php esc_html_e( 'E-mail cím', 'elallas-for-woo' ); ?> <span class="required">*</span></label>
		<input type="email" id="elallas-email" name="email" required value="<?php echo esc_attr( $elallas_p_email ); ?>" autocomplete="off" />
	</p>

	<p class="elallas-actions">
		<button type="submit" class="button elallas-button"><?php esc_html_e( 'Rendelés keresése', 'elallas-for-woo' ); ?></button>
	</p>
</form>

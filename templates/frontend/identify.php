<?php
/**
 * Step 1: order identification form.
 *
 * @package LightweightPlugins\Elallas
 *
 * @var string $form_action Form action URL.
 * @var string $nonce_field Nonce field markup.
 * @var string $honeypot    Honeypot field markup.
 * @var string $error       Optional error message.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$elallas_error = isset( $error ) ? (string) $error : '';
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
		<input type="text" id="elallas-order-number" name="order_number" required value="" autocomplete="off" />
	</p>

	<p class="elallas-field">
		<label for="elallas-email"><?php esc_html_e( 'E-mail cím', 'elallas-for-woo' ); ?> <span class="required">*</span></label>
		<input type="email" id="elallas-email" name="email" required value="" autocomplete="off" />
	</p>

	<p class="elallas-actions">
		<button type="submit" class="button elallas-button"><?php esc_html_e( 'Rendelés keresése', 'elallas-for-woo' ); ?></button>
	</p>
</form>

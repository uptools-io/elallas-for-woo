<?php
/**
 * Neutral "not found / not eligible" view.
 *
 * Never reveals whether the order number or the email was wrong.
 *
 * @package LightweightPlugins\Elallas
 *
 * @var string $message     Neutral message.
 * @var string $form_action Form action URL.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$elallas_message = isset( $message ) ? (string) $message : '';
?>
<div class="elallas-notice elallas-notice-warning">
	<?php echo esc_html( $elallas_message ); ?>
</div>

<p class="elallas-actions">
	<a class="button elallas-button" href="<?php echo esc_url( isset( $form_action ) ? (string) $form_action : '' ); ?>">
		<?php esc_html_e( 'Új keresés', 'elallas-for-woo' ); ?>
	</a>
</p>

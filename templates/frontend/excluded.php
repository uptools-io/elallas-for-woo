<?php
/**
 * View: the order (or the whole selection) is excepted from withdrawal.
 *
 * Shown when every withdrawable item is excluded by a merchant exception, so
 * the customer cannot start (or complete) a withdrawal. Lists each excluded
 * product with its reason so the refusal is transparent.
 *
 * @package LightweightPlugins\Elallas
 *
 * @var array<int, array{name: string, reason: string}> $items       Excluded products with reasons.
 * @var bool                                             $emailed     Whether a rejection e-mail was sent.
 * @var string                                           $form_action Form action URL.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$elallas_items   = isset( $items ) && is_array( $items ) ? $items : [];
$elallas_emailed = ! empty( $emailed );
?>
<h2><?php esc_html_e( 'Online elállás nem kezdeményezhető', 'elallas-for-woo' ); ?></h2>

<div class="elallas-notice elallas-notice-warning">
	<p><?php esc_html_e( 'Ehhez a rendeléshez nem gyakorolható online elállás, mert az érintett termék(ek)re a jogszabály szerint nem illeti meg elállási jog:', 'elallas-for-woo' ); ?></p>
</div>

<?php if ( ! empty( $elallas_items ) ) : ?>
	<ul class="elallas-excluded-list">
		<?php foreach ( $elallas_items as $elallas_item ) : ?>
			<li>
				<strong><?php echo esc_html( (string) $elallas_item['name'] ); ?></strong>
				<?php if ( '' !== trim( (string) $elallas_item['reason'] ) ) : ?>
					<span class="elallas-excluded-reason">— <?php echo esc_html( (string) $elallas_item['reason'] ); ?></span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>

<?php if ( $elallas_emailed ) : ?>
	<p class="elallas-hint"><?php esc_html_e( 'A kérelméről és az elutasítás okáról e-mailben is tájékoztattuk.', 'elallas-for-woo' ); ?></p>
<?php endif; ?>

<p><?php esc_html_e( 'Ha úgy gondolja, hogy tévedés történt, kérjük, vegye fel a kapcsolatot ügyfélszolgálatunkkal.', 'elallas-for-woo' ); ?></p>

<p class="elallas-actions">
	<a class="button elallas-button" href="<?php echo esc_url( isset( $form_action ) ? (string) $form_action : '' ); ?>">
		<?php esc_html_e( 'Új keresés', 'elallas-for-woo' ); ?>
	</a>
</p>

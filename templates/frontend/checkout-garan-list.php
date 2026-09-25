<?php
/**
 * GARAN labels of the covered items, right before the order / pay button
 * (classic + block checkout, order-pay page, classic cart).
 *
 * Theme override: yourtheme/elallas-for-woo/frontend/checkout-garan-list.php.
 * Every row must keep its product name and label markup.
 *
 * @package LightweightPlugins\Elallas
 *
 * @var array<int, array{key: string, label: string}> $rows     Rows (label = GaranRenderer HTML, includes the product name).
 * @var string                                        $key_attr data-elallas-cart-key | data-elallas-item-id.
 * @var string                                        $context  checkout|slot|orderpay|cart.
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div class="elallas-garan-checkout elallas-garan-checkout--<?php echo esc_attr( $context ); ?>" data-elallas-cart-list>
	<p class="elallas-garan-checkout__intro"><?php esc_html_e( 'A következő termékekre a gyártó tartóssági jótállást vállal:', 'elallas-for-woo' ); ?></p>
	<ul class="elallas-garan-checkout__list">
		<?php foreach ( $rows as $elallas_row ) : ?>
			<li class="elallas-garan-checkout__item" <?php echo esc_attr( $key_attr ); ?>="<?php echo esc_attr( $elallas_row['key'] ); ?>">
				<?php echo $elallas_row['label']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- GaranRenderer output (escaped template + verified official SVG). ?>
			</li>
		<?php endforeach; ?>
	</ul>
</div>

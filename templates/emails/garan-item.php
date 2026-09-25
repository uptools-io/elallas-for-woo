<?php
/**
 * GARAN line under an order item (HTML e-mail).
 *
 * Theme override: yourtheme/elallas-for-woo/emails/garan-item.php.
 *
 * @package LightweightPlugins\Elallas
 *
 * @var string $text            GARAN text line.
 * @var string $garan_url       Your Europe GARAN URL.
 * @var string $garan_url_label Visible link text.
 * @var string $product_url     Product page URL ('' when the product is gone).
 * @var string $image_url       Filled label PNG ('' = text only; never an unfilled image).
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div class="elallas-garan-email" style="margin:8px 0 0;padding:8px 10px;border-left:3px solid #034ea2;background:#f5f8fc;font-size:13px;line-height:1.5;">
	<?php if ( '' !== $image_url ) : ?>
		<img src="<?php echo esc_url( $image_url ); ?>" width="270" alt="<?php echo esc_attr( 'EU GARAN – ' . $text ); ?>" style="display:block;width:270px;max-width:100%;height:auto;margin:0 0 6px;border:0;" />
	<?php endif; ?>
	<strong>EU GARAN</strong> – <?php echo esc_html( $text ); ?>.
	<a href="<?php echo esc_url( $garan_url ); ?>"><?php echo esc_html( $garan_url_label ); ?></a>
	<?php if ( '' !== $product_url ) : ?>
		· <a href="<?php echo esc_url( $product_url ); ?>"><?php esc_html_e( 'A hivatalos címke a termékoldalon', 'elallas-for-woo' ); ?></a>
	<?php endif; ?>
</div>

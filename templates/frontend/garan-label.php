<?php
/**
 * GARAN durability label.
 *
 * Theme override: yourtheme/elallas-for-woo/frontend/garan-label.php. The
 * official graphic ($nested_svg / $full_svg), the text line and the Your
 * Europe link must be kept unchanged: the label is a mandatory, unmodifiable
 * EU graphic.
 *
 * @package LightweightPlugins\Elallas
 *
 * @var string $mode            nested|full.
 * @var string $prefix          Unique instance prefix.
 * @var string $nested_svg      Filled nested SVG (decorative, aria-hidden).
 * @var string $full_svg        Filled full SVG ('' when shared from another row).
 * @var bool   $lazy            Full label inside <template>, cloned on first open.
 * @var string $template_ref    Shared template key ('' = own template).
 * @var string $text            Accessible text line.
 * @var string $product_name    Product name ('' on the product page).
 * @var int    $product_id      Product id.
 * @var string $garan_url       Your Europe GARAN URL.
 * @var string $garan_url_label Visible link text.
 * @var bool   $available       Whether the graphic can be shown.
 * @var bool   $hidden          Initially hidden (variable product: shown for a covered variation).
 * @var string $default_json    Variable product: parent payload (JSON, 'null' = none), '' otherwise.
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- $nested_svg / $full_svg are built by GaranSvg::fill() from the bundled, checksum-verified official file; merchant values are inserted as DOM text nodes (XML-escaped). wp_kses_post() would strip the <svg>.
?>
<div class="elallas-garan elallas-garan--<?php echo esc_attr( $mode ); ?>" data-elallas-garan data-product-id="<?php echo esc_attr( (string) $product_id ); ?>"<?php echo '' !== $default_json ? ' data-elallas-garan-default="' . esc_attr( $default_json ) . '"' : ''; ?><?php echo $hidden ? ' hidden' : ''; ?>>
	<?php if ( $available && 'nested' === $mode ) : ?>
		<button type="button" class="elallas-garan__toggle" aria-expanded="false" aria-controls="<?php echo esc_attr( $prefix . '-full' ); ?>">
			<?php echo $nested_svg; ?>
			<span class="screen-reader-text" data-elallas-garan-open><?php echo esc_html( $text . ' – ' . __( 'a teljes címke megnyitása', 'elallas-for-woo' ) ); ?></span>
		</button>
		<div id="<?php echo esc_attr( $prefix . '-full' ); ?>" class="elallas-garan__full" role="region" aria-label="<?php echo esc_attr( $text ); ?>" hidden<?php echo '' !== $template_ref ? ' data-template-ref="' . esc_attr( $template_ref ) . '"' : ''; ?>>
			<div class="elallas-garan__scroller" tabindex="0">
				<?php if ( $lazy && '' !== $full_svg ) : ?>
					<template data-elallas-garan-full="<?php echo esc_attr( '' !== $template_ref ? $template_ref : $prefix ); ?>"><?php echo $full_svg; ?></template>
				<?php elseif ( ! $lazy ) : ?>
					<?php echo $full_svg; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php if ( ! $lazy ) : ?>
			<noscript><style>#<?php echo esc_attr( $prefix . '-full' ); ?>{display:block}</style></noscript>
		<?php endif; ?>
	<?php elseif ( $available ) : ?>
		<div class="elallas-garan__scroller" tabindex="0"><?php echo $full_svg; ?></div>
	<?php endif; ?>
	<p class="elallas-garan__text">
		<?php if ( '' !== $product_name ) : ?>
			<strong class="elallas-garan__product"><?php echo esc_html( $product_name ); ?>:</strong>
		<?php endif; ?>
		<span data-elallas-garan-text><?php echo esc_html( $text ); ?></span> –
		<a href="<?php echo esc_url( $garan_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $garan_url_label ); ?><span class="screen-reader-text"> <?php esc_html_e( '(új lapon nyílik)', 'elallas-for-woo' ); ?></span></a>
	</p>
</div>

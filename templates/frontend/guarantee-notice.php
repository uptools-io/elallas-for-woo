<?php
/**
 * Harmonised legal-guarantee notice (label + official EU notice + link).
 *
 * Theme override: yourtheme/elallas-for-woo/frontend/guarantee-notice.php.
 * The official image and the link to the QR-code target are mandatory: an
 * override may restyle the frame, but must print $image_url and $link_url
 * unchanged.
 *
 * @package LightweightPlugins\Elallas
 *
 * @var string $uid          Unique element id.
 * @var string $context      Output context (CSS modifier).
 * @var string $mode         toggle|inline.
 * @var string $label        Label (plain text).
 * @var string $code         Notice language code.
 * @var string $image_url    Official notice image URL.
 * @var int    $image_width  Intrinsic width.
 * @var int    $image_height Intrinsic height.
 * @var string $alt          Image alternative text.
 * @var string $link_url     QR-code target URL.
 * @var string $link_label   Visible short link text.
 * @var string $page_url     Standalone notice page URL ('' when none).
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$elallas_panel_id = $uid . '-panel';
$elallas_classes  = 'elallas-notice elallas-notice--' . $context . ' elallas-notice--' . $mode;

ob_start();
?>
<div class="elallas-notice__scroller" tabindex="0" role="group" aria-label="<?php echo esc_attr( $alt ); ?>">
	<a class="elallas-notice__zoom" href="<?php echo esc_url( $image_url ); ?>" target="_blank" rel="noopener">
		<img class="elallas-notice__image" src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $alt ); ?>" width="<?php echo (int) $image_width; ?>" height="<?php echo (int) $image_height; ?>" loading="lazy" decoding="async" />
		<span class="screen-reader-text"><?php esc_html_e( '(új lapon nyílik)', 'elallas-for-woo' ); ?></span>
	</a>
</div>
<p class="elallas-notice__link">
	<a href="<?php echo esc_url( $link_url ); ?>" target="_blank" rel="noopener" hreflang="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $link_label ); ?><span class="screen-reader-text"> <?php esc_html_e( '(új lapon nyílik)', 'elallas-for-woo' ); ?></span></a>
	<?php if ( '' !== $page_url ) : ?>
		<span aria-hidden="true">&middot;</span>
		<a href="<?php echo esc_url( $page_url ); ?>"><?php esc_html_e( 'Megnyitás külön oldalon', 'elallas-for-woo' ); ?></a>
	<?php endif; ?>
</p>
<?php
$elallas_body = (string) ob_get_clean();

if ( 'inline' === $mode ) :
	?>
<div class="<?php echo esc_attr( $elallas_classes ); ?>" id="<?php echo esc_attr( $uid ); ?>">
	<p class="elallas-notice__title"><?php echo esc_html( $label ); ?></p>
	<div class="elallas-notice__panel" id="<?php echo esc_attr( $elallas_panel_id ); ?>">
		<?php echo $elallas_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above. ?>
	</div>
</div>
<?php else : ?>
<details class="<?php echo esc_attr( $elallas_classes ); ?>" id="<?php echo esc_attr( $uid ); ?>" data-elallas-notice data-elallas-disclosure>
	<summary class="elallas-notice__label" aria-expanded="false" aria-controls="<?php echo esc_attr( $elallas_panel_id ); ?>"><?php echo esc_html( $label ); ?></summary>
	<div class="elallas-notice__panel" id="<?php echo esc_attr( $elallas_panel_id ); ?>" role="region" aria-label="<?php echo esc_attr( $label ); ?>">
		<?php echo $elallas_body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above. ?>
	</div>
</details>
	<?php
endif;

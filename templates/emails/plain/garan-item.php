<?php
/**
 * GARAN line under an order item (plain-text e-mail).
 *
 * @package LightweightPlugins\Elallas
 *
 * @var string $text            GARAN text line.
 * @var string $garan_url       Your Europe GARAN URL.
 * @var string $garan_url_label Visible link text.
 * @var string $product_url     Product page URL.
 * @var string $image_url       Unused in plain text.
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

echo "\n" . esc_html( 'EU GARAN – ' . $text ) . "\n";
echo esc_html( $garan_url ) . "\n";
if ( '' !== $product_url ) {
	echo esc_html__( 'A hivatalos címke a termékoldalon:', 'elallas-for-woo' ) . ' ' . esc_url_raw( $product_url ) . "\n";
}

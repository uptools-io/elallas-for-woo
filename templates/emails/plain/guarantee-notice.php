<?php
/**
 * Harmonised legal-guarantee notice in customer order e-mails (plain text).
 *
 * Theme override: yourtheme/elallas-for-woo/emails/plain/guarantee-notice.php.
 *
 * @package LightweightPlugins\Elallas
 *
 * @var string $label      Label (plain text).
 * @var string $png_url    Official colour PNG URL ('' when none is published).
 * @var bool   $attached   Whether the PNG is attached to this e-mail.
 * @var string $link_url   QR-code target URL.
 * @var string $link_label Visible short link text.
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

echo "\n" . esc_html( wp_strip_all_tags( $label ) ) . "\n";
echo esc_html( $link_label ) . ': ' . esc_url_raw( $link_url ) . "\n";

if ( '' !== $png_url ) {
	/* translators: %s: URL of the official notice image. */
	echo esc_html( sprintf( __( 'Teljes méretben: %s', 'elallas-for-woo' ), esc_url_raw( $png_url ) ) ) . "\n";
}

if ( $attached ) {
	echo esc_html__( 'A tájékoztatót mellékletben csatoltuk.', 'elallas-for-woo' ) . "\n";
}

echo "\n";

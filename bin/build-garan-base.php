<?php
/**
 * Dev-only: builds the raster bases of the GARAN e-mail image.
 *
 * GaranSvg::blank() empties the three editable fields of the official label
 * (nothing else changes); rsvg-convert (brew install librsvg) rasterises it at
 * 4x: assets/garan/garan-base-colour@4x.png (1077 x 1134). GaranRaster draws
 * the merchant values onto it with the bundled Inter 3.19 TTFs.
 *
 * Usage: php bin/build-garan-base.php
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

require dirname( __DIR__ ) . '/vendor/autoload.php';

use LightweightPlugins\Elallas\Compliance\GaranSvg;

$root  = dirname( __DIR__ );
$blank = GaranSvg::blank( (string) file_get_contents( $root . '/assets/garan/garan-label-colour.svg' ) );
if ( '' === $blank ) {
	fwrite( STDERR, "blank() failed\n" );
	exit( 1 );
}

$tmp = tempnam( sys_get_temp_dir(), 'garan' ) . '.svg';
file_put_contents( $tmp, $blank );
$out = $root . '/assets/garan/garan-base-colour@4x.png';
passthru( 'rsvg-convert -w 1077 -h 1134 -b white -o ' . escapeshellarg( $out ) . ' ' . escapeshellarg( $tmp ), $code );
unlink( $tmp );

if ( 0 !== $code ) {
	fwrite( STDERR, "rsvg-convert failed\n" );
	exit( 1 );
}

$size = getimagesize( $out );
printf( "%s %dx%d %d bytes\n", $out, $size[0] ?? 0, $size[1] ?? 0, filesize( $out ) );

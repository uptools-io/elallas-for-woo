<?php
/**
 * Filled GARAN label PNG for e-mails (GD + FreeType).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

use LightweightPlugins\Elallas\Support\Logger;

/**
 * E-mail clients cannot render the inline SVG with Inter, so customer e-mails
 * get a PNG: the build-time base (official label with the three editable
 * fields empty, bin/build-garan-base.php) plus the merchant's validated values
 * drawn with the bundled Inter 3.19 at the SVG positions (x4). Cached in
 * uploads/elallas-garan/ by GaranData::hash( ASSET_VERSION ). Fail-soft:
 * returns '' (text-only e-mail) whenever GD FreeType or a file is missing.
 */
final class GaranRaster {

	public const BASE      = 'assets/garan/garan-base-colour@4x.png';
	public const REGULAR   = 'assets/fonts/inter/Inter-Regular.ttf';
	public const BOLD      = 'assets/fonts/inter/Inter-ExtraBold.ttf';
	public const DIR       = 'elallas-garan';
	public const OUT_WIDTH = 540; // Shown at 270 CSS px (2x for sharp text).

	private const SCALE = 4.0;

	/**
	 * GD reports font sizes in points at 96 dpi: px = pt * 96 / 72.
	 */
	private const PT_PER_PX = 0.75;

	/**
	 * Whether a PNG can be produced on this host.
	 *
	 * @param string $root Plugin root (with trailing slash).
	 * @return bool
	 */
	public static function can_render( string $root = '' ): bool {
		$root = '' !== $root ? $root : ELALLAS_FOR_WOO_PATH;
		if ( ! function_exists( 'imagecreatefrompng' ) || ! function_exists( 'imagettftext' ) || ! function_exists( 'gd_info' ) ) {
			return false;
		}
		$info = gd_info();
		if ( empty( $info['FreeType Support'] ) || ! is_readable( $root . self::BASE ) || ! is_readable( $root . self::REGULAR ) || ! is_readable( $root . self::BOLD ) ) {
			return false;
		}

		// Some builds report FreeType support but cannot render (e.g. php-wasm): probe.
		return false !== @imagettfbbox( 9.0, 0, $root . self::REGULAR, 'A' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Capability probe.
	}

	/**
	 * Public URL of the filled PNG ('' when unavailable). Generates it once.
	 *
	 * @param GaranData $data Data.
	 * @return string
	 */
	public static function png_url( GaranData $data ): string {
		if ( ! self::can_render() || ! function_exists( 'wp_upload_dir' ) ) {
			return '';
		}

		$uploads = wp_upload_dir( null, false );
		if ( ! empty( $uploads['error'] ) ) {
			return '';
		}

		$name = 'garan-colour-' . $data->hash( OfficialAssets::ASSET_VERSION ) . '.png';
		$dir  = trailingslashit( $uploads['basedir'] ) . self::DIR;
		$path = $dir . '/' . $name;

		if ( ! is_file( $path ) && ! self::write( $data, $dir, $path ) ) {
			return '';
		}

		return trailingslashit( $uploads['baseurl'] ) . self::DIR . '/' . $name;
	}

	/**
	 * Draw the values onto the 4x base (pure GD; used by tests).
	 *
	 * @param GaranData $data Data.
	 * @param string    $root Plugin root (with trailing slash).
	 * @return \GdImage|false
	 */
	public static function draw( GaranData $data, string $root ) {
		$im = @imagecreatefrompng( $root . self::BASE ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Fail-soft; a missing base means text-only e-mail.
		if ( false === $im ) {
			return false;
		}
		imagealphablending( $im, true );
		$ink = (int) imagecolorallocate( $im, 0x23, 0x1f, 0x20 );

		// Positions: the SVG text origins (translate()) x4; sizes from .cls-5 / .cls-3.
		// Never return a label with an empty field: any failed text means no image.
		$ok = self::text( $im, $data->brand(), $root . self::REGULAR, 9.0, 6.32, 74.52, 0.0, $ink )
			&& self::text( $im, $data->model(), $root . self::REGULAR, 9.0, 196.75, 74.52, 0.0, $ink )
			&& self::text( $im, $data->years_label(), $root . self::BOLD, 80.0, 5.07, 150.57, -0.03, $ink );

		return $ok ? $im : false;
	}

	/**
	 * Draw one text, glyph by glyph when letter spacing applies.
	 *
	 * @param \GdImage $im      Image.
	 * @param string   $text    Text.
	 * @param string   $font    TTF path.
	 * @param float    $px      Font size in SVG units.
	 * @param float    $x       Origin x (SVG units).
	 * @param float    $y       Baseline y (SVG units).
	 * @param float    $spacing Letter spacing (em).
	 * @param int      $ink     Colour.
	 * @return bool False when FreeType failed.
	 */
	private static function text( $im, string $text, string $font, float $px, float $x, float $y, float $spacing, int $ink ): bool {
		$size = $px * self::SCALE * self::PT_PER_PX;
		$left = $x * self::SCALE;
		$base = (int) round( $y * self::SCALE );

		if ( 0.0 === $spacing ) {
			return false !== @imagettftext( $im, $size, 0, (int) round( $left ), $base, $ink, $font, $text ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Fail-soft, checked.
		}

		$chars = preg_split( '//u', $text, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $chars ) || [] === $chars ) {
			return false;
		}
		foreach ( $chars as $char ) {
			if ( false === @imagettftext( $im, $size, 0, (int) round( $left ), $base, $ink, $font, $char ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Fail-soft, checked.
				return false;
			}
			$advance = GaranMetrics::width( $char, GaranMetrics::WEIGHT_EXTRABOLD, $px * self::SCALE );
			$left   += $advance + $spacing * $px * self::SCALE;
		}

		return true;
	}

	/**
	 * Resample to OUT_WIDTH (truecolor, keeps the official colours).
	 *
	 * @param \GdImage $im Source.
	 * @return \GdImage|false
	 */
	public static function downscale( $im ) {
		$w     = imagesx( $im );
		$h     = imagesy( $im );
		$out_h = (int) round( $h * self::OUT_WIDTH / $w );
		$small = imagecreatetruecolor( self::OUT_WIDTH, $out_h );
		if ( false === $small ) {
			return false;
		}
		imagecopyresampled( $small, $im, 0, 0, 0, 0, self::OUT_WIDTH, $out_h, $w, $h );

		return $small;
	}

	/**
	 * Render, downscale and store the PNG.
	 *
	 * @param GaranData $data Data.
	 * @param string    $dir  Target directory.
	 * @param string    $path Target file.
	 * @return bool
	 */
	private static function write( GaranData $data, string $dir, string $path ): bool {
		if ( ! wp_mkdir_p( $dir ) ) {
			return false;
		}
		if ( ! is_file( $dir . '/index.html' ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Directory index guard, plugin-owned directory.
			file_put_contents( $dir . '/index.html', '' );
		}

		$im = self::draw( $data, ELALLAS_FOR_WOO_PATH );
		if ( false === $im ) {
			Logger::debug( 'GARAN e-mail image: base could not be loaded.' );
			return false;
		}

		$small = self::downscale( $im );
		if ( false === $small ) {
			return false;
		}

		$ok = self::store_png( $small, $path );
		if ( ! $ok ) {
			Logger::debug( 'GARAN e-mail image could not be written.' );
		}

		return $ok;
	}

	/**
	 * Write a PNG atomically: a temp file in the same directory, then rename()
	 * onto the final path, so a partial file is never served or cached.
	 *
	 * @param \GdImage|resource $im   Image.
	 * @param string            $path Final file.
	 * @return bool
	 */
	public static function store_png( $im, string $path ): bool {
		$dir = dirname( $path );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- Plugin-owned uploads directory, no WP_Filesystem for GD output.
		if ( ! is_dir( $dir ) || ! is_writable( $dir ) ) {
			return false;
		}

		$tmp = $dir . '/.' . basename( $path ) . '.' . bin2hex( random_bytes( 6 ) ) . '.tmp';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Atomic replace within the same directory.
		if ( imagepng( $im, $tmp, 6 ) && rename( $tmp, $path ) ) {
			return true;
		}

		if ( is_file( $tmp ) ) {
			wp_delete_file( $tmp );
		}

		return false;
	}
}

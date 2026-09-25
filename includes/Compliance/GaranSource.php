<?php
/**
 * Loads and verifies the bundled official GARAN SVGs.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

use LightweightPlugins\Elallas\Support\Logger;

/**
 * Reads each official label file at most once per request and checks its
 * sha256 against OfficialAssets::SVG_SHA256. A modified file is never shown:
 * the source becomes '' (the renderer prints only the text line and link),
 * the error is logged (at most daily) and flagged for the admin notice.
 */
final class GaranSource {

	/**
	 * Transient flag read by Admin\ComplianceNotice (value: time of detection).
	 */
	public const INTEGRITY_TRANSIENT = 'lw_elallas_garan_integrity_error';

	/**
	 * Verified sources per variant ('' = missing or modified).
	 *
	 * @var array<string, string>
	 */
	private static array $sources = [];

	/**
	 * Verified official SVG source of a variant.
	 *
	 * @param string $variant `colour` or `nested`.
	 * @return string '' when missing or modified.
	 */
	public static function get( string $variant ): string {
		if ( ! isset( self::$sources[ $variant ] ) ) {
			self::$sources[ $variant ] = self::load( $variant );
		}

		return self::$sources[ $variant ];
	}

	/**
	 * Whether both official files are present and unmodified.
	 *
	 * @return bool
	 */
	public static function integrity_ok(): bool {
		return '' !== self::get( 'colour' ) && '' !== self::get( 'nested' );
	}

	/**
	 * Read and verify one file.
	 *
	 * @param string $variant Variant.
	 * @return string
	 */
	private static function load( string $variant ): string {
		$relpath = OfficialAssets::garan_svg_relpath( $variant );
		if ( '' === $relpath ) {
			return '';
		}

		$path     = ELALLAS_FOR_WOO_PATH . $relpath;
		$expected = OfficialAssets::SVG_SHA256[ basename( $relpath ) ] ?? '';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local bundled file, constant path.
		$svg = is_readable( $path ) ? (string) file_get_contents( $path ) : '';

		if ( '' !== $svg && '' !== $expected && hash_equals( $expected, hash( 'sha256', $svg ) ) ) {
			return $svg;
		}

		self::report( $relpath );

		return '';
	}

	/**
	 * Log (at most once a day) and flag an integrity failure.
	 *
	 * @param string $relpath File.
	 * @return void
	 */
	private static function report( string $relpath ): void {
		if ( false !== get_transient( self::INTEGRITY_TRANSIENT ) ) {
			return;
		}

		set_transient( self::INTEGRITY_TRANSIENT, time(), DAY_IN_SECONDS );
		Logger::error(
			'Official GARAN label file is missing or modified; the label is not rendered.',
			[ 'file' => $relpath ]
		);
	}
}

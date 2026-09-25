<?php
/**
 * Guards the notice image / link filters (pure).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

/**
 * The official notice and its link may not be replaced (A7): a filtered link
 * must stay on europa.eu over https, a filtered image URL may only move the
 * official file to another host (CDN) under the same basename.
 *
 * Uses no WordPress function, so it is unit-testable.
 */
final class NoticeUrlPolicy {

	/**
	 * Accept a filtered link only if it is https on europa.eu or a subdomain.
	 *
	 * @param mixed  $candidate Filtered value.
	 * @param string $original  Official link.
	 * @return string
	 */
	public static function link( $candidate, string $original ): string {
		if ( ! is_string( $candidate ) || $candidate === $original ) {
			return $original;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Pure class, WordPress-free by design.
		$parts  = parse_url( $candidate );
		$scheme = is_array( $parts ) ? strtolower( (string) ( $parts['scheme'] ?? '' ) ) : '';
		$host   = is_array( $parts ) ? strtolower( (string) ( $parts['host'] ?? '' ) ) : '';

		if ( 'https' !== $scheme || '' === $host ) {
			return $original;
		}

		if ( 'europa.eu' === $host || str_ends_with( $host, '.europa.eu' ) ) {
			return $candidate;
		}

		return $original;
	}

	/**
	 * Accept a filtered image URL only if it is http(s) and keeps the official basename.
	 *
	 * @param mixed  $candidate Filtered value.
	 * @param string $original  Official URL.
	 * @return string
	 */
	public static function image( $candidate, string $original ): string {
		if ( ! is_string( $candidate ) || $candidate === $original ) {
			return $original;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Pure class, WordPress-free by design.
		$parts  = parse_url( $candidate );
		$scheme = is_array( $parts ) ? strtolower( (string) ( $parts['scheme'] ?? '' ) ) : '';
		$path   = is_array( $parts ) ? (string) ( $parts['path'] ?? '' ) : '';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Pure class, WordPress-free by design.
		$official = basename( (string) parse_url( $original, PHP_URL_PATH ) );

		if ( ! in_array( $scheme, [ 'http', 'https' ], true ) || '' === $official ) {
			return $original;
		}

		return basename( $path ) === $official ? $candidate : $original;
	}
}

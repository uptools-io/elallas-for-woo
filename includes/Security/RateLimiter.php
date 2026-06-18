<?php
/**
 * Simple transient-based rate limiter.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Security;

/**
 * Throttles repeated actions per client IP to deter brute-force order probing.
 */
final class RateLimiter {

	/**
	 * Whether the current client has exceeded the allowed attempts.
	 *
	 * Increments the counter as a side effect.
	 *
	 * @param string $action Action slug (e.g. "identify").
	 * @param int    $max    Maximum attempts within the window.
	 * @param int    $window Window length in seconds.
	 * @return bool True if the limit is exceeded.
	 */
	public static function too_many( string $action, int $max = 10, int $window = 600 ): bool {
		$key   = self::key( $action );
		$count = (int) get_transient( $key );

		if ( $count >= $max ) {
			return true;
		}

		set_transient( $key, $count + 1, $window );

		return false;
	}

	/**
	 * Whether a global (cross-IP) counter has exceeded its limit.
	 *
	 * Used for per-order throttling so an IP pool cannot brute-force the billing
	 * email against a single order. Increments the counter as a side effect.
	 *
	 * @param string $action Action slug (e.g. "order_123").
	 * @param int    $max    Maximum attempts within the window.
	 * @param int    $window Window length in seconds.
	 * @return bool True if the limit is exceeded.
	 */
	public static function too_many_global( string $action, int $max = 20, int $window = 3600 ): bool {
		$key   = 'lw_elallas_rlg_' . md5( $action );
		$count = (int) get_transient( $key );

		if ( $count >= $max ) {
			return true;
		}

		set_transient( $key, $count + 1, $window );

		return false;
	}

	/**
	 * Transient key for the current client and action.
	 *
	 * @param string $action Action slug.
	 * @return string
	 */
	private static function key( string $action ): string {
		return 'lw_elallas_rl_' . md5( $action . '|' . self::client_ip() );
	}

	/**
	 * Best-effort client IP.
	 *
	 * @return string
	 */
	public static function client_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		return '' !== $ip ? $ip : '0.0.0.0';
	}
}

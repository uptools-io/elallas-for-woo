<?php
/**
 * Honeypot + fill-time spam controls.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Security;

/**
 * Defense-in-depth anti-automation for the public form.
 *
 * Uses a per-day rotating honeypot field name (so the static open-source name
 * can't be hardcoded by a bot) plus a signed minimum form-fill-time check.
 */
final class Honeypot {

	/**
	 * Signed timestamp field name.
	 */
	private const TS_FIELD = 'elallas_ts';

	/**
	 * Minimum seconds between render and submit for a human.
	 */
	private const MIN_SECONDS = 2;

	/**
	 * The rotating honeypot field name (changes daily, not guessable from source).
	 *
	 * @return string
	 */
	public static function field_name(): string {
		return 'elallas_hp_' . substr( hash_hmac( 'sha256', gmdate( 'Y-m-d' ), wp_salt( 'auth' ) ), 0, 12 );
	}

	/**
	 * Render the honeypot + signed timestamp markup.
	 *
	 * @return string
	 */
	public static function field(): string {
		$ts = (string) time();

		return sprintf(
			'<div style="position:absolute;left:-9999px;" aria-hidden="true"><label>%1$s<input type="text" name="%2$s" value="" tabindex="-1" autocomplete="off" /></label><input type="hidden" name="%3$s" value="%4$s" /></div>',
			esc_html__( 'Hagyd üresen ezt a mezőt', 'elallas-for-woo' ),
			esc_attr( self::field_name() ),
			esc_attr( self::TS_FIELD ),
			esc_attr( $ts . '.' . self::ts_sig( $ts ) )
		);
	}

	/**
	 * Whether the submission looks like a bot.
	 *
	 * @param array<string, mixed> $post Raw POST data.
	 * @return bool
	 */
	public static function is_bot( array $post ): bool {
		if ( ! empty( $post[ self::field_name() ] ) ) {
			return true;
		}

		$ts_value = isset( $post[ self::TS_FIELD ] ) ? (string) $post[ self::TS_FIELD ] : '';

		return ! self::fill_time_ok( $ts_value );
	}

	/**
	 * Validate the signed timestamp and minimum fill time.
	 *
	 * @param string $value The "timestamp.signature" value.
	 * @return bool
	 */
	private static function fill_time_ok( string $value ): bool {
		$parts = explode( '.', $value, 2 );

		if ( 2 !== count( $parts ) ) {
			return false;
		}

		[ $ts, $sig ] = $parts;

		if ( ! hash_equals( self::ts_sig( $ts ), (string) $sig ) ) {
			return false;
		}

		$elapsed = time() - (int) $ts;

		return $elapsed >= self::MIN_SECONDS && $elapsed <= 86400;
	}

	/**
	 * HMAC signature for a timestamp.
	 *
	 * @param string $ts Timestamp.
	 * @return string
	 */
	private static function ts_sig( string $ts ): string {
		return hash_hmac( 'sha256', $ts, wp_salt( 'auth' ) );
	}
}

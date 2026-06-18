<?php
/**
 * PII encryption and hashing helpers.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Security;

/**
 * Hashes and (reversibly) encrypts personal data using WordPress salts.
 */
final class Encryption {

	/**
	 * Deterministic keyed hash (for lookups, e.g. email).
	 *
	 * @param string $value Value to hash.
	 * @return string 64-char hex string.
	 */
	public static function hash( string $value ): string {
		return hash_hmac( 'sha256', strtolower( trim( $value ) ), self::key() );
	}

	/**
	 * Encrypt a value (AES-256-CBC) — returns base64(iv.cipher).
	 *
	 * @param string $value Plain value.
	 * @return string
	 */
	public static function encrypt( string $value ): string {
		if ( '' === $value || ! function_exists( 'openssl_encrypt' ) ) {
			return '';
		}

		$iv     = random_bytes( 16 );
		$cipher = openssl_encrypt( $value, 'aes-256-cbc', self::key(), OPENSSL_RAW_DATA, $iv );

		return false === $cipher ? '' : base64_encode( $iv . $cipher );
	}

	/**
	 * Decrypt a value produced by encrypt().
	 *
	 * @param string $payload Encrypted payload.
	 * @return string
	 */
	public static function decrypt( string $payload ): string {
		if ( '' === $payload || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}

		$raw = base64_decode( $payload, true );

		if ( false === $raw || strlen( $raw ) <= 16 ) {
			return '';
		}

		$iv     = substr( $raw, 0, 16 );
		$cipher = substr( $raw, 16 );
		$plain  = openssl_decrypt( $cipher, 'aes-256-cbc', self::key(), OPENSSL_RAW_DATA, $iv );

		return false === $plain ? '' : $plain;
	}

	/**
	 * Encryption/hash key derived from WordPress auth salts.
	 *
	 * @return string
	 */
	private static function key(): string {
		$salt = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'lw-elallas';
		$salt .= defined( 'AUTH_SALT' ) ? AUTH_SALT : 'fallback';

		return hash( 'sha256', $salt, true );
	}
}

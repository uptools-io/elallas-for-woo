<?php
/**
 * PII encryption and hashing helpers.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Security;

/**
 * Hashes and (reversibly) encrypts personal data.
 *
 * Keys are derived from wp_salt('auth') (which WordPress auto-generates and
 * persists when the AUTH_KEY/AUTH_SALT constants are missing), with separate
 * derived keys for the lookup HMAC and the cipher. Encryption is authenticated
 * (AES-256-GCM) so ciphertext cannot be tampered with.
 */
final class Encryption {

	/**
	 * Deterministic keyed hash (for lookups, e.g. email).
	 *
	 * @param string $value Value to hash.
	 * @return string 64-char hex string.
	 */
	public static function hash( string $value ): string {
		return hash_hmac( 'sha256', strtolower( trim( $value ) ), self::hmac_key() );
	}

	/**
	 * Encrypt a value with AES-256-GCM — returns base64( iv . tag . cipher ).
	 *
	 * @param string $value Plain value.
	 * @return string
	 */
	public static function encrypt( string $value ): string {
		if ( '' === $value || ! function_exists( 'openssl_encrypt' ) ) {
			return '';
		}

		$iv     = random_bytes( 12 );
		$tag    = '';
		$cipher = openssl_encrypt( $value, 'aes-256-gcm', self::cipher_key(), OPENSSL_RAW_DATA, $iv, $tag );

		return false === $cipher ? '' : base64_encode( $iv . $tag . $cipher ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Base64 encoding of ciphertext, not obfuscation.
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

		$raw = base64_decode( $payload, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Base64 decoding of ciphertext, not obfuscation.

		if ( false === $raw || strlen( $raw ) <= 28 ) {
			return '';
		}

		$iv     = substr( $raw, 0, 12 );
		$tag    = substr( $raw, 12, 16 );
		$cipher = substr( $raw, 28 );
		$plain  = openssl_decrypt( $cipher, 'aes-256-gcm', self::cipher_key(), OPENSSL_RAW_DATA, $iv, $tag );

		return false === $plain ? '' : $plain;
	}

	/**
	 * 32-byte raw key for the cipher.
	 *
	 * @return string
	 */
	private static function cipher_key(): string {
		return hash_hmac( 'sha256', 'elallas:cipher', wp_salt( 'auth' ), true );
	}

	/**
	 * Key for the deterministic lookup HMAC (distinct from the cipher key).
	 *
	 * @return string
	 */
	private static function hmac_key(): string {
		return hash_hmac( 'sha256', 'elallas:hmac', wp_salt( 'auth' ) );
	}
}

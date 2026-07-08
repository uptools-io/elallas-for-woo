<?php
/**
 * WooCommerce-native logger wrapper.
 *
 * Writes to the WooCommerce logging system (WooCommerce → Status → Logs) under
 * the "elallas-for-woo" source, which is separate from WP_DEBUG and much easier
 * to consult when investigating a specific case or order.
 *
 * Levels: warning/error/critical are ALWAYS written (rare, high value). The
 * verbose levels (debug/info/notice) are written only when the "Debug logging"
 * option is enabled, mirroring the convention used by WooCommerce payment
 * gateways.
 *
 * Privacy: this plugin hashes/encrypts personal data, so log context must never
 * contain PII. Callers should pass identifiers only (case_id, order_id, the
 * display order number, status, error class); a defensive filter drops common
 * PII keys just in case.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Support;

use LightweightPlugins\Elallas\Options;

/**
 * Thin, safe wrapper around wc_get_logger().
 */
final class Logger {

	/**
	 * Log source (groups the entries in the WooCommerce log viewer).
	 */
	public const SOURCE = 'elallas-for-woo';

	/**
	 * Context keys that must never be logged (PII).
	 *
	 * @var array<int, string>
	 */
	private const BLOCKED_KEYS = [
		'email',
		'billing_email',
		'customer_email',
		'ip',
		'ip_address',
		'user_agent',
		'name',
		'customer_name',
		'bank_account',
		'customer_note',
	];

	/**
	 * Log an error (always written).
	 *
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Non-PII context.
	 * @return void
	 */
	public static function error( string $message, array $context = [] ): void {
		self::write( 'error', $message, $context, true );
	}

	/**
	 * Log a warning (always written).
	 *
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Non-PII context.
	 * @return void
	 */
	public static function warning( string $message, array $context = [] ): void {
		self::write( 'warning', $message, $context, true );
	}

	/**
	 * Log a notice (only when verbose logging is enabled).
	 *
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Non-PII context.
	 * @return void
	 */
	public static function notice( string $message, array $context = [] ): void {
		self::write( 'notice', $message, $context, false );
	}

	/**
	 * Log an info message (only when verbose logging is enabled).
	 *
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Non-PII context.
	 * @return void
	 */
	public static function info( string $message, array $context = [] ): void {
		self::write( 'info', $message, $context, false );
	}

	/**
	 * Log a debug message (only when verbose logging is enabled).
	 *
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Non-PII context.
	 * @return void
	 */
	public static function debug( string $message, array $context = [] ): void {
		self::write( 'debug', $message, $context, false );
	}

	/**
	 * Whether verbose (info/debug/notice) logging is enabled.
	 *
	 * @return bool
	 */
	public static function verbose_enabled(): bool {
		return (bool) Options::get( 'logging_enabled', false );
	}

	/**
	 * Write to the WooCommerce logger.
	 *
	 * @param string               $level   WC log level (error|warning|notice|info|debug).
	 * @param string               $message Message.
	 * @param array<string, mixed> $context Non-PII context.
	 * @param bool                 $always  Whether to write regardless of the verbose toggle.
	 * @return void
	 */
	private static function write( string $level, string $message, array $context, bool $always ): void {
		if ( ! $always && ! self::verbose_enabled() ) {
			return;
		}

		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		$logger = wc_get_logger();

		if ( ! is_object( $logger ) || ! method_exists( $logger, $level ) ) {
			return;
		}

		$data           = self::scrub( $context );
		$data['source'] = self::SOURCE;

		$logger->{$level}( $message, $data );
	}

	/**
	 * Drop any accidentally-passed PII keys from the context.
	 *
	 * @param array<string, mixed> $context Context.
	 * @return array<string, mixed>
	 */
	private static function scrub( array $context ): array {
		foreach ( self::BLOCKED_KEYS as $key ) {
			unset( $context[ $key ] );
		}

		return $context;
	}
}

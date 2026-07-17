<?php
/**
 * PHPUnit bootstrap for pure-logic unit tests.
 *
 * Provides minimal WordPress function polyfills so domain/value classes load
 * without a full WordPress install.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! function_exists( '__' ) ) {
	/**
	 * i18n polyfill.
	 *
	 * @param string      $text   Text.
	 * @param string|null $domain Text domain.
	 * @return string
	 */
	function __( string $text, ?string $domain = null ): string {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Escaped i18n polyfill.
	 *
	 * @param string      $text   Text.
	 * @param string|null $domain Text domain.
	 * @return string
	 */
	function esc_html__( string $text, ?string $domain = null ): string {
		return $text;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Filter polyfill (returns the value unchanged).
	 *
	 * @param string $hook  Hook name.
	 * @param mixed  $value Value.
	 * @param mixed  ...$args Extra args.
	 * @return mixed
	 */
	function apply_filters( string $hook, mixed $value, mixed ...$args ): mixed {
		return $value;
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	/**
	 * sanitize_email polyfill: strip characters WordPress disallows in e-mails.
	 *
	 * @param string $email Raw address.
	 * @return string
	 */
	function sanitize_email( string $email ): string {
		$clean = preg_replace( '/[^a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~@-]/', '', trim( $email ) );

		return is_string( $clean ) ? $clean : '';
	}
}

if ( ! function_exists( 'is_email' ) ) {
	/**
	 * is_email polyfill: return the address if it validates, false otherwise.
	 *
	 * @param string $email Address to validate.
	 * @return string|false
	 */
	function is_email( string $email ) {
		return filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : false;
	}
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

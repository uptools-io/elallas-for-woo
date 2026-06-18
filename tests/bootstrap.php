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

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

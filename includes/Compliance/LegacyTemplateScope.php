<?php
/**
 * Tracks rendering inside the WooCommerce classic (legacy) template block.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

/**
 * On block themes WooCommerce may still render the single product through the
 * `woocommerce/legacy-template` block (the "Classic product template" block, or
 * the fallback template), which runs the classic PHP templates and hooks. While
 * that block renders, the classic `woocommerce_single_product_summary` hook is
 * the only placement that fires, so ProductPlacement must not skip it.
 *
 * Entered on `render_block_data` (fires only when the block really renders,
 * i.e. after any `pre_render_block` short-circuit), left on
 * `render_block_woocommerce/legacy-template`. A depth counter keeps nesting safe.
 */
final class LegacyTemplateScope {

	public const BLOCK = 'woocommerce/legacy-template';

	/**
	 * Current nesting depth of legacy-template renders.
	 *
	 * @var int
	 */
	private static int $depth = 0;

	/**
	 * Whether the hooks are registered.
	 *
	 * @var bool
	 */
	private static bool $registered = false;

	/**
	 * Register the tracking filters once.
	 *
	 * @return void
	 */
	public static function register(): void {
		if ( self::$registered ) {
			return;
		}
		self::$registered = true;

		add_filter( 'render_block_data', [ self::class, 'on_block_data' ], PHP_INT_MAX, 1 );
		add_filter( 'render_block_' . self::BLOCK, [ self::class, 'on_rendered' ], PHP_INT_MIN, 1 );
	}

	/**
	 * Enter the scope when a legacy-template block starts rendering.
	 *
	 * @param mixed $parsed_block Parsed block.
	 * @return mixed Unchanged.
	 */
	public static function on_block_data( $parsed_block ) {
		if ( is_array( $parsed_block ) && self::BLOCK === ( $parsed_block['blockName'] ?? null ) ) {
			self::enter();
		}

		return $parsed_block;
	}

	/**
	 * Leave the scope once the legacy-template block rendered.
	 *
	 * @param mixed $html Block HTML.
	 * @return mixed Unchanged.
	 */
	public static function on_rendered( $html ) {
		self::leave();

		return $html;
	}

	/**
	 * Enter one level.
	 *
	 * @return void
	 */
	public static function enter(): void {
		++self::$depth;
	}

	/**
	 * Leave one level (never below zero).
	 *
	 * @return void
	 */
	public static function leave(): void {
		self::$depth = max( 0, self::$depth - 1 );
	}

	/**
	 * Whether a legacy-template block is rendering right now.
	 *
	 * @return bool
	 */
	public static function inside(): bool {
		return self::$depth > 0;
	}

	/**
	 * Reset (tests).
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$depth = 0;
	}
}

<?php
/**
 * Shared single-product placement (classic hook vs. block theme filter).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

/**
 * Places compliance output under the add-to-cart form on the product page.
 *
 * Classic theme: `woocommerce_single_product_summary` at the given priority.
 * Block theme: after the server-rendered `woocommerce/add-to-cart-form` block
 * (on block themes WooCommerce runs every summary callback in one place, before
 * the excerpt, so the classic hook would put the output above it). Each key is
 * printed at most once per request; a shortcode may claim a key with mark().
 *
 * Usage: ProductPlacement::add( 'notice', 35, 20, static fn ( \WC_Product $p ): string => '…' );
 */
final class ProductPlacement {

	/**
	 * Keys already printed in this request.
	 *
	 * @var array<string, bool>
	 */
	private static array $rendered = [];

	/**
	 * Register a product-page contribution.
	 *
	 * @param string   $key              Unique key ('notice', 'garan', …).
	 * @param int      $classic_priority Priority on woocommerce_single_product_summary.
	 * @param int      $block_priority   Priority on render_block_woocommerce/add-to-cart-form.
	 * @param callable $render           fn( \WC_Product $product ): string — returns HTML ('' = nothing).
	 * @return void
	 */
	public static function add( string $key, int $classic_priority, int $block_priority, callable $render ): void {
		add_action(
			'woocommerce_single_product_summary',
			static function () use ( $key, $render ): void {
				if ( self::is_block_theme() ) {
					return;
				}

				global $product;

				if ( $product instanceof \WC_Product ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderers return escaped HTML.
					echo self::once( $key, $render, $product );
				}
			},
			$classic_priority
		);

		add_filter(
			'render_block_woocommerce/add-to-cart-form',
			static function ( $html, $block = null, $instance = null ) use ( $key, $render ) {
				if ( ! self::is_block_theme() ) {
					return $html;
				}

				$product = self::block_product( $instance );

				return null === $product ? $html : (string) $html . self::once( $key, $render, $product );
			},
			$block_priority,
			3
		);
	}

	/**
	 * Claim a key (e.g. from a shortcode) so the automatic placement is skipped.
	 *
	 * @param string $key Key.
	 * @return void
	 */
	public static function mark( string $key ): void {
		self::$rendered[ $key ] = true;
	}

	/**
	 * Whether a key was already printed in this request.
	 *
	 * @param string $key Key.
	 * @return bool
	 */
	public static function rendered( string $key ): bool {
		return ! empty( self::$rendered[ $key ] );
	}

	/**
	 * Whether the active theme is a block (FSE) theme.
	 *
	 * @return bool
	 */
	public static function is_block_theme(): bool {
		if ( function_exists( 'wc_current_theme_is_fse_theme' ) ) {
			return (bool) wc_current_theme_is_fse_theme();
		}

		return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
	}

	/**
	 * Render a key once.
	 *
	 * @param string      $key     Key.
	 * @param callable    $render  Renderer.
	 * @param \WC_Product $product Product.
	 * @return string
	 */
	private static function once( string $key, callable $render, \WC_Product $product ): string {
		if ( self::rendered( $key ) ) {
			return '';
		}

		$html = (string) $render( $product );

		if ( '' !== $html ) {
			self::mark( $key );
		}

		return $html;
	}

	/**
	 * Product of an add-to-cart-form block instance (block context, then the global).
	 *
	 * @param mixed $instance WP_Block instance.
	 * @return \WC_Product|null
	 */
	private static function block_product( $instance ): ?\WC_Product {
		if ( $instance instanceof \WP_Block && ! empty( $instance->context['postId'] ) ) {
			$product = wc_get_product( (int) $instance->context['postId'] );

			if ( $product instanceof \WC_Product ) {
				return $product;
			}
		}

		global $product;

		return $product instanceof \WC_Product ? $product : null;
	}
}

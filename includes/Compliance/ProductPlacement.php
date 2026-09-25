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
 * Block theme: after the server-rendered `woocommerce/add-to-cart-form` or
 * `woocommerce/add-to-cart-with-options` block
 * (on block themes WooCommerce runs every summary callback in one place, before
 * the excerpt, so the classic hook would put the output above it). A block
 * theme that renders the product through the `woocommerce/legacy-template`
 * block runs the classic templates, so there the classic hook is used (see
 * LegacyTemplateScope). Each key is printed at most once per product per
 * request (two products on one page each get theirs); a shortcode may claim a
 * key for a product with mark().
 *
 * Usage: ProductPlacement::add( 'notice', 35, 20, static fn ( \WC_Product $p ): string => '…' );
 */
final class ProductPlacement {

	/**
	 * Add-to-cart blocks the output follows on block themes (classic form and
	 * the newer "Add to Cart + Options" block).
	 *
	 * @var string[]
	 */
	private const ADD_TO_CART_BLOCKS = [ 'woocommerce/add-to-cart-form', 'woocommerce/add-to-cart-with-options' ];

	/**
	 * "key:product_id" pairs already printed in this request.
	 *
	 * @var array<string, bool>
	 */
	private static array $rendered = [];

	/**
	 * Register a product-page contribution.
	 *
	 * @param string   $key              Unique key ('notice', 'garan', …).
	 * @param int      $classic_priority Priority on woocommerce_single_product_summary.
	 * @param int      $block_priority   Priority on the add-to-cart block render filters.
	 * @param callable $render           fn( \WC_Product $product ): string — returns HTML ('' = nothing).
	 * @return void
	 */
	public static function add( string $key, int $classic_priority, int $block_priority, callable $render ): void {
		LegacyTemplateScope::register();

		add_action(
			'woocommerce_single_product_summary',
			static function () use ( $key, $render ): void {
				if ( ! self::classic_hooks_active() ) {
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

		$append = static function ( $html, $block = null, $instance = null ) use ( $key, $render ) {
			if ( ! self::is_block_theme() ) {
				return $html;
			}

			$product = self::block_product( $instance );

			return null === $product ? $html : (string) $html . self::once( $key, $render, $product );
		};

		// Whichever add-to-cart block renders first gets the output (once per product).
		foreach ( self::ADD_TO_CART_BLOCKS as $block_name ) {
			add_filter( 'render_block_' . $block_name, $append, $block_priority, 3 );
		}
	}

	/**
	 * Claim a key for a product (e.g. from a shortcode) so the automatic
	 * placement of that product is skipped.
	 *
	 * @param string $key        Key.
	 * @param int    $product_id Product id.
	 * @return void
	 */
	public static function mark( string $key, int $product_id ): void {
		self::$rendered[ $key . ':' . $product_id ] = true;
	}

	/**
	 * Whether a key was already printed for a product in this request.
	 *
	 * @param string $key        Key.
	 * @param int    $product_id Product id.
	 * @return bool
	 */
	public static function rendered( string $key, int $product_id ): bool {
		return ! empty( self::$rendered[ $key . ':' . $product_id ] );
	}

	/**
	 * Forget every claim (tests).
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$rendered = [];
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
	 * Whether the classic single-product hooks are the placement to use now:
	 * classic themes, or a block theme rendering the legacy-template block.
	 *
	 * @return bool
	 */
	public static function classic_hooks_active(): bool {
		return ! self::is_block_theme() || LegacyTemplateScope::inside();
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
		$product_id = (int) $product->get_id();

		if ( self::rendered( $key, $product_id ) ) {
			return '';
		}

		$html = (string) $render( $product );

		if ( '' !== $html ) {
			self::mark( $key, $product_id );
		}

		return $html;
	}

	/**
	 * Product of an add-to-cart block instance (block context, then the global).
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

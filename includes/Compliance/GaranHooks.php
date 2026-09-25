<?php
/**
 * Front-end placements of the GARAN durability label.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

use LightweightPlugins\Elallas\Options;

/**
 * Registered only when garan_enabled is on. The label before the order / pay
 * button is mandatory (45/2014. 15. § (1)): it has no setting, only the
 * `elallas_garan_checkout_visible` developer filter.
 */
final class GaranHooks {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		$mode = GaranRenderer::product_mode();

		ProductPlacement::add( 'garan', 31, 10, [ $this, 'product_html' ] );
		new GaranVariations();

		if ( 'description' === $mode ) {
			add_action( 'woocommerce_after_single_product_summary', [ $this, 'render_product_full' ], 12 );
			add_filter( 'render_block_woocommerce/product-details', [ $this, 'append_product_full' ], 10, 1 );
		}

		add_action( 'woocommerce_review_order_before_submit', [ $this, 'render_checkout' ], 10 );
		add_action( 'woocommerce_pay_order_before_submit', [ $this, 'render_order_pay' ], 10 );
		add_action( 'wp', [ $this, 'queue_checkout_block' ] );
		add_action( 'woocommerce_order_item_meta_end', [ $this, 'render_item_view' ], 10, 4 );

		if ( Options::get( 'garan_display_cart' ) ) {
			/**
			 * Filters the priority on woocommerce_proceed_to_checkout (before express-pay buttons).
			 *
			 * @param int $priority Default 5.
			 */
			add_action( 'woocommerce_proceed_to_checkout', [ $this, 'render_cart' ], (int) apply_filters( 'elallas_garan_cart_priority', 5 ) );
		}

		if ( Options::get( 'garan_display_archive' ) ) {
			add_action( 'woocommerce_after_shop_loop_item_title', [ $this, 'render_loop' ], 15 );
		}
	}

	/**
	 * Product page label under the add-to-cart form (via ProductPlacement).
	 *
	 * In description mode only the text line appears here; the full label
	 * follows under the description.
	 *
	 * @param \WC_Product $product Product.
	 * @return string
	 */
	public function product_html( \WC_Product $product ): string {
		if ( 'description' === GaranRenderer::product_mode() ) {
			return GaranRenderer::product_text( $product );
		}

		return GaranRenderer::product_label( $product );
	}

	/**
	 * Description mode: full label after the product tabs (classic themes and
	 * the legacy-template block on block themes).
	 *
	 * @return void
	 */
	public function render_product_full(): void {
		if ( ! ProductPlacement::classic_hooks_active() ) {
			return;
		}

		global $product;
		if ( $product instanceof \WC_Product ) {
			echo GaranRenderer::product_label( $product, 'full' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- GaranRenderer output.
		}
	}

	/**
	 * Description mode on block themes: after the product-details block.
	 *
	 * @param string $html Block HTML.
	 * @return string
	 */
	public function append_product_full( $html ): string {
		global $product;

		if ( ! ProductPlacement::is_block_theme() || ! $product instanceof \WC_Product ) {
			return (string) $html;
		}

		return (string) $html . GaranRenderer::product_label( $product, 'full' );
	}

	/**
	 * Classic checkout: right before the order button (after the notice @5).
	 *
	 * @return void
	 */
	public function render_checkout(): void {
		if ( self::checkout_visible( 'checkout' ) ) {
			echo GaranCheckoutList::from_cart( 'checkout' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output.
		}
	}

	/**
	 * Order-pay page: covered items of the order, before the pay button.
	 *
	 * @return void
	 */
	public function render_order_pay(): void {
		$order = function_exists( 'wc_get_order' ) ? wc_get_order( absint( get_query_var( 'order-pay' ) ) ) : false;
		if ( ! $order instanceof \WC_Order || ! GoodsScope::order_has_goods( $order ) ) {
			return;
		}
		if ( ! self::checkout_visible( 'orderpay', $order ) ) {
			return;
		}

		echo GaranCheckoutList::from_order( $order, 'orderpay' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output.
	}

	/**
	 * Classic cart: before the express-pay and "proceed to checkout" buttons.
	 *
	 * @return void
	 */
	public function render_cart(): void {
		if ( ! GaranRenderer::hidden_for_b2b( 'cart' ) ) {
			echo GaranCheckoutList::from_cart( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output.
		}
	}

	/**
	 * Block checkout: contribute the list to the slot (closest to the button).
	 *
	 * @return void
	 */
	public function queue_checkout_block(): void {
		if ( ! CheckoutSlot::is_block_checkout() ) {
			return;
		}

		CheckoutSlot::contribute(
			'garan',
			20,
			static function (): string {
				return self::checkout_visible( 'slot' ) ? GaranCheckoutList::from_cart( 'slot' ) : '';
			}
		);
	}

	/**
	 * Order view (thank-you page, My account): label under each covered item.
	 *
	 * E-mails are handled by GaranEmail; the order-pay page shows the list
	 * before the pay button instead.
	 *
	 * @param int   $item_id    Item id.
	 * @param mixed $item       Item.
	 * @param mixed $order      Order.
	 * @param bool  $plain_text Plain-text context.
	 * @return void
	 */
	public function render_item_view( $item_id, $item, $order, $plain_text = false ): void {
		if ( $plain_text || '' !== EmailContext::current() || is_admin() || ! Options::get( 'garan_display_email' ) ) {
			return;
		}
		if ( ! $item instanceof \WC_Order_Item_Product || ! $order instanceof \WC_Order ) {
			return;
		}
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
			return;
		}
		if ( GaranRenderer::hidden_for_b2b( 'order', $order ) ) {
			return;
		}

		$data = GaranResolver::for_order_item( $item );
		if ( null === $data ) {
			return;
		}

		$html = GaranRenderer::render(
			$data,
			[
				'mode'       => 'nested',
				'prefix'     => GaranCheckoutList::item_prefix( (int) $item_id ),
				'lazy'       => true,
				'product_id' => (int) $item->get_product_id(),
			]
		);
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- GaranRenderer output.
	}

	/**
	 * Shop / archive loop: nested label on covered product cards only.
	 *
	 * @return void
	 */
	public function render_loop(): void {
		global $product;

		if ( ! $product instanceof \WC_Product || GaranRenderer::hidden_for_b2b( 'loop' ) ) {
			return;
		}

		$data = GaranResolver::for_wc_product( $product );
		if ( null === $data ) {
			return;
		}

		$html = GaranRenderer::render(
			$data,
			[
				'mode'         => 'nested',
				'lazy'         => true,
				'product_name' => $product->get_name(),
				'product_id'   => (int) $product->get_id(),
			]
		);
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- GaranRenderer output.
	}

	/**
	 * Mandatory checkout visibility (developer filter + B2B hiding).
	 *
	 * @param string         $context checkout|slot|orderpay.
	 * @param \WC_Order|null $order   Order (order-pay).
	 * @return bool
	 */
	private static function checkout_visible( string $context, ?\WC_Order $order = null ): bool {
		/**
		 * Filters whether the mandatory GARAN list before the order button is shown.
		 *
		 * @param bool           $visible Default true.
		 * @param string         $context checkout|slot|orderpay.
		 * @param \WC_Order|null $order   Order on the order-pay page.
		 */
		if ( ! apply_filters( 'elallas_garan_checkout_visible', true, $context, $order ) ) {
			return false;
		}

		return ! GaranRenderer::hidden_for_b2b( $context, $order );
	}
}

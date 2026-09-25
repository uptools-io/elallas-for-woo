<?php
/**
 * Snapshots the GARAN data onto order line items at checkout.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

/**
 * Always instantiated (independent of garan_enabled), so orders placed while
 * the module is off still carry the purchase-time data. Runs for the classic
 * and the block checkout (the Store API creates line items through
 * WC_Checkout::create_order_line_items(), which fires the same hook).
 */
final class GaranSnapshot {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'snapshot_item' ], 10, 4 );
	}

	/**
	 * Store `_lw_elallas_garan` on covered items (HPOS-safe WC CRUD).
	 *
	 * @param mixed  $item          Order item.
	 * @param string $cart_item_key Cart item key.
	 * @param mixed  $values        Cart item values.
	 * @param mixed  $order         Order.
	 * @return void
	 */
	public function snapshot_item( $item, $cart_item_key = '', $values = [], $order = null ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Hook signature.
		if ( ! $item instanceof \WC_Order_Item_Product || ! is_array( $values ) ) {
			return;
		}

		$data = GaranResolver::for_cart_item( $values );
		if ( null === $data ) {
			return;
		}

		$snapshot      = $data->to_array();
		$snapshot['v'] = OfficialAssets::ASSET_VERSION;
		$item->add_meta_data( GaranResolver::ITEM_META, (string) wp_json_encode( $snapshot ), true );
	}
}

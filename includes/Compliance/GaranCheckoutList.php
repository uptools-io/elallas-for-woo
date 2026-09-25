<?php
/**
 * GARAN label list of the covered cart / order items.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

use LightweightPlugins\Elallas\Frontend\TemplateLoader;

/**
 * Builds templates/frontend/checkout-garan-list.php: one row per covered item
 * with its product name, nested label and text line. Prefixes are
 * deterministic (cart item key / order item id) so an AJAX-refreshed fragment
 * never collides with ids already on the page; identical labels share one
 * full-label <template>.
 */
final class GaranCheckoutList {

	/**
	 * List for the current cart ('' when no item is covered).
	 *
	 * @param string $context checkout|slot|cart.
	 * @return string
	 */
	public static function from_cart( string $context ): string {
		if ( ! function_exists( 'WC' ) || null === WC()->cart ) {
			return '';
		}

		$items = [];
		foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
			$product = $cart_item['data'] ?? null;
			$data    = GaranResolver::for_cart_item( $cart_item );
			if ( null === $data || ! $product instanceof \WC_Product ) {
				continue;
			}
			$items[] = [
				'key'    => (string) $key,
				'prefix' => 'elg-' . substr( md5( (string) $key ), 0, 8 ),
				'name'   => $product->get_name(),
				'id'     => (int) $product->get_id(),
				'data'   => $data,
			];
		}

		return self::render( $items, 'data-elallas-cart-key', $context );
	}

	/**
	 * List for an order (order-pay page): snapshot, else current data.
	 *
	 * @param \WC_Order $order   Order.
	 * @param string    $context orderpay.
	 * @return string
	 */
	public static function from_order( \WC_Order $order, string $context ): string {
		$items = [];
		foreach ( $order->get_items() as $item_id => $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}
			$data = GaranResolver::for_order_item( $item );
			if ( null === $data ) {
				continue;
			}
			$items[] = [
				'key'    => (string) $item_id,
				'prefix' => self::item_prefix( (int) $item_id ),
				'name'   => $item->get_name(),
				'id'     => (int) $item->get_product_id(),
				'data'   => $data,
			];
		}

		return self::render( $items, 'data-elallas-item-id', $context );
	}

	/**
	 * Deterministic prefix of an order item.
	 *
	 * @param int $item_id Order item id.
	 * @return string
	 */
	public static function item_prefix( int $item_id ): string {
		return 'elg-' . substr( md5( 'item-' . $item_id ), 0, 8 );
	}

	/**
	 * Render the rows.
	 *
	 * @param array<int, array{key: string, prefix: string, name: string, id: int, data: GaranData}> $items    Covered items.
	 * @param string                                                                                 $key_attr Row key attribute.
	 * @param string                                                                                 $context  Context.
	 * @return string
	 */
	private static function render( array $items, string $key_attr, string $context ): string {
		if ( [] === $items ) {
			return '';
		}

		$rows = [];
		$seen = [];
		foreach ( $items as $item ) {
			$ref  = 'elgt-' . substr( $item['data']->hash( OfficialAssets::ASSET_VERSION ), 0, 10 );
			$html = GaranRenderer::render(
				$item['data'],
				[
					'mode'          => 'nested',
					'prefix'        => $item['prefix'],
					'lazy'          => true,
					'template_ref'  => $ref,
					'with_template' => ! isset( $seen[ $ref ] ),
					'product_name'  => $item['name'],
					'product_id'    => $item['id'],
				]
			);

			$seen[ $ref ] = true;
			$rows[]       = [
				'key'   => $item['key'],
				'label' => $html,
			];
		}

		return TemplateLoader::render(
			'frontend/checkout-garan-list.php',
			[
				'rows'     => $rows,
				'key_attr' => $key_attr,
				'context'  => $context,
			]
		);
	}
}

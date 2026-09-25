<?php
/**
 * GARAN label lines in customer order e-mails.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

use LightweightPlugins\Elallas\Frontend\TemplateLoader;
use LightweightPlugins\Elallas\Options;

/**
 * Adds the GARAN line under each covered item of customer order e-mails
 * (never admin e-mails), from the purchase-time snapshot. 1.1.0: text + Your
 * Europe link + product page link, plus the filled label PNG (GaranRaster)
 * when the host has GD FreeType. An unfilled official ("XX") image is never
 * sent.
 */
final class GaranEmail {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		if ( Options::get( 'garan_display_email' ) ) {
			add_action( 'woocommerce_order_item_meta_end', [ $this, 'render_item_email' ], 10, 4 );
		}
	}

	/**
	 * Print the line (HTML or plain text).
	 *
	 * @param int   $item_id    Item id.
	 * @param mixed $item       Item.
	 * @param mixed $order      Order.
	 * @param bool  $plain_text Plain-text e-mail.
	 * @return void
	 */
	public function render_item_email( $item_id, $item, $order, $plain_text = false ): void {
		if ( ! EmailContext::in_customer_email() || ! $item instanceof \WC_Order_Item_Product ) {
			return;
		}
		if ( $order instanceof \WC_Order && GaranRenderer::hidden_for_b2b( 'email', $order ) ) {
			return;
		}

		$data = GaranResolver::for_order_item( $item );
		if ( null === $data ) {
			return;
		}

		$product = $item->get_product();
		$vars    = [
			'text'            => GaranRenderer::text( $data ),
			'garan_url'       => GaranRenderer::GARAN_URL,
			'garan_url_label' => GaranRenderer::GARAN_URL_LABEL,
			'product_url'     => $product instanceof \WC_Product ? (string) get_permalink( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id() ) : '',
			'image_url'       => $plain_text ? '' : GaranRaster::png_url( $data ),
		];

		echo TemplateLoader::render( $plain_text ? 'emails/plain/garan-item.php' : 'emails/garan-item.php', $vars ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped template output.
	}
}

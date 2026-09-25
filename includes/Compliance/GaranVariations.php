<?php
/**
 * Variation-aware GARAN data for the product page.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

/**
 * Adds each variation's resolved GARAN values to the variation data
 * (`elallas_garan`), so assets/js/compliance-garan.js can update the
 * already validated label texts on `found_variation` / `reset_data`, or hide
 * the label for a variation without one.
 */
final class GaranVariations {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_filter( 'woocommerce_available_variation', [ $this, 'variation_data' ], 10, 3 );
	}

	/**
	 * Add `elallas_garan` (payload or null) to a variation's data.
	 *
	 * @param mixed $data      Variation data.
	 * @param mixed $product   Parent product.
	 * @param mixed $variation Variation.
	 * @return mixed
	 */
	public function variation_data( $data, $product, $variation ) {
		if ( ! is_array( $data ) || ! $variation instanceof \WC_Product ) {
			return $data;
		}

		$data['elallas_garan'] = self::payload( GaranResolver::for_wc_product( $variation ) );

		return $data;
	}

	/**
	 * First covered variation of a variable product (parent not covered).
	 *
	 * @param \WC_Product $product Variable product.
	 * @return GaranData|null
	 */
	public static function first_covered( \WC_Product $product ): ?GaranData {
		foreach ( $product->get_children() as $child_id ) {
			$data = GaranResolver::for_product( (int) $product->get_id(), (int) $child_id );
			if ( null !== $data ) {
				return $data;
			}
		}

		return null;
	}

	/**
	 * Client payload of a label, null = no label.
	 *
	 * @param GaranData|null $data Data.
	 * @return array<string, string>|null
	 */
	public static function payload( ?GaranData $data ): ?array {
		if ( null === $data ) {
			return null;
		}

		$text = GaranRenderer::text( $data );

		return [
			'years' => $data->years_label(),
			'brand' => $data->brand(),
			'model' => $data->model(),
			'text'  => $text,
			'title' => 'EU GARAN – ' . $text,
			'open'  => $text . ' – ' . __( 'a teljes címke megnyitása', 'elallas-for-woo' ),
		];
	}
}

<?php
/**
 * Renders the filled GARAN label (HTML).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

use LightweightPlugins\Elallas\Frontend\TemplateLoader;

/**
 * Builds the label markup through templates/frontend/garan-label.php:
 * nested (opens the full label on first interaction) or full, always with the
 * accessible text line and the Your Europe GARAN link.
 */
final class GaranRenderer {

	/**
	 * Your Europe GARAN page (Annex II of Regulation (EU) 2025/1960). The QR
	 * code of the full label encodes the same path without `index.htm`
	 * (https://europa.eu/youreurope/commercial-guarantee-durability).
	 */
	public const GARAN_URL = 'https://europa.eu/youreurope/commercial-guarantee-durability/index.htm';

	/**
	 * Visible link text.
	 */
	public const GARAN_URL_LABEL = 'europa.eu/youreurope/commercial-guarantee-durability';

	public const STYLE_HANDLE  = 'elallas-compliance-garan';
	public const SCRIPT_HANDLE = 'elallas-compliance-garan';

	/**
	 * Accessible text: "Gyártói tartóssági jótállás: 3 év (Brand, Model)".
	 *
	 * @param GaranData $data Data.
	 * @return string
	 */
	public static function text( GaranData $data ): string {
		return sprintf(
			/* translators: 1: years, 2: brand, 3: model identifier */
			__( 'Gyártói tartóssági jótállás: %1$s év (%2$s, %3$s)', 'elallas-for-woo' ),
			$data->years_label(),
			$data->brand(),
			$data->model()
		);
	}

	/**
	 * Label HTML.
	 *
	 * Args: mode (nested|full), prefix (deterministic on AJAX-refreshed and
	 * checkout surfaces), lazy (full label inside <template>, cloned on first
	 * open), template_ref (hash key shared by identical labels in one list: only
	 * the first row carries the template), product_name, product_id.
	 *
	 * @param GaranData            $data Data.
	 * @param array<string, mixed> $args Arguments.
	 * @return string
	 */
	public static function render( GaranData $data, array $args = [] ): string {
		$args = wp_parse_args(
			$args,
			[
				'mode'          => 'nested',
				'prefix'        => '',
				'lazy'          => false,
				'template_ref'  => '',
				'with_template' => true,
				'product_name'  => '',
				'product_id'    => 0,
				'text_only'     => false,
				'hidden'        => false,
				'default_json'  => '',
			]
		);

		$mode   = 'full' === $args['mode'] ? 'full' : 'nested';
		$prefix = sanitize_html_class( (string) $args['prefix'] );
		$prefix = '' !== $prefix ? $prefix : wp_unique_id( 'elg' );
		$text   = self::text( $data );
		$full   = '';
		$nested = '';

		if ( 'nested' === $mode && ! $args['text_only'] ) {
			$nested = self::svg( 'nested', $data, $prefix . '-n', '' );
		}
		if ( ! $args['text_only'] && ( 'full' === $mode || ! $args['lazy'] || $args['with_template'] ) ) {
			$full = self::svg( 'colour', $data, $prefix . '-f', $text );
		}

		self::enqueue();

		return TemplateLoader::render(
			'frontend/garan-label.php',
			[
				'mode'            => $args['text_only'] ? 'text' : $mode,
				'prefix'          => $prefix,
				'nested_svg'      => $nested,
				'full_svg'        => $full,
				'lazy'            => 'nested' === $mode && (bool) $args['lazy'],
				'template_ref'    => (string) $args['template_ref'],
				'text'            => $text,
				'product_name'    => (string) $args['product_name'],
				'product_id'      => (int) $args['product_id'],
				'garan_url'       => self::GARAN_URL,
				'garan_url_label' => self::GARAN_URL_LABEL,
				'hidden'          => (bool) $args['hidden'],
				'default_json'    => (string) $args['default_json'],
				'available'       => ! $args['text_only'] && self::available( $mode, $nested, $full, (bool) $args['lazy'] && ! $args['with_template'] ),
			]
		);
	}

	/**
	 * Product-page label (mode from the settings, unique prefix).
	 *
	 * @param \WC_Product $product Product.
	 * @param string      $mode    nested|full ('' = settings).
	 * @return string
	 */
	public static function product_label( \WC_Product $product, string $mode = '' ): string {
		if ( self::hidden_for_b2b( 'product' ) ) {
			return '';
		}

		$data   = GaranResolver::for_wc_product( $product );
		$hidden = false;
		$json   = '';
		if ( $product->is_type( 'variable' ) ) {
			// Variations may override the parent; the script swaps the values on
			// found_variation. Parent not covered: first covered variation, hidden.
			$json = (string) wp_json_encode( GaranVariations::payload( $data ) );
			if ( null === $data ) {
				$data   = GaranVariations::first_covered( $product );
				$hidden = true;
			}
		}
		if ( null === $data ) {
			return '';
		}

		$mode = '' !== $mode ? $mode : self::product_mode();

		return self::render(
			$data,
			[
				'mode'         => 'full' === $mode ? 'full' : 'nested',
				'product_id'   => (int) $product->get_id(),
				'hidden'       => $hidden,
				'default_json' => $json,
			]
		);
	}

	/**
	 * Admin preview: the filled nested label (decorative) and the text line.
	 *
	 * @param GaranData $data Saved, valid data.
	 * @return string SVG + escaped text, '' when the official file failed verification.
	 */
	public static function preview( GaranData $data ): string {
		$svg = self::svg( 'nested', $data, wp_unique_id( 'elgpv' ), '' );
		if ( '' === $svg ) {
			return '';
		}

		return '<div class="elallas-garan elallas-garan--preview" style="max-width:368px">' . $svg . '<p class="elallas-garan__text">' . esc_html( self::text( $data ) ) . '</p></div>';
	}

	/**
	 * Product-page text line only (description mode: the full label follows
	 * under the description).
	 *
	 * @param \WC_Product $product Product.
	 * @return string
	 */
	public static function product_text( \WC_Product $product ): string {
		$data = GaranResolver::for_wc_product( $product );
		if ( null === $data || self::hidden_for_b2b( 'product' ) ) {
			return '';
		}

		return self::render(
			$data,
			[
				'text_only'  => true,
				'product_id' => (int) $product->get_id(),
			]
		);
	}

	/**
	 * `[elallas_garan_label product_id="" mode="nested|full"]`.
	 *
	 * Defaults to the global product, then the current post. Called by
	 * ComplianceShortcodes only when the module is on.
	 *
	 * @param array<string, mixed> $atts Attributes.
	 * @return string
	 */
	public static function shortcode( array $atts ): string {
		$atts = shortcode_atts(
			[
				'product_id' => '',
				'mode'       => '',
			],
			$atts,
			'elallas_garan_label'
		);

		$product = self::shortcode_product( absint( $atts['product_id'] ) );
		if ( null === $product ) {
			return '';
		}

		$on_own_page = function_exists( 'is_product' ) && is_product() && (int) get_queried_object_id() === (int) $product->get_id();
		if ( $on_own_page && ProductPlacement::rendered( 'garan' ) ) {
			return '';
		}

		$mode = sanitize_key( (string) $atts['mode'] );
		$html = self::product_label( $product, in_array( $mode, [ 'nested', 'full' ], true ) ? $mode : '' );
		if ( '' !== $html && $on_own_page ) {
			ProductPlacement::mark( 'garan' );
		}

		return $html;
	}

	/**
	 * Product-page mode from the settings (whitelisted).
	 *
	 * @return string nested|full|description
	 */
	public static function product_mode(): string {
		$mode = (string) \LightweightPlugins\Elallas\Options::get( 'garan_product_mode' );

		return in_array( $mode, [ 'nested', 'full', 'description' ], true ) ? $mode : 'nested';
	}

	/**
	 * B2B hiding (shared rule with the notice).
	 *
	 * @param string         $context Context.
	 * @param \WC_Order|null $order   Order on order-based surfaces.
	 * @return bool
	 */
	public static function hidden_for_b2b( string $context, ?\WC_Order $order = null ): bool {
		return NoticeRenderer::hidden_for_b2b( $context, $order );
	}

	/**
	 * Enqueue the GARAN stylesheet (Inter @font-face) and toggle script.
	 *
	 * @return void
	 */
	public static function enqueue(): void {
		if ( ! function_exists( 'wp_enqueue_style' ) ) {
			return;
		}

		wp_enqueue_style( self::STYLE_HANDLE, ELALLAS_FOR_WOO_URL . 'assets/css/compliance-garan.css', [], ELALLAS_FOR_WOO_VERSION );
		wp_enqueue_script( self::SCRIPT_HANDLE, ELALLAS_FOR_WOO_URL . 'assets/js/compliance-garan.js', [], ELALLAS_FOR_WOO_VERSION, true );
	}

	/**
	 * Filled SVG of a variant ('' when the official file failed verification).
	 *
	 * @param string    $variant colour|nested.
	 * @param GaranData $data    Data.
	 * @param string    $prefix  Instance prefix.
	 * @param string    $title   Accessible name ('' = decorative).
	 * @return string
	 */
	private static function svg( string $variant, GaranData $data, string $prefix, string $title ): string {
		$source = GaranSource::get( $variant );
		if ( '' === $source ) {
			return '';
		}

		$desc = '' === $title ? '' : sprintf(
			/* translators: %s: Your Europe URL */
			__( 'Az Európai Unió hivatalos GARAN címkéje a gyártói tartóssági jótállásról. A QR-kód a %s oldalra mutat.', 'elallas-for-woo' ),
			self::GARAN_URL_LABEL
		);
		$svg = GaranSvg::fill( $source, $data, $prefix, 'nested' === $variant, '' === $title ? '' : 'EU GARAN – ' . $title, $desc );

		if ( '' === $svg ) {
			\LightweightPlugins\Elallas\Support\Logger::error( 'GARAN label could not be filled.', [ 'variant' => $variant ] );
		}

		return $svg;
	}

	/**
	 * Whether the graphic can be shown (official files verified and filled).
	 *
	 * @param string $mode       nested|full.
	 * @param string $nested     Nested SVG.
	 * @param string $full       Full SVG.
	 * @param bool   $shared_ref Full label comes from another row's template.
	 * @return bool
	 */
	private static function available( string $mode, string $nested, string $full, bool $shared_ref ): bool {
		if ( 'full' === $mode ) {
			return '' !== $full;
		}

		return '' !== $nested && ( '' !== $full || $shared_ref );
	}

	/**
	 * Product targeted by the shortcode.
	 *
	 * @param int $product_id Explicit id (0 = context).
	 * @return \WC_Product|null
	 */
	private static function shortcode_product( int $product_id ): ?\WC_Product {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return null;
		}
		if ( 0 === $product_id ) {
			global $product;
			if ( $product instanceof \WC_Product ) {
				return $product;
			}
			$product_id = (int) get_the_ID();
		}

		$found = $product_id > 0 ? wc_get_product( $product_id ) : null;

		return $found instanceof \WC_Product ? $found : null;
	}
}

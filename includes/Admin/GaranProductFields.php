<?php
/**
 * GARAN product editor fields (years, brand, model).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin;

use LightweightPlugins\Elallas\Compliance\GaranData;
use LightweightPlugins\Elallas\Compliance\GaranResolver;
use LightweightPlugins\Elallas\Data\DefaultTexts;
use LightweightPlugins\Elallas\Options;

/**
 * Adds the GARAN fields to the General product tab and validates them on save
 * (GaranData + GaranMetrics width check). Always active, independent of the
 * garan_enabled module switch, so data can be entered before switching on.
 */
final class GaranProductFields {

	public const META_ENABLED = Options::META_PREFIX . 'garan_enabled';
	public const META_YEARS   = Options::META_PREFIX . 'garan_years';
	public const META_BRAND   = Options::META_PREFIX . 'garan_brand';
	public const META_MODEL   = Options::META_PREFIX . 'garan_model';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'woocommerce_product_options_general_product_data', [ $this, 'render_fields' ], 20 );
		add_action( 'woocommerce_process_product_meta', [ $this, 'save_fields' ] );
	}

	/**
	 * Render the field group.
	 *
	 * @return void
	 */
	public function render_fields(): void {
		global $post;
		$product_id = $post instanceof \WP_Post ? (int) $post->ID : 0;
		$raw        = self::raw( $product_id );

		echo '<div class="options_group elallas-garan-fields">';

		if ( ! Options::get( 'garan_enabled' ) ) {
			printf( '<p class="description" style="padding:0 12px;">%s</p>', esc_html__( 'A GARAN modul jelenleg ki van kapcsolva a beállításokban; a címke nem jelenik meg.', 'elallas-for-woo' ) );
		}

		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
		if ( $product instanceof \WC_Product && $product->is_type( 'variable' ) ) {
			printf(
				'<p class="description elallas-garan-variable-warning" style="padding:0 12px;">%s</p>',
				esc_html__( 'Variálható termék: a variációk alapból ezeket az adatokat öröklik. Eltérő modellazonosítónál vagy jótállásnál a variáció „GARAN címke” beállításában adj meg saját adatot (vagy kapcsold ki).', 'elallas-for-woo' )
			);
		}

		woocommerce_wp_checkbox(
			[
				'id'    => self::META_ENABLED,
				'label' => __( 'Gyártói tartóssági jótállás (GARAN címke)', 'elallas-for-woo' ),
				'value' => 'yes' === $raw['enabled'] ? 'yes' : 'no',
			]
		);

		foreach ( self::text_fields() as $key => $field ) {
			woocommerce_wp_text_input( array_merge( $field, [ 'value' => $raw[ $key ] ] ) );
		}

		printf( '<p class="description elallas-garan-warning" style="padding:0 12px;">%s</p>', esc_html( DefaultTexts::garan_warning() ) );
		echo '</div>';
	}

	/**
	 * Save and validate.
	 *
	 * @param int $product_id Product id.
	 * @return void
	 */
	public function save_fields( int $product_id ): void {
		// woocommerce_process_product_meta verifies the product nonce upstream; re-check capability.
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$wants  = isset( $_POST[ self::META_ENABLED ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$values = [];
		foreach ( [
			'years' => self::META_YEARS,
			'brand' => self::META_BRAND,
			'model' => self::META_MODEL,
		] as $key => $meta ) {
			$values[ $key ] = isset( $_POST[ $meta ] ) && is_scalar( $_POST[ $meta ] ) ? self::clean( (string) wp_unslash( $_POST[ $meta ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised in clean().
		}

		$result = self::validate( $wants, $values );
		if ( '' !== $result['error'] && class_exists( '\WC_Admin_Meta_Boxes' ) ) {
			\WC_Admin_Meta_Boxes::add_error( $result['error'] );
		}

		self::store( $product_id, $result['enabled'], $result['values'] );
	}

	/**
	 * Validate submitted values.
	 *
	 * @param bool                  $wants  Checkbox ticked.
	 * @param array<string, string> $values Cleaned years/brand/model.
	 * @return array{enabled: string, values: array<string, string>, error: string}
	 */
	public static function validate( bool $wants, array $values ): array {
		$allow = GaranResolver::allow_half_years();
		$data  = GaranData::from_input( $values['years'], $values['brand'], $values['model'], $allow );

		if ( null !== $data ) {
			$values['years'] = $data->years();
		}
		if ( ! $wants ) {
			return [
				'enabled' => 'no',
				'values'  => $values,
				'error'   => '',
			];
		}

		return [
			'enabled' => null === $data ? 'no' : 'yes',
			'values'  => $values,
			'error'   => null === $data ? self::error_message( GaranData::errors( $values['years'], $values['brand'], $values['model'], $allow ) ) : '',
		];
	}

	/**
	 * Write the four meta keys.
	 *
	 * @param int                   $id      Product / variation id.
	 * @param string                $enabled '', 'yes' or 'no'.
	 * @param array<string, string> $values  Values.
	 * @return void
	 */
	public static function store( int $id, string $enabled, array $values ): void {
		update_post_meta( $id, self::META_ENABLED, $enabled );
		update_post_meta( $id, self::META_YEARS, $values['years'] );
		update_post_meta( $id, self::META_BRAND, $values['brand'] );
		update_post_meta( $id, self::META_MODEL, $values['model'] );
	}

	/**
	 * Whether the label is switched on for a product.
	 *
	 * @param int $id Product id.
	 * @return bool
	 */
	public static function is_enabled( int $id ): bool {
		return 'yes' === get_post_meta( $id, self::META_ENABLED, true );
	}

	/**
	 * Raw stored values.
	 *
	 * @param int $id Product / variation id.
	 * @return array{enabled: string, years: string, brand: string, model: string}
	 */
	public static function raw( int $id ): array {
		return [
			'enabled' => (string) get_post_meta( $id, self::META_ENABLED, true ),
			'years'   => (string) get_post_meta( $id, self::META_YEARS, true ),
			'brand'   => (string) get_post_meta( $id, self::META_BRAND, true ),
			'model'   => (string) get_post_meta( $id, self::META_MODEL, true ),
		];
	}

	/**
	 * Sanitise a text value: no tags, no control characters, no truncation.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function clean( string $value ): string {
		return trim( (string) preg_replace( '/[\x00-\x1F\x7F]/u', '', sanitize_text_field( $value ) ) );
	}

	/**
	 * Field definitions for woocommerce_wp_text_input().
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function text_fields(): array {
		return [
			'years' => [
				'id'                => self::META_YEARS,
				'label'             => __( 'Időtartam (év)', 'elallas-for-woo' ),
				'type'              => 'text',
				'description'       => __( 'Csak 2 évnél hosszabb, egész év (pl. 3); a rendszer ellenőrzi, hogy a címkén elfér-e.', 'elallas-for-woo' ),
				'custom_attributes' => [
					'inputmode' => 'decimal',
					'pattern'   => '[0-9]{1,2}([.,]5)?',
				],
			],
			'brand' => [
				'id'                => self::META_BRAND,
				'label'             => __( 'Gyártó (Brand/Trademark)', 'elallas-for-woo' ),
				'description'       => __( 'A gyártó által megadott név, a gyártó által a címkén használt formában.', 'elallas-for-woo' ),
				'custom_attributes' => [ 'maxlength' => '40' ],
			],
			'model' => [
				'id'                => self::META_MODEL,
				'label'             => __( 'Modellazonosító', 'elallas-for-woo' ),
				'custom_attributes' => [ 'maxlength' => '16' ],
			],
		];
	}

	/**
	 * Admin error text naming the failing fields.
	 *
	 * @param array<string, string> $errors field => code.
	 * @return string
	 */
	private static function error_message( array $errors ): string {
		$names = [
			'years' => __( 'időtartam', 'elallas-for-woo' ),
			'brand' => __( 'gyártó', 'elallas-for-woo' ),
			'model' => __( 'modellazonosító', 'elallas-for-woo' ),
		];
		$list  = implode( ', ', array_intersect_key( $names, $errors ) );

		return sprintf(
			/* translators: %s: comma-separated list of the invalid fields. */
			__( 'A GARAN címke nem került bekapcsolásra: az időtartam csak 2 évnél hosszabb, a címkén elférő egész év lehet, a gyártó és a modellazonosító kitöltése kötelező, és a címke fix mezőszélességén el kell férniük (a betűméret nem csökkenthető). Hibás vagy túl hosszú: %s.', 'elallas-for-woo' ),
			$list
		);
	}
}

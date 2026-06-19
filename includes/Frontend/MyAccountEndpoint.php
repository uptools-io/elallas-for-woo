<?php
/**
 * WooCommerce "My Account" withdrawals endpoint.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Frontend;

use LightweightPlugins\Elallas\Options;
use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Database\DocumentRepository;
use LightweightPlugins\Elallas\Pdf\DocumentService;

/**
 * Adds a /my-account/withdrawals/ endpoint listing the customer's cases.
 *
 * Keeps the withdrawal flow within two clicks for logged-in customers.
 */
final class MyAccountEndpoint {

	private const ENDPOINT = 'withdrawals';

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! Options::get( 'enabled' ) || ! Options::get( 'display_account' ) ) {
			return;
		}

		add_action( 'init', [ $this, 'add_endpoint' ] );
		add_filter( 'woocommerce_get_query_vars', [ $this, 'add_query_var' ] );
		add_filter( 'woocommerce_account_menu_items', [ $this, 'add_menu_item' ] );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', [ $this, 'render' ] );
	}

	/**
	 * Register the rewrite endpoint.
	 *
	 * @return void
	 */
	public function add_endpoint(): void {
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * Register the WooCommerce query var.
	 *
	 * @param array<string, string> $vars Query vars.
	 * @return array<string, string>
	 */
	public function add_query_var( array $vars ): array {
		$vars[ self::ENDPOINT ] = self::ENDPOINT;
		return $vars;
	}

	/**
	 * Add the account menu item.
	 *
	 * @param array<string, string> $items Menu items.
	 * @return array<string, string>
	 */
	public function add_menu_item( array $items ): array {
		$new = [];
		foreach ( $items as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'orders' === $key ) {
				$new[ self::ENDPOINT ] = __( 'Elállás', 'elallas-for-woo' );
			}
		}

		if ( ! isset( $new[ self::ENDPOINT ] ) ) {
			$new[ self::ENDPOINT ] = __( 'Elállás', 'elallas-for-woo' );
		}

		return $new;
	}

	/**
	 * Render the endpoint content.
	 *
	 * @return void
	 */
	public function render(): void {
		$cases     = CaseRepository::find_by_customer( get_current_user_id() );
		$downloads = [];

		foreach ( $cases as $case ) {
			$docs = DocumentRepository::for_case( $case->id );

			if ( ! empty( $docs ) ) {
				$url = DocumentService::download_url( (int) $docs[0]->id );
				if ( '' !== $url ) {
					$downloads[ $case->id ] = $url;
				}
			}
		}

		echo TemplateLoader::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'frontend/my-account.php',
			[
				'cases'     => $cases,
				'downloads' => $downloads,
				'form_url'  => Shortcodes::page_url(),
			]
		);
	}
}

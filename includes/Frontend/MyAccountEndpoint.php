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
 * Adds a My Account endpoint listing the customer's cases (default slug
 * `withdrawals`, merchant-editable — so e.g. `/fiokom/elallasaim/`).
 *
 * Keeps the withdrawal flow within two clicks for logged-in customers.
 */
final class MyAccountEndpoint {

	/**
	 * Internal endpoint key — the query-var, menu and action name. Stays stable
	 * even when the merchant renames the public URL slug, so routing and the
	 * `woocommerce_account_{key}_endpoint` hook never break. (This mirrors how
	 * WooCommerce keeps a fixed key per built-in endpoint and only varies the
	 * slug.) Also the default slug when no override is stored.
	 */
	private const ENDPOINT = 'withdrawals';

	/**
	 * Option holding the merchant-editable public URL slug. Named to sit in
	 * WooCommerce > Settings > Advanced > Account endpoints next to the built-in
	 * endpoint slugs; {@see \LightweightPlugins\Elallas\Admin\Settings\AccountEndpointSetting}
	 * renders the field there and WooCommerce persists it.
	 */
	public const SLUG_OPTION = 'woocommerce_myaccount_withdrawals_endpoint';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Registered regardless of display state so a slug change is always
		// picked up (and enabling the endpoint later resolves cleanly). Both
		// hooks are needed: the first time the slug is stored WordPress fires
		// `add_option_*`, and only later edits fire `update_option_*`.
		add_action( 'add_option_' . self::SLUG_OPTION, [ $this, 'flush_on_slug_change' ], 10, 0 );
		add_action( 'update_option_' . self::SLUG_OPTION, [ $this, 'flush_on_slug_change' ], 10, 0 );

		if ( ! Options::get( 'enabled' ) || ! Options::get( 'display_account' ) ) {
			return;
		}

		add_action( 'init', [ $this, 'add_endpoint' ] );
		add_filter( 'woocommerce_get_query_vars', [ $this, 'add_query_var' ] );
		add_filter( 'woocommerce_account_menu_items', [ $this, 'add_menu_item' ] );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', [ $this, 'render' ] );
	}

	/**
	 * The public URL slug for the endpoint.
	 *
	 * Merchant-editable via WooCommerce > Settings > Advanced > Account endpoints;
	 * defaults to `withdrawals`. Always returned URL-safe.
	 *
	 * @return string
	 */
	public static function slug(): string {
		$slug = sanitize_title( (string) get_option( self::SLUG_OPTION, self::ENDPOINT ) );

		return '' !== $slug ? $slug : self::ENDPOINT;
	}

	/**
	 * Register the rewrite endpoint.
	 *
	 * @return void
	 */
	public function add_endpoint(): void {
		add_rewrite_endpoint( self::slug(), EP_ROOT | EP_PAGES );
	}

	/**
	 * Register the WooCommerce query var.
	 *
	 * Key = stable internal name (routing/hooks); value = current public slug.
	 * WooCommerce remaps the slug query-var back onto this key when parsing the
	 * request, exactly as it does for its own renamed endpoints.
	 *
	 * @param array<string, string> $vars Query vars.
	 * @return array<string, string>
	 */
	public function add_query_var( array $vars ): array {
		$vars[ self::ENDPOINT ] = self::slug();
		return $vars;
	}

	/**
	 * Queue a rewrite flush when the endpoint slug changes.
	 *
	 * The endpoint is (re)registered with the new slug on the next request's
	 * `init`; {@see \LightweightPlugins\Elallas\Plugin::maybe_flush_rewrite()}
	 * then flushes at `init` priority 20 — after registration — so the new slug
	 * resolves and the old one 404s. Deferring to the next request (rather than
	 * flushing here) is required because this request already registered the
	 * old slug on `init`. Covers every change path (WooCommerce settings save,
	 * WP-CLI, programmatic), not only the WooCommerce settings screen.
	 *
	 * @return void
	 */
	public function flush_on_slug_change(): void {
		set_transient( 'lw_elallas_flush_rewrite', 1, MINUTE_IN_SECONDS );
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

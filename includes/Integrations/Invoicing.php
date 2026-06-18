<?php
/**
 * Invoicing integration.
 *
 * Best-effort bridge to Hungarian invoicing plugins (Számlázz.hu, Billingo) and
 * NAV VAT data. Never issues automatic storno invoices: it only adds an order
 * note advising manual handling and fires a hook so dedicated plugins can react.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Integrations;

use LightweightPlugins\Elallas\Woo\OrderAdapter;

/**
 * Wires withdrawal cases to invoicing providers in a guarded, best-effort way.
 */
final class Invoicing {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'elallas_case_created', [ $this, 'on_case_created' ], 10, 2 );
	}

	/**
	 * Annotate the order when a withdrawal case is created.
	 *
	 * @param int $case_id  Withdrawal case ID.
	 * @param int $order_id Linked WooCommerce order ID.
	 * @return void
	 */
	public function on_case_created( int $case_id, int $order_id ): void {
		$order = OrderAdapter::get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$providers = $this->detect( $order );

		if ( in_array( true, $providers, true ) ) {
			$order->add_order_note(
				__( 'Elállási kérelem érkezett. Számlázó bővítmény észlelve: a sztornó/módosító számlát manuálisan kell kezelni, automatikus sztornó nem történik.', 'elallas-for-woo' )
			);
		} else {
			$order->add_order_note(
				__( 'Elállási kérelem érkezett. Kérjük, a számla kezelését (sztornó/módosítás) végezze el manuálisan.', 'elallas-for-woo' )
			);
		}

		/**
		 * Fires after a withdrawal case is recorded against an order so external
		 * invoicing plugins can take over (no automatic storno is performed here).
		 *
		 * @param int $case_id  Withdrawal case ID.
		 * @param int $order_id Linked order ID.
		 */
		do_action( 'elallas_invoicing_case_created', $case_id, $order_id );
	}

	/**
	 * Detect which Hungarian invoicing providers are present.
	 *
	 * @param \WC_Order $order Order being processed.
	 * @return array<string, bool> Provider slug => detected.
	 */
	public function detect( \WC_Order $order ): array {
		$szamlazz = class_exists( '\SzamlaAgent\SzamlaAgent' )
			|| class_exists( 'SzamlaAgent' )
			|| function_exists( 'woocommerce_szamlazz' )
			|| defined( 'WOOCOMMERCE_SZAMLAZZ_VERSION' );

		$billingo = class_exists( '\Billingo\API\Connector' )
			|| function_exists( 'woocommerce_billingo' )
			|| defined( 'WOOCOMMERCE_BILLINGO_VERSION' )
			|| defined( 'BILLINGO_VERSION' );

		$nav_vat = '' !== OrderAdapter::vat_number( $order );

		return [
			'szamlazz' => $szamlazz,
			'billingo' => $billingo,
			'nav_vat'  => $nav_vat,
		];
	}
}

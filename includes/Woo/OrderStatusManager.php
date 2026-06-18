<?php
/**
 * Optional custom WooCommerce order statuses for withdrawals.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Woo;

use LightweightPlugins\Elallas\Options;
use LightweightPlugins\Elallas\Models\CaseStatus;
use LightweightPlugins\Elallas\Database\CaseRepository;

/**
 * Registers wc-withdrawal-* order statuses and syncs them from case statuses.
 *
 * Opt-in only (the "use_wc_statuses" setting). When off, the plugin only adds
 * order notes/meta and never changes the order status.
 */
final class OrderStatusManager {

	/**
	 * Constructor — only wires up when the setting is enabled.
	 */
	public function __construct() {
		if ( ! Options::get( 'use_wc_statuses' ) ) {
			return;
		}

		add_action( 'init', [ $this, 'register_statuses' ] );
		add_filter( 'wc_order_statuses', [ $this, 'add_statuses' ] );
		add_action( 'elallas_case_created', [ $this, 'on_created' ], 20, 2 );
		add_action( 'elallas_case_status_changed', [ $this, 'on_status_changed' ], 20, 3 );
	}

	/**
	 * Status slug => label map.
	 *
	 * @return array<string, string>
	 */
	private function statuses(): array {
		return [
			'wc-withdrawal-requested' => __( 'Elállás kérve', 'elallas-for-woo' ),
			'wc-withdrawal-review'    => __( 'Elállás – ellenőrzés alatt', 'elallas-for-woo' ),
			'wc-withdrawal-accepted'  => __( 'Elállás elfogadva', 'elallas-for-woo' ),
			'wc-withdrawal-closed'    => __( 'Elállás lezárva', 'elallas-for-woo' ),
		];
	}

	/**
	 * Register the custom order post statuses.
	 *
	 * @return void
	 */
	public function register_statuses(): void {
		foreach ( $this->statuses() as $slug => $label ) {
			register_post_status(
				$slug,
				[
					'label'                     => $label,
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: %s: number of orders. */
					'label_count'               => _n_noop( $label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>', 'elallas-for-woo' ),
				]
			);
		}
	}

	/**
	 * Add the statuses to the WooCommerce order status list.
	 *
	 * @param array<string, string> $statuses Existing statuses.
	 * @return array<string, string>
	 */
	public function add_statuses( array $statuses ): array {
		return array_merge( $statuses, $this->statuses() );
	}

	/**
	 * When a case is created, move the order to "withdrawal requested".
	 *
	 * @param int $case_id  Case ID.
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function on_created( int $case_id, int $order_id ): void {
		$this->set_order_status( $order_id, 'wc-withdrawal-requested' );
	}

	/**
	 * Sync the order status when the case status changes.
	 *
	 * @param int    $case_id    Case ID.
	 * @param string $old_status Previous status.
	 * @param string $new_status New status.
	 * @return void
	 */
	public function on_status_changed( int $case_id, string $old_status, string $new_status ): void {
		$target = $this->map( $new_status );
		$case   = CaseRepository::find( $case_id );

		if ( '' !== $target && null !== $case ) {
			$this->set_order_status( $case->order_id, $target );
		}
	}

	/**
	 * Map a case status to an order status slug ('' = leave order unchanged).
	 *
	 * @param string $case_status Case status.
	 * @return string
	 */
	private function map( string $case_status ): string {
		return match ( $case_status ) {
			CaseStatus::RECEIVED, CaseStatus::AUTO_CONFIRMED                                                  => 'wc-withdrawal-requested',
			CaseStatus::MANUAL_REVIEW                                                                         => 'wc-withdrawal-review',
			CaseStatus::ACCEPTED, CaseStatus::AWAITING_RETURN, CaseStatus::GOODS_RECEIVED, CaseStatus::REFUND_PENDING => 'wc-withdrawal-accepted',
			CaseStatus::CLOSED                                                                                => 'wc-withdrawal-closed',
			default                                                                                           => '',
		};
	}

	/**
	 * Set the order status via WooCommerce CRUD (HPOS-safe).
	 *
	 * @param int    $order_id Order ID.
	 * @param string $status   Status slug (with wc- prefix).
	 * @return void
	 */
	private function set_order_status( int $order_id, string $status ): void {
		$order = OrderAdapter::get_order( $order_id );

		if ( null === $order || 'wc-' . $order->get_status() === $status ) {
			return;
		}

		$order->update_status( $status, __( 'Elállási ügy státusz-szinkron.', 'elallas-for-woo' ) );
	}
}

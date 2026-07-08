<?php
/**
 * Surfaces withdrawal cases on the WooCommerce order edit screen.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin;

use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Database\CaseItemRepository;

/**
 * Renders a prominent panel on the order edit screen when the order has one or
 * more withdrawal cases, so the intent is not buried in the order notes.
 */
final class OrderWithdrawalNotice {

	/**
	 * Constructor — hooks the order data panel (works for both the legacy post
	 * edit screen and the HPOS order edit screen).
	 */
	public function __construct() {
		add_action( 'woocommerce_admin_order_data_after_order_details', [ $this, 'render' ] );
	}

	/**
	 * Render the withdrawal panel.
	 *
	 * @param mixed $order Order (WC_Order on the order screen).
	 * @return void
	 */
	public function render( $order ): void {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$cases = CaseRepository::find_by_order( $order->get_id() );

		if ( empty( $cases ) ) {
			return;
		}

		echo '<div class="elallas-order-withdrawal" style="clear:both; margin:12px 0 0; padding:12px 16px; border:1px solid #c3c4c7; border-left:4px solid #b32d2e; background:#fcf9e8;">';
		echo '<h3 style="margin:0 0 8px;">' . esc_html__( 'Elállási igény', 'elallas-for-woo' ) . '</h3>';
		echo '<ul style="margin:0; list-style:disc; padding-left:20px;">';

		foreach ( $cases as $case ) {
			$url          = admin_url( 'admin.php?page=elallas-for-woo&view=case&case_id=' . (int) $case->id );
			$has_excepted = self::has_excepted_item( (int) $case->id );

			echo '<li style="margin-bottom:4px;">';
			echo '<a href="' . esc_url( $url ) . '"><strong>' . esc_html( $case->case_number ) . '</strong></a> — ';
			echo esc_html( $case->status_label() );
			echo ' <span style="color:#50575e;">(' . esc_html( (string) $case->submitted_at ) . ')</span>';

			if ( $has_excepted ) {
				echo ' <strong style="color:#b32d2e;">— ' . esc_html__( 'kizárt tételt tartalmaz', 'elallas-for-woo' ) . '</strong>';
			}

			echo '</li>';
		}

		echo '</ul></div>';
	}

	/**
	 * Whether any item of the case is flagged as an exclusion.
	 *
	 * @param int $case_id Case ID.
	 * @return bool
	 */
	private static function has_excepted_item( int $case_id ): bool {
		foreach ( CaseItemRepository::for_case( $case_id ) as $item ) {
			if ( $item->is_excepted() ) {
				return true;
			}
		}

		return false;
	}
}

<?php
/**
 * Records a WooCommerce refund for a withdrawal case.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Woo;

use LightweightPlugins\Elallas\Database\EventRepository;
use LightweightPlugins\Elallas\Domain\CaseService;
use LightweightPlugins\Elallas\Domain\RefundCalculator;
use LightweightPlugins\Elallas\Models\CaseStatus;
use LightweightPlugins\Elallas\Models\WithdrawalCase;

/**
 * Creates the refund (record only, no payment gateway), restocks the chosen
 * lines, logs a case event and optionally closes the case.
 */
final class RefundCreator {

	/**
	 * Record the refund.
	 *
	 * @param WithdrawalCase                   $case     Case.
	 * @param array<int, array<string, mixed>> $request  Item ID => [qty] or [amount] (gross).
	 * @param array<int, int>                  $restock  Order item IDs to put back in stock.
	 * @param bool                             $close    Whether to close the case afterwards.
	 * @param int                              $actor_id Admin user ID.
	 * @return int|\WP_Error Refund ID or error.
	 */
	public static function create( WithdrawalCase $case, array $request, array $restock, bool $close, int $actor_id ): int|\WP_Error {
		$order = OrderAdapter::get_order( $case->order_id );

		if ( null === $order || ! CaseStatus::allows_refund( $case->status ) ) {
			return new \WP_Error( 'elallas_refund_unavailable', __( 'Ehhez az ügyhöz most nem rögzíthető visszatérítés.', 'elallas-for-woo' ) );
		}

		$lines  = RefundLines::for_case( $case, $order );
		$result = RefundCalculator::build( $lines, $request, wc_get_price_decimals() );

		if ( (float) $result['amount'] <= 0 ) {
			return new \WP_Error( 'elallas_refund_empty', __( 'Nincs visszatérítendő összeg: minden tétel 0, vagy már vissza lett térítve.', 'elallas-for-woo' ) );
		}

		$refund = wc_create_refund(
			[
				'amount'         => $result['amount'],
				/* translators: %s: case number. */
				'reason'         => sprintf( __( 'Elállás: %s', 'elallas-for-woo' ), $case->case_number ),
				'order_id'       => $order->get_id(),
				'line_items'     => $result['line_items'],
				'refund_payment' => false,
				'restock_items'  => false,
			]
		);

		if ( is_wp_error( $refund ) ) {
			return $refund;
		}

		$refund->update_meta_data( RefundLines::CASE_META, (string) $case->id );
		$refund->save();

		$restocked = self::restock( $order, $lines, $result['line_items'], $restock );
		self::log( $case, $refund, $result, $restocked, $actor_id );

		if ( $close && CaseStatus::CLOSED !== $case->status ) {
			( new CaseService() )->change_status( $case->id, CaseStatus::CLOSED, $actor_id );
		}

		return $refund->get_id();
	}

	/**
	 * Put the chosen, stock-reduced product lines back in stock.
	 *
	 * @param \WC_Order                        $order      Order.
	 * @param array<int, array<string, mixed>> $lines      Calculator lines.
	 * @param array<int, array<string, mixed>> $line_items Refunded line items.
	 * @param array<int, int>                  $restock    Chosen order item IDs.
	 * @return array<int, int> Order item ID => restocked quantity.
	 */
	private static function restock( \WC_Order $order, array $lines, array $line_items, array $restock ): array {
		$chosen = [];

		foreach ( $lines as $line ) {
			$id = (int) $line['item_id'];

			if ( ! empty( $line['restockable'] ) && in_array( $id, $restock, true ) && ! empty( $line_items[ $id ]['qty'] ) ) {
				$chosen[ $id ] = [ 'qty' => (int) $line_items[ $id ]['qty'] ];
			}
		}

		if ( [] !== $chosen ) {
			wc_restock_refunded_items( $order, $chosen );
		}

		return array_map( static fn( array $row ): int => $row['qty'], $chosen );
	}

	/**
	 * Audit event for the refund.
	 *
	 * @param WithdrawalCase       $case      Case.
	 * @param \WC_Order_Refund     $refund    Refund.
	 * @param array<string, mixed> $result    Calculator result.
	 * @param array<int, int>      $restocked Restocked quantities.
	 * @param int                  $actor_id  Admin user ID.
	 * @return void
	 */
	private static function log( WithdrawalCase $case, \WC_Order_Refund $refund, array $result, array $restocked, int $actor_id ): void {
		EventRepository::log(
			$case->id,
			'refund_recorded',
			'admin',
			$actor_id,
			sprintf(
				/* translators: 1: refund amount, 2: refund ID. */
				__( 'Visszatérítés rögzítve a WooCommerce-ben: %1$s (#%2$d).', 'elallas-for-woo' ),
				html_entity_decode( wp_strip_all_tags( wc_price( (float) $result['amount'] ) ), ENT_QUOTES, 'UTF-8' ),
				$refund->get_id()
			),
			[
				'refund_id' => $refund->get_id(),
				'amount'    => $result['amount'],
				'items'     => array_map( static fn( array $row ): int => (int) $row['qty'], $result['line_items'] ),
				'restocked' => $restocked,
			]
		);
	}
}

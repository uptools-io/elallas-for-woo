<?php
/**
 * Eligibility checker.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Domain;

use LightweightPlugins\Elallas\Options;
use LightweightPlugins\Elallas\Woo\OrderAdapter;
use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Models\DeadlineStatus;

/**
 * Decides whether an order may start a withdrawal — and flags the deadline.
 *
 * Never hard-blocks on the deadline: an expired/unknown deadline is surfaced
 * for manual review (per 415/2025 and the project's compliance design).
 */
final class EligibilityChecker {

	/**
	 * Check an order against the supplied email.
	 *
	 * @param \WC_Order $order Order.
	 * @param string    $email Candidate email.
	 * @return EligibilityResult
	 */
	public function check( \WC_Order $order, string $email ): EligibilityResult {
		$reasons         = [];
		$deadline_status = $this->deadline_status( $order );

		if ( ! OrderAdapter::email_matches( $order, $email ) ) {
			$reasons[] = __( 'Az e-mail cím nem egyezik a rendeléssel.', 'elallas-for-woo' );
		}

		if ( ! $this->status_allows( $order ) ) {
			$reasons[] = __( 'A rendelés státusza nem alkalmas elállásra.', 'elallas-for-woo' );
		}

		if ( CaseRepository::exists_open_for_order( $order->get_id() ) ) {
			$reasons[] = __( 'A rendeléshez már tartozik folyamatban lévő elállási ügy.', 'elallas-for-woo' );
		}

		// Deadline handling: 'block' refuses expired orders; other modes flag for manual review.
		if ( DeadlineStatus::EXPIRED === $deadline_status && 'block' === Options::get( 'expired_handling' ) ) {
			$reasons[] = __( 'Az elállási határidő lejárt.', 'elallas-for-woo' );
		}

		$eligible = empty( $reasons );

		/**
		 * Filter the final eligibility decision.
		 *
		 * @param bool      $eligible Whether withdrawal may proceed.
		 * @param \WC_Order $order    Order.
		 */
		$eligible = (bool) apply_filters( 'elallas_is_order_eligible', $eligible, $order );

		return $eligible
			? EligibilityResult::allow( $deadline_status )
			: EligibilityResult::deny( $reasons, $deadline_status );
	}

	/**
	 * Whether the order status is in the configured eligible list.
	 *
	 * @param \WC_Order $order Order.
	 * @return bool
	 */
	private function status_allows( \WC_Order $order ): bool {
		$allowed = (array) Options::get( 'eligible_statuses', [ 'processing', 'completed' ] );

		return in_array( $order->get_status(), $allowed, true );
	}

	/**
	 * Compute the deadline status for an order.
	 *
	 * @param \WC_Order $order Order.
	 * @return string DeadlineStatus constant.
	 */
	private function deadline_status( \WC_Order $order ): string {
		$dates  = OrderAdapter::dates( $order );
		$source = (string) Options::get( 'deadline_start', 'order_completed' );
		$days   = (int) apply_filters( 'elallas_deadline_days', (int) Options::get( 'deadline_days', 14 ), $order );
		$start  = DeadlineCalculator::resolve_start( $dates, $source );

		return DeadlineCalculator::status_for( $start, $days );
	}
}

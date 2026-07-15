<?php
/**
 * Processes each step of the withdrawal flow.
 *
 * Eligibility (incl. email match) is re-validated on every step, so tampering
 * with the carried order number requires the matching email.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Frontend;

use LightweightPlugins\Elallas\Options;
use LightweightPlugins\Elallas\Domain\CaseService;
use LightweightPlugins\Elallas\Domain\EligibilityChecker;
use LightweightPlugins\Elallas\Domain\EligibilityResult;
use LightweightPlugins\Elallas\Domain\OrderSnapshotBuilder;
use LightweightPlugins\Elallas\Domain\ProductExclusion;
use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Security\RateLimiter;
use LightweightPlugins\Elallas\Woo\OrderAdapter;
use LightweightPlugins\Elallas\Data\DefaultTexts;
use LightweightPlugins\Elallas\Integrations\Multilingual;

/**
 * Builds the view descriptor for each step of the flow.
 */
final class StepProcessor {

	/**
	 * Step 1 -> 2: identify the order.
	 *
	 * @return array{view: string, data: array<string, mixed>}
	 */
	public function identify(): array {
		if ( RateLimiter::too_many( 'identify' ) ) {
			return $this->denied();
		}

		[ $order, $email, $order_number, $result ] = $this->revalidate();

		if ( null === $order ) {
			return $this->denied();
		}

		// Throttle per resolved order ID (not the raw, spoofable number string).
		if ( RateLimiter::too_many_global( 'order_' . $order->get_id() ) ) {
			return $this->denied();
		}

		$enforce = ProductExclusion::is_enforced( $order );
		$items   = $enforce ? $this->decorate_items( OrderAdapter::items( $order ) ) : OrderAdapter::items( $order );

		// Whole order excepted: nothing is withdrawable — explain why instead of
		// showing an item selector the customer could never submit.
		if ( $enforce && $this->all_excluded( $items ) ) {
			return $this->excluded_view( $this->normalize_excluded_items( $items ) );
		}

		return $this->view(
			'select',
			[
				'order'           => $order,
				'order_number'    => $order_number,
				'email'           => $email,
				'items'           => $items,
				'deadline_status' => $result->deadline_status,
			]
		);
	}

	/**
	 * Step 2 -> 3: build the confirmation summary.
	 *
	 * @return array{view: string, data: array<string, mixed>}
	 */
	public function select(): array {
		[ $order, $email, $order_number, $result ] = $this->revalidate();

		if ( null === $order ) {
			return $this->denied();
		}

		$selected = FormRequest::selected_items();
		$rows     = OrderSnapshotBuilder::build( $order, $selected );
		$enforce  = ProductExclusion::is_enforced( $order );
		$eligible = $enforce ? OrderSnapshotBuilder::partition( $rows )['eligible'] : $rows;

		// Only excepted (or no) items selected: keep them on the selector with a hint.
		if ( empty( $eligible ) ) {
			return $this->view(
				'select',
				[
					'order'           => $order,
					'order_number'    => $order_number,
					'email'           => $email,
					'items'           => $enforce ? $this->decorate_items( OrderAdapter::items( $order ) ) : OrderAdapter::items( $order ),
					'deadline_status' => $result->deadline_status,
					'error'           => __( 'Kérjük, válasszon legalább egy elállásra jogosult terméket.', 'elallas-for-woo' ),
				]
			);
		}

		return $this->view( 'confirm', $this->confirm_view_data( $order, $order_number, $email, $eligible, $selected, $result->deadline_status ) );
	}

	/**
	 * Step 3: create + confirm the case.
	 *
	 * @return array{view: string, data: array<string, mixed>}
	 */
	public function confirm(): array {
		if ( RateLimiter::too_many( 'confirm' ) ) {
			return $this->denied();
		}

		[ $order, $email, $order_number, $result ] = $this->revalidate();

		if ( null === $order ) {
			return $this->denied();
		}

		// Throttle per resolved order ID (not the raw, spoofable number string).
		if ( RateLimiter::too_many_global( 'order_' . $order->get_id() ) ) {
			return $this->denied();
		}

		$selected = FormRequest::selected_items();
		$rows     = OrderSnapshotBuilder::build( $order, $selected );
		$enforce  = ProductExclusion::is_enforced( $order );
		$parts    = OrderSnapshotBuilder::partition( $rows );
		$eligible = $enforce ? $parts['eligible'] : $rows;
		$excepted = $enforce ? $parts['excepted'] : [];

		// Backstop: no withdrawable item remains, but an excepted one slipped
		// through (e.g. a tampered submission bypassing the front-end gate).
		// Record the case and refuse it with the reason instead of auto-confirming.
		if ( empty( $eligible ) ) {
			if ( ! empty( $excepted ) ) {
				return $this->reject_excluded( $order, $email, $excepted, $result->deadline_status );
			}

			return $this->denied();
		}

		if ( ! FormRequest::consent_given() ) {
			$data          = $this->confirm_view_data( $order, $order_number, $email, $eligible, $selected, $result->deadline_status );
			$data['error'] = __( 'A folytatáshoz minden nyilatkozatot el kell fogadnia.', 'elallas-for-woo' );
			return $this->view( 'confirm', $data );
		}

		$type    = OrderSnapshotBuilder::is_full( $eligible, $order ) ? 'full' : 'partial';
		$context = SubmissionContext::build( $email, $type, $result->deadline_status, FormRequest::note(), FormRequest::bank_account() );
		$service = new CaseService();
		$case_id = $service->create( $order, $eligible, $context );

		if ( 0 === $case_id ) {
			return $this->denied();
		}

		$service->confirm( $case_id );

		return $this->view( 'success', [ 'case' => CaseRepository::find( $case_id ) ] );
	}

	/**
	 * Re-validate the carried order + email.
	 *
	 * @return array{0: ?\WC_Order, 1: string, 2: string, 3: ?EligibilityResult}
	 */
	private function revalidate(): array {
		$order_number = FormRequest::order_number();
		$email        = FormRequest::email();
		$order        = OrderAdapter::get_order_by_number( $order_number );
		$result       = $order ? ( new EligibilityChecker() )->check( $order, $email ) : null;

		if ( null === $order || ! $result instanceof EligibilityResult || ! $result->eligible ) {
			return [ null, $email, $order_number, null ];
		}

		return [ $order, $email, $order_number, $result ];
	}

	/**
	 * Data for the confirmation view.
	 *
	 * @param \WC_Order        $order           Order.
	 * @param string           $order_number    Order number.
	 * @param string           $email           Email.
	 * @param array<int,mixed> $rows            Snapshot rows.
	 * @param array<int,int>   $selected        Selected map.
	 * @param string           $deadline_status Deadline status.
	 * @return array<string, mixed>
	 */
	private function confirm_view_data( \WC_Order $order, string $order_number, string $email, array $rows, array $selected, string $deadline_status ): array {
		return [
			'order'           => $order,
			'order_number'    => $order_number,
			'email'           => $email,
			'rows'            => $rows,
			'selected'        => $selected,
			'withdrawal_type' => OrderSnapshotBuilder::is_full( $rows, $order ) ? 'full' : 'partial',
			'deadline_status' => $deadline_status,
			'declaration'     => Multilingual::translate_option_string( 'legal_declaration' ),
			'confirm_label'   => Multilingual::translate_option_string( 'confirm_label' ),
		];
	}

	/**
	 * Annotate order line items with their exclusion state for the selector view.
	 *
	 * @param array<int, array<string, mixed>> $items Order line items.
	 * @return array<int, array<string, mixed>>
	 */
	private function decorate_items( array $items ): array {
		foreach ( $items as $index => $item ) {
			[ $excluded, $reason ] = ProductExclusion::evaluate( (int) ( $item['product_id'] ?? 0 ) );

			$items[ $index ]['excluded']         = $excluded;
			$items[ $index ]['exclusion_reason'] = $reason;
		}

		return $items;
	}

	/**
	 * Whether every line item is excepted from withdrawal.
	 *
	 * @param array<int, array<string, mixed>> $items Decorated line items.
	 * @return bool
	 */
	private function all_excluded( array $items ): bool {
		if ( empty( $items ) ) {
			return false;
		}

		foreach ( $items as $item ) {
			if ( empty( $item['excluded'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Normalize decorated line items into the excluded-view shape.
	 *
	 * @param array<int, array<string, mixed>> $items Decorated line items.
	 * @return array<int, array{name: string, reason: string}>
	 */
	private function normalize_excluded_items( array $items ): array {
		$out = [];

		foreach ( $items as $item ) {
			if ( empty( $item['excluded'] ) ) {
				continue;
			}

			$out[] = [
				'name'   => (string) ( $item['product_name_snapshot'] ?? '' ),
				'reason' => (string) ( $item['exclusion_reason'] ?? '' ),
			];
		}

		return $out;
	}

	/**
	 * Normalize excepted snapshot rows into the excluded-view shape.
	 *
	 * @param array<int, array<string, mixed>> $rows Excepted snapshot rows.
	 * @return array<int, array{name: string, reason: string}>
	 */
	private function normalize_excepted_rows( array $rows ): array {
		$out = [];

		foreach ( $rows as $row ) {
			$out[] = [
				'name'   => (string) ( $row['product_name_snapshot'] ?? '' ),
				'reason' => (string) ( $row['eligibility_note'] ?? '' ),
			];
		}

		return $out;
	}

	/**
	 * Backstop for a submission that reached confirmation with only excepted items:
	 * record the case and refuse it with the exclusion reason (status e-mail).
	 *
	 * @param \WC_Order                        $order           Order.
	 * @param string                           $email           Carried email.
	 * @param array<int, array<string, mixed>> $excepted        Excepted snapshot rows.
	 * @param string                           $deadline_status Deadline status.
	 * @return array{view: string, data: array<string, mixed>}
	 */
	private function reject_excluded( \WC_Order $order, string $email, array $excepted, string $deadline_status ): array {
		// Bound the backstop: a rejected case is terminal (does not count as "open"),
		// so without this guard every repeat submission would create another case and
		// re-send the customer a rejection e-mail. Record + notify at most once per order.
		if ( ! empty( CaseRepository::find_by_order( $order->get_id() ) ) ) {
			return $this->excluded_view( $this->normalize_excepted_rows( $excepted ) );
		}

		$context = SubmissionContext::build( $email, 'full', $deadline_status, FormRequest::note(), FormRequest::bank_account() );
		$service = new CaseService();
		$case_id = $service->create( $order, $excepted, $context );
		$emailed = false;

		if ( $case_id > 0 ) {
			$message = trim(
				__( 'Az elállási kérelmét nem tudjuk teljesíteni, mert az érintett termék(ek)re a jogszabály szerint nem gyakorolható elállási jog:', 'elallas-for-woo' )
				. "\n\n" . ProductExclusion::summarize( $excepted )
			);

			/**
			 * Filter the customer-facing rejection message for an excluded-item withdrawal.
			 *
			 * @since 1.0.13
			 * @param string                           $message  Default message (incl. the reason summary).
			 * @param array<int, array<string, mixed>> $excepted Excepted snapshot rows.
			 * @param \WC_Order                        $order    Order.
			 */
			$message = (string) apply_filters( 'elallas_exclusion_reason_message', $message, $excepted, $order );

			$service->reject( $case_id, $message );
			$emailed = (bool) Options::get( 'email_status_enabled' );
		}

		return $this->excluded_view( $this->normalize_excepted_rows( $excepted ), $emailed );
	}

	/**
	 * Build the "cannot withdraw — excluded products" view descriptor.
	 *
	 * @param array<int, array{name: string, reason: string}> $items   Excluded products with reasons.
	 * @param bool                                             $emailed Whether a rejection e-mail was sent.
	 * @return array{view: string, data: array<string, mixed>}
	 */
	private function excluded_view( array $items, bool $emailed = false ): array {
		return $this->view( 'excluded', [ 'items' => $items, 'emailed' => $emailed ] );
	}

	/**
	 * Neutral "not found / not eligible" view.
	 *
	 * @return array{view: string, data: array<string, mixed>}
	 */
	private function denied(): array {
		return $this->view( 'denied', [ 'message' => DefaultTexts::neutral_error() ] );
	}

	/**
	 * Build a view descriptor.
	 *
	 * @param string               $view View slug.
	 * @param array<string, mixed> $data View data.
	 * @return array{view: string, data: array<string, mixed>}
	 */
	private function view( string $view, array $data = [] ): array {
		return [ 'view' => $view, 'data' => $data ];
	}
}

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

use LightweightPlugins\Elallas\Domain\CaseService;
use LightweightPlugins\Elallas\Domain\EligibilityChecker;
use LightweightPlugins\Elallas\Domain\EligibilityResult;
use LightweightPlugins\Elallas\Domain\OrderSnapshotBuilder;
use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Security\RateLimiter;
use LightweightPlugins\Elallas\Woo\OrderAdapter;
use LightweightPlugins\Elallas\Options;
use LightweightPlugins\Elallas\Data\DefaultTexts;

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

		return $this->view(
			'select',
			[
				'order'           => $order,
				'order_number'    => $order_number,
				'email'           => $email,
				'items'           => OrderAdapter::items( $order ),
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

		if ( empty( $rows ) ) {
			return $this->view(
				'select',
				[
					'order'           => $order,
					'order_number'    => $order_number,
					'email'           => $email,
					'items'           => OrderAdapter::items( $order ),
					'deadline_status' => $result->deadline_status,
					'error'           => __( 'Kérjük, válasszon legalább egy terméket.', 'elallas-for-woo' ),
				]
			);
		}

		return $this->view( 'confirm', $this->confirm_view_data( $order, $order_number, $email, $rows, $selected, $result->deadline_status ) );
	}

	/**
	 * Step 3: create + confirm the case.
	 *
	 * @return array{view: string, data: array<string, mixed>}
	 */
	public function confirm(): array {
		[ $order, $email, $order_number, $result ] = $this->revalidate();

		if ( null === $order ) {
			return $this->denied();
		}

		$selected = FormRequest::selected_items();
		$rows     = OrderSnapshotBuilder::build( $order, $selected );

		if ( empty( $rows ) ) {
			return $this->denied();
		}

		if ( ! FormRequest::consent_given() ) {
			$data          = $this->confirm_view_data( $order, $order_number, $email, $rows, $selected, $result->deadline_status );
			$data['error'] = __( 'A folytatáshoz minden nyilatkozatot el kell fogadnia.', 'elallas-for-woo' );
			return $this->view( 'confirm', $data );
		}

		$type    = count( $rows ) === count( OrderAdapter::items( $order ) ) ? 'full' : 'partial';
		$context = SubmissionContext::build( $email, $type, $result->deadline_status, FormRequest::note() );
		$service = new CaseService();
		$case_id = $service->create( $order, $rows, $context );

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
			'withdrawal_type' => count( $rows ) === count( OrderAdapter::items( $order ) ) ? 'full' : 'partial',
			'deadline_status' => $deadline_status,
			'declaration'     => (string) Options::get( 'legal_declaration', DefaultTexts::declaration() ),
		];
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

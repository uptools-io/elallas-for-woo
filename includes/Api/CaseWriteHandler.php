<?php
/**
 * Case write handler (create + confirm).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Api;

use LightweightPlugins\Elallas\Options;
use LightweightPlugins\Elallas\Data\DefaultTexts;
use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Domain\CaseService;
use LightweightPlugins\Elallas\Domain\EligibilityChecker;
use LightweightPlugins\Elallas\Domain\EligibilityResult;
use LightweightPlugins\Elallas\Domain\OrderSnapshotBuilder;
use LightweightPlugins\Elallas\Domain\ProductExclusion;
use LightweightPlugins\Elallas\Frontend\SubmissionContext;
use LightweightPlugins\Elallas\Security\RateLimiter;
use LightweightPlugins\Elallas\Woo\OrderAdapter;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Encapsulates the validated create/confirm flow so REST behaves identically
 * to the front-end form (same eligibility, snapshot and submission context).
 */
final class CaseWriteHandler {

	/**
	 * Create (and confirm) a withdrawal case from a request.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function create( WP_REST_Request $request ): WP_REST_Response {
		$order_number = sanitize_text_field( (string) $request->get_param( 'order_number' ) );

		if ( ! Options::get( 'enabled' ) || RateLimiter::too_many( 'rest_cases_create' ) ) {
			return $this->neutral();
		}

		$email  = sanitize_email( (string) $request->get_param( 'email' ) );
		$order  = OrderAdapter::get_order_by_number( $order_number );
		$result = $order ? ( new EligibilityChecker() )->check( $order, $email ) : null;

		if ( null === $order || ! $result instanceof EligibilityResult || ! $result->eligible ) {
			return $this->neutral();
		}

		// Throttle per resolved order ID (not the raw, spoofable number string).
		if ( RateLimiter::too_many_global( 'order_' . $order->get_id() ) ) {
			return $this->neutral();
		}

		if ( true !== rest_sanitize_boolean( $request->get_param( 'consent' ) ) ) {
			return $this->neutral();
		}

		$rows = OrderSnapshotBuilder::build( $order, $this->selected_items( $request ) );

		if ( empty( $rows ) ) {
			return $this->neutral();
		}

		$enforce  = ProductExclusion::is_enforced( $order );
		$parts    = OrderSnapshotBuilder::partition( $rows );
		$eligible = $enforce ? $parts['eligible'] : $rows;
		$excepted = $enforce ? $parts['excepted'] : [];

		// Excepted-only submission: create + refuse with the reason, never
		// auto-confirm. Mirrors the front-end exclusion backstop.
		if ( empty( $eligible ) ) {
			if ( ! empty( $excepted ) ) {
				return $this->reject_excluded( $request, $order, $result, $excepted );
			}

			return $this->neutral();
		}

		return $this->persist( $request, $order, $result, $eligible );
	}

	/**
	 * Record and immediately refuse a submission whose items are all excepted.
	 *
	 * @param WP_REST_Request                 $request  Request object.
	 * @param \WC_Order                        $order    Order.
	 * @param EligibilityResult                $result   Eligibility result.
	 * @param array<int, array<string, mixed>> $excepted Excepted snapshot rows.
	 * @return WP_REST_Response
	 */
	private function reject_excluded( WP_REST_Request $request, \WC_Order $order, EligibilityResult $result, array $excepted ): WP_REST_Response {
		$summary = ProductExclusion::summarize( $excepted );

		// Bound the backstop: record + notify at most once per order (a rejected
		// case is terminal, so it would otherwise re-fire on every repeat submission).
		if ( ! empty( CaseRepository::find_by_order( $order->get_id() ) ) ) {
			return new WP_REST_Response(
				[
					'success'  => false,
					'excluded' => true,
					'message'  => $summary,
				],
				422
			);
		}

		$email   = sanitize_email( (string) $request->get_param( 'email' ) );
		$note    = sanitize_textarea_field( (string) $request->get_param( 'customer_note' ) );
		$bank    = sanitize_text_field( (string) $request->get_param( 'bank_account' ) );
		$context = SubmissionContext::build( $email, 'full', $result->deadline_status, $note, $bank );
		$service = new CaseService();
		$case_id = $service->create( $order, $excepted, $context );

		if ( $case_id > 0 ) {
			$message = trim(
				__( 'Az elállási kérelmét nem tudjuk teljesíteni, mert az érintett termék(ek)re a jogszabály szerint nem gyakorolható elállási jog:', 'elallas-for-woo' )
				. "\n\n" . $summary
			);

			/** This filter is documented in includes/Frontend/StepProcessor.php */
			$message = (string) apply_filters( 'elallas_exclusion_reason_message', $message, $excepted, $order );

			$service->reject( $case_id, $message );
		}

		return new WP_REST_Response(
			[
				'success'  => false,
				'excluded' => true,
				'message'  => $summary,
			],
			422
		);
	}

	/**
	 * Build the context, create + confirm the case, and shape the response.
	 *
	 * @param WP_REST_Request                 $request Request object.
	 * @param \WC_Order                        $order   Order.
	 * @param EligibilityResult                $result  Eligibility result.
	 * @param array<int, array<string, mixed>> $rows    Snapshot rows.
	 * @return WP_REST_Response
	 */
	private function persist( WP_REST_Request $request, \WC_Order $order, EligibilityResult $result, array $rows ): WP_REST_Response {
		$type    = OrderSnapshotBuilder::is_full( $rows, $order ) ? 'full' : 'partial';
		$email   = sanitize_email( (string) $request->get_param( 'email' ) );
		$note    = sanitize_textarea_field( (string) $request->get_param( 'customer_note' ) );
		$bank    = sanitize_text_field( (string) $request->get_param( 'bank_account' ) );
		$context = SubmissionContext::build( $email, $type, $result->deadline_status, $note, $bank );
		$service = new CaseService();
		$case_id = $service->create( $order, $rows, $context );

		if ( 0 === $case_id ) {
			return $this->neutral();
		}

		$service->confirm( $case_id );
		$case = CaseRepository::find( $case_id );

		return new WP_REST_Response(
			[
				'success'     => true,
				'case_number' => $case ? $case->case_number : '',
				'case_id'     => $case_id,
			],
			201
		);
	}

	/**
	 * Confirm an existing case after re-validating the carried email.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function confirm( WP_REST_Request $request ): WP_REST_Response {
		if ( RateLimiter::too_many( 'rest_confirm' ) ) {
			return $this->neutral();
		}

		$case = CaseRepository::find( (int) $request['id'] );

		if ( null === $case ) {
			return $this->neutral();
		}

		if ( RateLimiter::too_many_global( 'order_' . $case->order_id ) ) {
			return $this->neutral();
		}

		$email = sanitize_email( (string) $request->get_param( 'email' ) );
		$order = OrderAdapter::get_order( $case->order_id );

		if ( null === $order || ! OrderAdapter::email_matches( $order, $email ) ) {
			return $this->neutral();
		}

		$confirmed = ( new CaseService() )->confirm( $case->id );

		return new WP_REST_Response(
			[
				'success'     => $confirmed,
				'case_number' => $case->case_number,
				'case_id'     => $case->id,
			],
			$confirmed ? 200 : 422
		);
	}

	/**
	 * Selected items map from request: order_item_id => qty.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array<int, int>
	 */
	private function selected_items( WP_REST_Request $request ): array {
		$raw      = (array) $request->get_param( 'items' );
		$selected = [];

		foreach ( $raw as $item_id => $qty ) {
			$qty = (int) $qty;
			if ( $qty > 0 ) {
				$selected[ (int) $item_id ] = $qty;
			}
		}

		return $selected;
	}

	/**
	 * Neutral failure response (never reveals which field was wrong).
	 *
	 * @return WP_REST_Response
	 */
	private function neutral(): WP_REST_Response {
		return new WP_REST_Response(
			[
				'success' => false,
				'message' => DefaultTexts::neutral_error(),
			],
			422
		);
	}
}

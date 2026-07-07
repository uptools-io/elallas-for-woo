<?php
/**
 * Case service — orchestrates case creation and lifecycle.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Domain;

use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Database\CaseItemRepository;
use LightweightPlugins\Elallas\Database\EventRepository;
use LightweightPlugins\Elallas\Models\CaseStatus;
use LightweightPlugins\Elallas\Models\DeadlineStatus;
use LightweightPlugins\Elallas\Woo\OrderAdapter;
use LightweightPlugins\Elallas\Support\Logger;

/**
 * Creates withdrawal cases and manages their lifecycle.
 */
final class CaseService {

	/**
	 * Create a withdrawal case from a (validated) request.
	 *
	 * @param \WC_Order                        $order    Order.
	 * @param array<int, array<string, mixed>> $items    Snapshot rows from OrderSnapshotBuilder.
	 * @param array<string, mixed>             $context  Prepared context (privacy fields already hashed/encrypted).
	 * @return int Case ID (0 on failure).
	 */
	public function create( \WC_Order $order, array $items, array $context ): int {
		$dates           = OrderAdapter::dates( $order );
		$deadline_status = (string) ( $context['deadline_status'] ?? DeadlineStatus::UNKNOWN );
		$now             = current_time( 'mysql' );

		$case_id = CaseRepository::insert(
			[
				'case_number'              => CaseNumberGenerator::generate(),
				'order_id'                 => $order->get_id(),
				'order_number'             => $order->get_order_number(),
				'customer_id'              => (int) $order->get_customer_id(),
				'customer_email_hash'      => (string) ( $context['email_hash'] ?? '' ),
				'customer_email_encrypted' => $context['email_encrypted'] ?? null,
				'status'                   => CaseStatus::RECEIVED,
				'withdrawal_type'          => (string) ( $context['withdrawal_type'] ?? 'full' ),
				'submitted_at'             => $now,
				'deadline_status'          => $deadline_status,
				'order_created_at'         => $dates['created'],
				'order_completed_at'       => $dates['completed'],
				'delivery_date'            => $dates['delivery'],
				'ip_hash'                  => (string) ( $context['ip_hash'] ?? '' ),
				'user_agent_hash'          => (string) ( $context['user_agent_hash'] ?? '' ),
				'source_url'               => (string) ( $context['source_url'] ?? '' ),
				'language'                 => (string) ( $context['language'] ?? '' ),
				'customer_note'            => $context['customer_note'] ?? null,
				'bank_account_encrypted'   => $context['bank_account_encrypted'] ?? null,
			]
		);

		if ( 0 === $case_id ) {
			Logger::error(
				'Elállási ügy létrehozása sikertelen (adatbázis-beszúrás).',
				[
					'order_id'     => $order->get_id(),
					'order_number' => $order->get_order_number(),
				]
			);
			return 0;
		}

		CaseItemRepository::bulk_insert( $case_id, $items );
		$this->update_order_meta( $order, $case_id, $deadline_status );
		EventRepository::log( $case_id, 'case_created', 'customer', $order->get_customer_id() ?: null, __( 'Elállási nyilatkozat beérkezett.', 'elallas-for-woo' ) );

		/**
		 * Fires after a withdrawal case is created.
		 *
		 * @param int $case_id  Case ID.
		 * @param int $order_id Order ID.
		 */
		do_action( 'elallas_case_created', $case_id, $order->get_id() );

		Logger::info(
			'Elállási ügy létrehozva.',
			[
				'case_id'      => $case_id,
				'order_id'     => $order->get_id(),
				'order_number' => $order->get_order_number(),
				'type'         => (string) ( $context['withdrawal_type'] ?? 'full' ),
				'deadline'     => $deadline_status,
			]
		);

		return $case_id;
	}

	/**
	 * Confirm a case (second step of the two-step flow).
	 *
	 * @param int $case_id Case ID.
	 * @return bool
	 */
	public function confirm( int $case_id ): bool {
		$case = CaseRepository::find( $case_id );

		if ( null === $case ) {
			return false;
		}

		$status = DeadlineStatus::WITHIN === $case->deadline_status
			? CaseStatus::AUTO_CONFIRMED
			: CaseStatus::MANUAL_REVIEW;

		CaseRepository::update( $case_id, [ 'status' => $status, 'confirmed_at' => current_time( 'mysql' ) ] );
		EventRepository::log( $case_id, 'case_confirmed', 'customer', $case->customer_id ?: null, __( 'A fogyasztó megerősítette a nyilatkozatot.', 'elallas-for-woo' ) );

		/**
		 * Fires after a case is confirmed by the customer.
		 *
		 * @param int $case_id Case ID.
		 */
		do_action( 'elallas_case_confirmed', $case_id );

		Logger::info( 'Elállási ügy megerősítve.', [ 'case_id' => $case_id, 'status' => $status ] );

		return true;
	}

	/**
	 * Change a case's status (admin action).
	 *
	 * @param int      $case_id    Case ID.
	 * @param string   $new_status New status.
	 * @param int|null $actor_id   Acting admin user ID.
	 * @param string   $message    Optional note to the customer, included in the status e-mail.
	 * @return bool
	 */
	public function change_status( int $case_id, string $new_status, ?int $actor_id = null, string $message = '' ): bool {
		if ( ! CaseStatus::is_valid( $new_status ) ) {
			return false;
		}

		$case = CaseRepository::find( $case_id );

		if ( null === $case || $case->status === $new_status ) {
			return false;
		}

		$old_status = $case->status;
		CaseRepository::update_status( $case_id, $new_status );

		$log = sprintf(
			/* translators: 1: old status label, 2: new status label. */
			__( 'Státusz módosítva: %1$s → %2$s', 'elallas-for-woo' ),
			CaseStatus::label( $old_status ),
			CaseStatus::label( $new_status )
		);

		if ( '' !== $message ) {
			/* translators: %s: admin note sent to the customer. */
			$log .= ' — ' . sprintf( __( 'Üzenet a vásárlónak: %s', 'elallas-for-woo' ), $message );
		}

		EventRepository::log(
			$case_id,
			'status_changed',
			null === $actor_id ? 'system' : 'admin',
			$actor_id,
			$log
		);

		/**
		 * Fires after a case status changes.
		 *
		 * @param int    $case_id    Case ID.
		 * @param string $old_status Previous status.
		 * @param string $new_status New status.
		 * @param string $message    Optional note to the customer.
		 */
		do_action( 'elallas_case_status_changed', $case_id, $old_status, $new_status, $message );

		Logger::info(
			'Elállási ügy státusza módosítva.',
			[
				'case_id'  => $case_id,
				'from'     => $old_status,
				'to'       => $new_status,
				'actor_id' => $actor_id,
			]
		);

		return true;
	}

	/**
	 * Update order meta to reflect the case.
	 *
	 * @param \WC_Order $order           Order.
	 * @param int       $case_id         Case ID.
	 * @param string    $deadline_status Deadline status.
	 * @return void
	 */
	private function update_order_meta( \WC_Order $order, int $case_id, string $deadline_status ): void {
		$case_ids   = (array) $order->get_meta( '_lw_elallas_case_ids' );
		$case_ids[] = $case_id;

		$order->update_meta_data( '_lw_elallas_has_case', 'yes' );
		$order->update_meta_data( '_lw_elallas_case_ids', array_values( array_unique( array_map( 'intval', $case_ids ) ) ) );
		$order->update_meta_data( '_lw_elallas_deadline_status', $deadline_status );
		$order->add_order_note( __( 'Elállási nyilatkozat érkezett (Elállás for WooCommerce).', 'elallas-for-woo' ) );
		$order->save();
	}
}

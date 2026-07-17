<?php
/**
 * Withdrawal case entity (DTO).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Models;

/**
 * Typed representation of a row in the cases table.
 */
final class WithdrawalCase {

	/**
	 * Case row primary key.
	 *
	 * @var int
	 */
	public int $id = 0;

	/**
	 * Human-readable case number.
	 *
	 * @var string
	 */
	public string $case_number = '';

	/**
	 * Related WooCommerce order ID.
	 *
	 * @var int
	 */
	public int $order_id = 0;

	/**
	 * Related WooCommerce order number.
	 *
	 * @var string
	 */
	public string $order_number = '';

	/**
	 * Related customer user ID.
	 *
	 * @var int
	 */
	public int $customer_id = 0;

	/**
	 * Hash of the customer email address.
	 *
	 * @var string
	 */
	public string $customer_email_hash = '';

	/**
	 * Encrypted customer email address, if stored.
	 *
	 * @var string|null
	 */
	public ?string $customer_email_encrypted = null;

	/**
	 * Current case status.
	 *
	 * @var string
	 */
	public string $status = CaseStatus::RECEIVED;

	/**
	 * Withdrawal type (full or partial).
	 *
	 * @var string
	 */
	public string $withdrawal_type = 'full';

	/**
	 * Timestamp when the case was submitted.
	 *
	 * @var string|null
	 */
	public ?string $submitted_at = null;

	/**
	 * Timestamp when the case was confirmed.
	 *
	 * @var string|null
	 */
	public ?string $confirmed_at = null;

	/**
	 * Deadline status of the case.
	 *
	 * @var string
	 */
	public string $deadline_status = DeadlineStatus::UNKNOWN;

	/**
	 * Timestamp when the related order was created.
	 *
	 * @var string|null
	 */
	public ?string $order_created_at = null;

	/**
	 * Timestamp when the related order was completed.
	 *
	 * @var string|null
	 */
	public ?string $order_completed_at = null;

	/**
	 * Delivery date of the related order.
	 *
	 * @var string|null
	 */
	public ?string $delivery_date = null;

	/**
	 * Hash of the submitter IP address.
	 *
	 * @var string
	 */
	public string $ip_hash = '';

	/**
	 * Hash of the submitter user agent.
	 *
	 * @var string
	 */
	public string $user_agent_hash = '';

	/**
	 * URL the case was submitted from.
	 *
	 * @var string
	 */
	public string $source_url = '';

	/**
	 * Language code of the submission.
	 *
	 * @var string
	 */
	public string $language = '';

	/**
	 * ID of the admin assigned to the case.
	 *
	 * @var int
	 */
	public int $assigned_admin_id = 0;

	/**
	 * Note left by the customer.
	 *
	 * @var string|null
	 */
	public ?string $customer_note = null;

	/**
	 * Encrypted bank account details, if stored.
	 *
	 * @var string|null
	 */
	public ?string $bank_account_encrypted = null;

	/**
	 * Timestamp when the case row was created.
	 *
	 * @var string
	 */
	public string $created_at = '';

	/**
	 * Timestamp when the case row was last updated.
	 *
	 * @var string
	 */
	public string $updated_at = '';

	/**
	 * Build from a DB row.
	 *
	 * @param object|array<string, mixed> $row Database row.
	 * @return self
	 */
	public static function from_row( object|array $row ): self {
		$data     = (array) $row;
		$instance = new self();

		foreach ( $data as $key => $value ) {
			if ( ! property_exists( $instance, $key ) ) {
				continue;
			}

			if ( in_array( $key, [ 'id', 'order_id', 'customer_id', 'assigned_admin_id' ], true ) ) {
				$instance->$key = (int) $value;
			} else {
				$instance->$key = null === $value ? null : (string) $value;
			}
		}

		return $instance;
	}

	/**
	 * Status label.
	 *
	 * @return string
	 */
	public function status_label(): string {
		return CaseStatus::label( $this->status );
	}

	/**
	 * Deadline status label.
	 *
	 * @return string
	 */
	public function deadline_label(): string {
		return DeadlineStatus::label( $this->deadline_status );
	}

	/**
	 * Sample case with placeholder data for the WooCommerce email preview.
	 *
	 * @return self
	 */
	public static function sample(): self {
		$instance                  = new self();
		$instance->case_number     = 'EL-2026-000001';
		$instance->order_number    = '1001';
		$instance->status          = CaseStatus::RECEIVED;
		$instance->withdrawal_type = 'full';
		$instance->submitted_at    = '2026-06-19 10:00:00';
		$instance->deadline_status = DeadlineStatus::WITHIN;

		return $instance;
	}
}

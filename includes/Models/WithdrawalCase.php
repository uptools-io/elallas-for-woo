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

	public int $id                      = 0;
	public string $case_number          = '';
	public int $order_id                = 0;
	public string $order_number         = '';
	public int $customer_id             = 0;
	public string $customer_email_hash  = '';
	public ?string $customer_email_encrypted = null;
	public string $status               = CaseStatus::RECEIVED;
	public string $withdrawal_type      = 'full';
	public ?string $submitted_at        = null;
	public ?string $confirmed_at        = null;
	public string $deadline_status      = DeadlineStatus::UNKNOWN;
	public ?string $order_created_at    = null;
	public ?string $order_completed_at  = null;
	public ?string $delivery_date       = null;
	public string $ip_hash              = '';
	public string $user_agent_hash      = '';
	public string $source_url           = '';
	public string $language             = '';
	public int $assigned_admin_id       = 0;
	public ?string $customer_note       = null;
	public string $created_at           = '';
	public string $updated_at           = '';

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
}

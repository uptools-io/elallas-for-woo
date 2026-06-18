<?php
/**
 * Eligibility result DTO.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Domain;

use LightweightPlugins\Elallas\Models\DeadlineStatus;

/**
 * Outcome of an eligibility check.
 */
final class EligibilityResult {

	/**
	 * Constructor.
	 *
	 * @param bool               $eligible        Whether withdrawal can proceed.
	 * @param string             $deadline_status DeadlineStatus constant.
	 * @param array<int, string> $reasons         Reasons (used when not eligible or flagged).
	 */
	public function __construct(
		public bool $eligible,
		public string $deadline_status = DeadlineStatus::UNKNOWN,
		public array $reasons = []
	) {}

	/**
	 * Build an eligible result.
	 *
	 * @param string $deadline_status DeadlineStatus constant.
	 * @return self
	 */
	public static function allow( string $deadline_status ): self {
		return new self( true, $deadline_status );
	}

	/**
	 * Build a not-eligible result.
	 *
	 * @param array<int, string> $reasons         Reasons.
	 * @param string             $deadline_status DeadlineStatus constant.
	 * @return self
	 */
	public static function deny( array $reasons, string $deadline_status = DeadlineStatus::UNKNOWN ): self {
		return new self( false, $deadline_status, $reasons );
	}
}

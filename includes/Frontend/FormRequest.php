<?php
/**
 * Parses and sanitizes the withdrawal form request.
 *
 * Centralizes all $_POST access for the front-end flow.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Frontend;

use LightweightPlugins\Elallas\Security\Honeypot;

/**
 * Read-only accessor for the withdrawal form POST data.
 */
final class FormRequest {

	private const NONCE_ACTION = 'elallas_form';

	/**
	 * Whether this is a withdrawal-form POST submission.
	 *
	 * @return bool
	 */
	public static function is_submission(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtolower( sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'get';

		return 'post' === $method && ! empty( $_POST['elallas_step'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Verify the form nonce.
	 *
	 * @return bool
	 */
	public static function verify_nonce(): bool {
		return isset( $_POST['elallas_nonce'] )
			&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['elallas_nonce'] ) ), self::NONCE_ACTION );
	}

	/**
	 * The requested step.
	 *
	 * @return string
	 */
	public static function step(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return isset( $_POST['elallas_step'] ) ? sanitize_key( wp_unslash( $_POST['elallas_step'] ) ) : '';
	}

	/**
	 * Carried order number.
	 *
	 * @return string
	 */
	public static function order_number(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return isset( $_POST['order_number'] ) ? sanitize_text_field( wp_unslash( $_POST['order_number'] ) ) : '';
	}

	/**
	 * Carried email.
	 *
	 * @return string
	 */
	public static function email(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	}

	/**
	 * Optional customer note.
	 *
	 * @return string
	 */
	public static function note(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return isset( $_POST['customer_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['customer_note'] ) ) : '';
	}

	/**
	 * Optional bank account / IBAN for the refund.
	 *
	 * @return string
	 */
	public static function bank_account(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return isset( $_POST['bank_account'] ) ? sanitize_text_field( wp_unslash( $_POST['bank_account'] ) ) : '';
	}

	/**
	 * Whether the honeypot field was filled (bot).
	 *
	 * @return bool
	 */
	public static function is_bot(): bool {
		return Honeypot::is_bot( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Selected items map: order_item_id => quantity (> 0 only).
	 *
	 * @return array<int, int>
	 */
	public static function selected_items(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified in FormHandler::handle() before this runs; each element is cast to int in the loop below.
		$raw      = isset( $_POST['items'] ) && is_array( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : [];
		$selected = [];

		foreach ( (array) $raw as $item_id => $qty ) {
			$qty = (int) $qty;
			if ( $qty > 0 ) {
				$selected[ (int) $item_id ] = $qty;
			}
		}

		return $selected;
	}

	/**
	 * Whether all three consent checkboxes were ticked.
	 *
	 * @return bool
	 */
	public static function consent_given(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return ! empty( $_POST['consent_data'] ) && ! empty( $_POST['consent_intent'] ) && ! empty( $_POST['consent_processing'] );
	}
}

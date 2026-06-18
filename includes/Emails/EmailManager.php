<?php
/**
 * Email Manager.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Emails;

use LightweightPlugins\Elallas\Options;

/**
 * Registers email classes with WooCommerce and triggers sends on domain events.
 */
final class EmailManager {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'woocommerce_email_classes', [ $this, 'register_emails' ] );
		add_action( 'elallas_case_confirmed', [ $this, 'on_confirmed' ] );
		add_action( 'elallas_case_status_changed', [ $this, 'on_status_changed' ], 10, 3 );
	}

	/**
	 * Register email classes with WooCommerce.
	 *
	 * @param array<string, \WC_Email> $emails WC email classes.
	 * @return array<string, \WC_Email>
	 */
	public function register_emails( array $emails ): array {
		$emails['Elallas_Customer_Confirmation'] = new CustomerConfirmation();
		$emails['Elallas_Admin_Notification']    = new AdminNotification();
		$emails['Elallas_Status_Update']         = new StatusUpdate();

		return $emails;
	}

	/**
	 * Handle a confirmed case: notify the customer and the admin.
	 *
	 * @param int $case_id Case ID.
	 * @return void
	 */
	public function on_confirmed( int $case_id ): void {
		$emails = \WC()->mailer()->get_emails();

		if ( Options::get( 'email_customer_enabled' ) && isset( $emails['Elallas_Customer_Confirmation'] ) ) {
			$emails['Elallas_Customer_Confirmation']->trigger( $case_id );
		}

		if ( Options::get( 'email_admin_enabled' ) && isset( $emails['Elallas_Admin_Notification'] ) ) {
			$emails['Elallas_Admin_Notification']->trigger( $case_id );
		}
	}

	/**
	 * Handle a status change: notify the customer.
	 *
	 * @param int    $case_id Case ID.
	 * @param string $old     Previous status.
	 * @param string $new     New status.
	 * @return void
	 */
	public function on_status_changed( int $case_id, string $old, string $new ): void {
		unset( $old, $new );

		if ( ! Options::get( 'email_status_enabled' ) ) {
			return;
		}

		$emails = \WC()->mailer()->get_emails();

		if ( isset( $emails['Elallas_Status_Update'] ) ) {
			$emails['Elallas_Status_Update']->trigger( $case_id );
		}
	}
}

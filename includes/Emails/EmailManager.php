<?php
/**
 * Email Manager.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Emails;

use LightweightPlugins\Elallas\Options;
use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Integrations\Multilingual;
use LightweightPlugins\Elallas\Support\Logger;

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
		add_action( 'elallas_case_status_changed', [ $this, 'on_status_changed' ], 10, 4 );
		add_filter( 'woocommerce_email_from_address', [ $this, 'from_address' ], 10, 2 );
		add_filter( 'woocommerce_email_from_name', [ $this, 'from_name' ], 10, 2 );
	}

	/**
	 * Override the From address for this plugin's e-mails when configured.
	 *
	 * @param string $address Default from address.
	 * @param mixed  $email   WC_Email instance (or null in some contexts).
	 * @return string
	 */
	public function from_address( $address, $email = null ) {
		if ( ! self::is_plugin_email( $email ) ) {
			return $address;
		}

		$custom = trim( (string) Options::get( 'email_from_address', '' ) );

		return ( '' !== $custom && is_email( $custom ) ) ? $custom : $address;
	}

	/**
	 * Override the From name for this plugin's e-mails when configured.
	 *
	 * @param string $name  Default from name.
	 * @param mixed  $email WC_Email instance (or null in some contexts).
	 * @return string
	 */
	public function from_name( $name, $email = null ) {
		if ( ! self::is_plugin_email( $email ) ) {
			return $name;
		}

		$custom = trim( (string) Options::get( 'email_from_name', '' ) );

		return '' !== $custom ? $custom : $name;
	}

	/**
	 * Whether the given WC_Email belongs to this plugin.
	 *
	 * @param mixed $email WC_Email instance or other.
	 * @return bool
	 */
	private static function is_plugin_email( $email ): bool {
		return $email instanceof \WC_Email && str_starts_with( (string) $email->id, 'elallas_' );
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

		// Customer-facing email: render in the language the case was submitted in.
		if ( Options::get( 'email_customer_enabled' ) && isset( $emails['Elallas_Customer_Confirmation'] ) ) {
			Multilingual::switch_to( self::case_language( $case_id ) );

			try {
				$emails['Elallas_Customer_Confirmation']->trigger( $case_id );
				Logger::debug( 'Vevői visszaigazoló e-mail elindítva.', [ 'case_id' => $case_id ] );
			} finally {
				Multilingual::restore();
			}
		}

		// Admin notification: render in the shop's default language.
		if ( Options::get( 'email_admin_enabled' ) && isset( $emails['Elallas_Admin_Notification'] ) ) {
			Multilingual::switch_to( Multilingual::default_language() );

			try {
				$emails['Elallas_Admin_Notification']->trigger( $case_id );
				Logger::debug( 'Admin értesítő e-mail elindítva.', [ 'case_id' => $case_id ] );
			} finally {
				Multilingual::restore();
			}
		}
	}

	/**
	 * Handle a status change: notify the customer.
	 *
	 * @param int    $case_id Case ID.
	 * @param string $old     Previous status.
	 * @param string $new     New status.
	 * @param string $message Optional note to the customer.
	 * @return void
	 */
	public function on_status_changed( int $case_id, string $old, string $new, string $message = '' ): void {
		unset( $old, $new );

		if ( ! Options::get( 'email_status_enabled' ) ) {
			return;
		}

		$emails = \WC()->mailer()->get_emails();

		if ( isset( $emails['Elallas_Status_Update'] ) ) {
			Multilingual::switch_to( self::case_language( $case_id ) );

			try {
				$emails['Elallas_Status_Update']->trigger( $case_id, $message );
				Logger::debug( 'Státusz e-mail elindítva.', [ 'case_id' => $case_id ] );
			} finally {
				Multilingual::restore();
			}
		}
	}

	/**
	 * Build the optional "policy / terms" link block shown at the foot of every
	 * withdrawal e-mail. Uses the configured URL, or falls back to the store's
	 * WooCommerce Terms &amp; Conditions page. Returns '' when disabled or when no
	 * URL can be resolved.
	 *
	 * @param bool $plain Whether the plain-text variant is requested.
	 * @return string
	 */
	public static function policy_link( bool $plain = false ): string {
		if ( ! Options::get( 'email_policy_enabled' ) ) {
			return '';
		}

		$url = trim( (string) Options::get( 'email_policy_url', '' ) );

		if ( '' === $url && function_exists( 'wc_terms_and_conditions_page_id' ) ) {
			$tc_id = (int) wc_terms_and_conditions_page_id();

			if ( $tc_id > 0 ) {
				$url = (string) get_permalink( $tc_id );
			}
		}

		/**
		 * Filter the resolved policy / terms link URL used in withdrawal e-mails.
		 *
		 * Handy for a per-language target (e.g. a translated ÁSZF page).
		 *
		 * @since 1.0.13
		 * @param string $url Resolved URL (custom option value, else the WooCommerce T&C page).
		 */
		$url = (string) apply_filters( 'elallas_policy_url', $url );

		if ( '' === $url ) {
			return '';
		}

		$label = Multilingual::translate_option_string( 'email_policy_label' );

		if ( '' === trim( $label ) ) {
			$label = __( 'Általános Szerződési Feltételek (ÁSZF)', 'elallas-for-woo' );
		}

		if ( $plain ) {
			// Plain-text channel: strip any markup from the label and raw-escape the
			// URL (no HTML entity-encoding, which would garble query-arg URLs).
			return wp_strip_all_tags( $label ) . ': ' . esc_url_raw( $url );
		}

		return sprintf(
			'<p style="margin-top:16px;"><a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a></p>',
			esc_url( $url ),
			esc_html( $label )
		);
	}

	/**
	 * Language the case was submitted in, or '' when unknown.
	 *
	 * @param int $case_id Case ID.
	 * @return string
	 */
	private static function case_language( int $case_id ): string {
		$case = CaseRepository::find( $case_id );

		return ( $case && '' !== $case->language ) ? $case->language : '';
	}
}

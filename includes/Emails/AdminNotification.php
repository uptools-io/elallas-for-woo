<?php
/**
 * Admin notification email (new withdrawal case).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Emails;

use LightweightPlugins\Elallas\Options;
use LightweightPlugins\Elallas\Database\CaseRepository;

/**
 * Sent to the shop admin when a withdrawal case is confirmed.
 */
class AdminNotification extends \WC_Email {

	use PreviewableEmailTrait;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'elallas_admin_notification';
		$this->title       = __( 'Új elállási nyilatkozat (admin)', 'elallas-for-woo' );
		$this->description = __( 'Értesítés a kereskedő felé, ha új elállási nyilatkozat érkezik.', 'elallas-for-woo' );

		$this->template_base  = ELALLAS_FOR_WOO_PATH . 'templates/';
		$this->template_html  = 'emails/admin-notification.php';
		$this->template_plain = 'emails/plain/admin-notification.php';

		$this->placeholders = [
			'{case_number}'  => '',
			'{order_number}' => '',
		];

		parent::__construct();
	}

	/**
	 * Get default recipient.
	 *
	 * @return string
	 */
	public function get_default_recipient(): string {
		$recipient = (string) Options::get( 'email_admin_recipient', '' );

		return '' !== $recipient ? $recipient : (string) get_option( 'admin_email' );
	}

	/**
	 * Get default subject.
	 *
	 * @return string
	 */
	public function get_default_subject(): string {
		return __( 'Új elállási nyilatkozat érkezett – #{order_number}', 'elallas-for-woo' );
	}

	/**
	 * Get default heading.
	 *
	 * @return string
	 */
	public function get_default_heading(): string {
		return __( 'Új elállási nyilatkozat', 'elallas-for-woo' );
	}

	/**
	 * Trigger the email.
	 *
	 * @param int $case_id Case ID.
	 * @return void
	 */
	public function trigger( int $case_id ): void {
		$case = CaseRepository::find( $case_id );

		if ( ! $case ) {
			return;
		}

		$this->object                         = $case;
		$this->recipient                      = $this->get_default_recipient();
		$this->placeholders['{case_number}']  = $case->case_number;
		$this->placeholders['{order_number}'] = $case->order_number;

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send(
				$this->get_recipient(),
				$this->get_subject(),
				$this->get_content(),
				$this->get_headers(),
				$this->get_attachments()
			);
		}
	}

	/**
	 * Get content HTML.
	 *
	 * @return string
	 */
	public function get_content_html(): string {
		return wc_get_template_html( $this->template_html, $this->template_args( false ), '', $this->template_base );
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain(): string {
		return wc_get_template_html( $this->template_plain, $this->template_args( true ), '', $this->template_base );
	}

	/**
	 * Build template arguments.
	 *
	 * @param bool $plain_text Whether the plain-text variant is requested.
	 * @return array<string, mixed>
	 */
	private function template_args( bool $plain_text ): array {
		[ $case, $items ] = $this->resolve_case_items();

		return [
			'case'          => $case,
			'items'         => $items,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => true,
			'plain_text'    => $plain_text,
			'email'         => $this,
		];
	}
}

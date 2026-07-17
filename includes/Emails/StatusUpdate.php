<?php
/**
 * Status update email (case status changed).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Emails;

use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Woo\OrderAdapter;

/**
 * Sent to the customer when their withdrawal case status changes.
 */
class StatusUpdate extends \WC_Email {

	use PreviewableEmailTrait;

	/**
	 * Optional admin note to the customer, shown in this e-mail.
	 *
	 * @var string
	 */
	protected string $status_message = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'elallas_status_update';
		$this->customer_email = true;
		$this->title          = __( 'Elállási ügy státusz frissítés (vevő)', 'elallas-for-woo' );
		$this->description    = __( 'Értesítés a vevőnek, ha elállási ügyének státusza megváltozik.', 'elallas-for-woo' );

		$this->template_base  = ELALLAS_FOR_WOO_PATH . 'templates/';
		$this->template_html  = 'emails/status-update.php';
		$this->template_plain = 'emails/plain/status-update.php';

		$this->placeholders = [
			'{case_number}'  => '',
			'{order_number}' => '',
		];

		parent::__construct();
	}

	/**
	 * Get default subject.
	 *
	 * @return string
	 */
	public function get_default_subject(): string {
		return __( 'Elállási ügyének státusza frissült – #{case_number}', 'elallas-for-woo' );
	}

	/**
	 * Get default heading.
	 *
	 * @return string
	 */
	public function get_default_heading(): string {
		return __( 'Elállási ügyének státusza frissült', 'elallas-for-woo' );
	}

	/**
	 * Trigger the email.
	 *
	 * @param int    $case_id Case ID.
	 * @param string $message Optional admin note to the customer.
	 * @return void
	 */
	public function trigger( int $case_id, string $message = '' ): void {
		$case = CaseRepository::find( $case_id );

		if ( ! $case ) {
			return;
		}

		$order = OrderAdapter::get_order( $case->order_id );

		if ( ! $order ) {
			return;
		}

		$this->status_message                 = $message;
		$this->object                         = $case;
		$this->recipient                      = $order->get_billing_email();
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
			'case'           => $case,
			'items'          => $items,
			'email_heading'  => $this->get_heading(),
			'sent_to_admin'  => false,
			'plain_text'     => $plain_text,
			'status_message' => $this->status_message,
			'email'          => $this,
		];
	}
}

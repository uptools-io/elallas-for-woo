<?php
/**
 * Customer confirmation email (durable medium acknowledgement).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Emails;

use LightweightPlugins\Elallas\Options;
use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Models\WithdrawalCase;
use LightweightPlugins\Elallas\Woo\OrderAdapter;

/**
 * Sent to the customer when their withdrawal case is confirmed.
 */
class CustomerConfirmation extends \WC_Email {

	use PreviewableEmailTrait;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'elallas_customer_confirmation';
		$this->customer_email = true;
		$this->title          = __( 'Elállás visszaigazolása (vevő)', 'elallas-for-woo' );
		$this->description    = __( 'Tartós adathordozón küldött visszaigazolás a vevőnek az elállási nyilatkozat rögzítéséről.', 'elallas-for-woo' );

		$this->template_base  = ELALLAS_FOR_WOO_PATH . 'templates/';
		$this->template_html  = 'emails/customer-confirmation.php';
		$this->template_plain = 'emails/plain/customer-confirmation.php';

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
		return __( 'Elállási nyilatkozatának visszaigazolása – #{order_number}', 'elallas-for-woo' );
	}

	/**
	 * Get default heading.
	 *
	 * @return string
	 */
	public function get_default_heading(): string {
		return __( 'Elállási nyilatkozatának visszaigazolása', 'elallas-for-woo' );
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

		$order = OrderAdapter::get_order( $case->order_id );

		if ( ! $order ) {
			return;
		}

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
			'case'          => $case,
			'items'         => $items,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => $plain_text,
			'email'         => $this,
		];
	}

	/**
	 * Attach the generated withdrawal PDF when enabled.
	 *
	 * @return array<int, string>
	 */
	public function get_attachments(): array {
		$attachments = [];

		if ( ! ( $this->object instanceof WithdrawalCase ) || ! Options::get( 'pdf_enabled' ) ) {
			return $attachments;
		}

		if ( ! class_exists( '\LightweightPlugins\Elallas\Pdf\DocumentService' ) ) {
			return $attachments;
		}

		$document_id = \LightweightPlugins\Elallas\Pdf\DocumentService::generate( (int) $this->object->id );

		if ( $document_id > 0 ) {
			$path = \LightweightPlugins\Elallas\Pdf\DocumentService::get_file_path( $document_id );

			if ( '' !== $path && file_exists( $path ) ) {
				$attachments[] = $path;
			}
		}

		return $attachments;
	}
}

<?php
/**
 * Harmonised notice in customer order e-mails.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

use LightweightPlugins\Elallas\Frontend\TemplateLoader;
use LightweightPlugins\Elallas\Integrations\Multilingual;
use LightweightPlugins\Elallas\Options;

/**
 * Adds the notice (linked official PNG + label + QR-target link, or plain
 * text) after the order table of customer e-mails, and optionally attaches
 * the official colour PNG. Admin e-mails never get it. A language without an
 * official PNG (EN) gets text + link only — never a self-made image.
 */
final class NoticeEmail {

	/**
	 * Allowed e-mail modes.
	 *
	 * @var array<int, string>
	 */
	public const MODES = [ 'image', 'attachment', 'both' ];

	/**
	 * Register hooks.
	 */
	public function __construct() {
		if ( ! Options::get( 'notice_display_email' ) ) {
			return;
		}

		add_action( 'woocommerce_email_after_order_table', [ $this, 'render_email' ], 15, 4 );
		add_filter( 'woocommerce_email_attachments', [ $this, 'email_attachments' ], 10, 4 );
	}

	/**
	 * Print the notice block.
	 *
	 * @param mixed $order         Order.
	 * @param mixed $sent_to_admin Sent to admin.
	 * @param mixed $plain_text    Plain-text e-mail.
	 * @param mixed $email         WC_Email.
	 * @return void
	 */
	public function render_email( $order, $sent_to_admin = false, $plain_text = false, $email = null ): void {
		if ( $sent_to_admin || ! $email instanceof \WC_Email || ! $order instanceof \WC_Order ) {
			return;
		}

		if ( ! self::applies( $order, (string) $email->id ) ) {
			return;
		}

		$code = self::language_for_order( $order );
		$mode = self::mode();
		$png  = NoticeSource::png_url( $code );

		$html = TemplateLoader::render(
			$plain_text ? 'emails/plain/guarantee-notice.php' : 'emails/guarantee-notice.php',
			[
				'label'      => NoticeRenderer::label(),
				'png_url'    => $png,
				'show_image' => '' !== $png && 'attachment' !== $mode,
				'attached'   => '' !== NoticeSource::png_path( $code ) && ( 'image' !== $mode || $plain_text ),
				'alt'        => NoticeSource::alt( $code ),
				'link_url'   => NoticeSource::link_url( $code ),
				'link_label' => NoticeSource::link_label( $code ),
			]
		);

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output is escaped.
	}

	/**
	 * Attach the official colour PNG (attachment/both mode, and always for plain-text e-mails).
	 *
	 * @param mixed $attachments Attachments.
	 * @param mixed $email_id    E-mail id.
	 * @param mixed $order       Object the e-mail is about.
	 * @param mixed $email       WC_Email.
	 * @return mixed
	 */
	public function email_attachments( $attachments, $email_id = '', $order = null, $email = null ) {
		if ( ! is_array( $attachments ) || ! $order instanceof \WC_Order || ! self::applies( $order, (string) $email_id ) ) {
			return $attachments;
		}

		$plain = $email instanceof \WC_Email && 'plain' === $email->get_email_type();

		if ( 'image' === self::mode() && ! $plain ) {
			return $attachments;
		}

		$path = NoticeSource::png_path( self::language_for_order( $order ) );

		if ( '' !== $path && ! in_array( $path, $attachments, true ) ) {
			$attachments[] = $path;
		}

		return $attachments;
	}

	/**
	 * Notice language of an order e-mail: WPML order language, the language at
	 * send time (WCML / Polylang switch before sending), the site default, `hu`.
	 *
	 * @param \WC_Order $order Order.
	 * @return string
	 */
	public static function language_for_order( \WC_Order $order ): string {
		$lang = (string) $order->get_meta( 'wpml_language' );

		return NoticeSource::language( 'email', '' !== $lang ? $lang : Multilingual::current_language() );
	}

	/**
	 * Configured e-mail mode (whitelisted).
	 *
	 * @return string
	 */
	public static function mode(): string {
		$mode = (string) Options::get( 'notice_email_mode' );

		return in_array( $mode, self::MODES, true ) ? $mode : 'image';
	}

	/**
	 * Common gates: customer e-mail, goods in the order, visibility / B2B.
	 *
	 * @param \WC_Order $order    Order.
	 * @param string    $email_id E-mail id.
	 * @return bool
	 */
	private static function applies( \WC_Order $order, string $email_id ): bool {
		return EmailContext::is_customer_email( $email_id )
			&& GoodsScope::order_has_goods( $order )
			&& NoticeRenderer::is_visible( 'email', $order );
	}
}

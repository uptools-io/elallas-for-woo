<?php
/**
 * HTML of the harmonised legal-guarantee notice (label + official image + link).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

use LightweightPlugins\Elallas\Data\DefaultTexts;
use LightweightPlugins\Elallas\Domain\B2BDetector;
use LightweightPlugins\Elallas\Frontend\TemplateLoader;
use LightweightPlugins\Elallas\Integrations\Multilingual;
use LightweightPlugins\Elallas\Options;

/**
 * Renders templates/frontend/guarantee-notice.php.
 *
 * The image URL, the link and the label are prepared here and handed to the
 * (theme-overridable) template as finished values, so an override can restyle
 * the frame but never swap the official file or the QR-code target.
 */
final class NoticeRenderer {

	/**
	 * Deterministic ids for AJAX-replaced / cloned contexts (wp_unique_id() would
	 * restart at 1 inside the update_order_review fragment and collide).
	 *
	 * @var array<string, string>
	 */
	private const FIXED_IDS = [
		'checkout' => 'elallas-notice-checkout',
		'orderpay' => 'elallas-notice-orderpay',
		'slot'     => 'elallas-notice-slot',
	];

	/**
	 * Render the notice.
	 *
	 * @param array{context?: string, mode?: string, label?: string, order?: \WC_Order|null} $args Arguments.
	 * @return string '' when hidden.
	 */
	public static function render( array $args = [] ): string {
		$context = (string) ( $args['context'] ?? 'shortcode' );
		$order   = ( $args['order'] ?? null ) instanceof \WC_Order ? $args['order'] : null;

		if ( ! self::is_visible( $context, $order ) ) {
			return '';
		}

		$code = NoticeSource::language( $context );
		$html = TemplateLoader::render(
			'frontend/guarantee-notice.php',
			[
				'uid'          => self::FIXED_IDS[ $context ] ?? wp_unique_id( 'elallas-notice-' ),
				'context'      => sanitize_html_class( $context ),
				'mode'         => 'inline' === ( $args['mode'] ?? 'toggle' ) ? 'inline' : 'toggle',
				'label'        => self::label( (string) ( $args['label'] ?? '' ) ),
				'code'         => $code,
				'image_url'    => NoticeSource::image_url( $code ),
				'image_width'  => (int) round( OfficialAssets::SVG_SIZE['width'] ),
				'image_height' => (int) round( OfficialAssets::SVG_SIZE['height'] ),
				'alt'          => NoticeSource::alt( $code ),
				'link_url'     => NoticeSource::link_url( $code ),
				'link_label'   => NoticeSource::link_label( $code ),
				'page_url'     => 'page' === $context ? '' : self::page_url(),
			]
		);

		if ( '' !== $html ) {
			ComplianceAssets::enqueue();
		}

		return $html;
	}

	/**
	 * Translated label with a gettext fallback (never an empty summary).
	 *
	 * @param string $override Explicit label (shortcode attribute), '' = option.
	 * @return string
	 */
	public static function label( string $override = '' ): string {
		$label = '' !== $override
			? Multilingual::translate_string( $override, 'notice_label' )
			: Multilingual::translate_option_string( 'notice_label' );

		// An untouched default is the raw Hungarian source: show its translation instead.
		if ( '' === trim( $label ) || DefaultTexts::notice_label() === $label ) {
			return __( 'Az Ön jogszabályi szavatossági jogai', 'elallas-for-woo' );
		}

		return $label;
	}

	/**
	 * Visibility gates: developer filter and B2B hiding.
	 *
	 * @param string         $context Output context.
	 * @param \WC_Order|null $order   Order on order-based surfaces.
	 * @return bool
	 */
	public static function is_visible( string $context, ?\WC_Order $order = null ): bool {
		/**
		 * Filter whether the notice is shown in a context.
		 *
		 * @param bool           $visible Visible.
		 * @param string         $context product|header|footer|checkout|orderpay|slot|order|email|shortcode|page.
		 * @param \WC_Order|null $order   Order on order-based surfaces.
		 */
		if ( ! apply_filters( 'elallas_notice_visible', true, $context, $order ) ) {
			return false;
		}

		return ! self::hidden_for_b2b( $context, $order );
	}

	/**
	 * B2B hiding: detector on order-based surfaces, filter elsewhere (default: show).
	 *
	 * @param string         $context Output context.
	 * @param \WC_Order|null $order   Order.
	 * @return bool
	 */
	public static function hidden_for_b2b( string $context, ?\WC_Order $order = null ): bool {
		if ( ! Options::get( 'compliance_hide_b2b' ) ) {
			return false;
		}

		if ( null !== $order ) {
			return B2BDetector::is_b2b( $order );
		}

		/**
		 * Whether the visitor is a business customer where no order exists yet
		 * (product page, cart, checkout, header, footer). Default false: show,
		 * because wrongly hiding a mandatory consumer notice is the bigger risk.
		 *
		 * @param bool   $is_b2b  Is B2B.
		 * @param string $context Output context.
		 */
		return (bool) apply_filters( 'elallas_compliance_is_b2b', false, $context );
	}

	/**
	 * URL of the standalone notice page (translated), '' when none.
	 *
	 * @return string
	 */
	public static function page_url(): string {
		$page_id = (int) Options::get( 'notice_page_id' );

		if ( $page_id <= 0 ) {
			return '';
		}

		$page_id = Multilingual::object_id( $page_id, 'page' );

		return 'publish' === get_post_status( $page_id ) ? (string) get_permalink( $page_id ) : '';
	}
}

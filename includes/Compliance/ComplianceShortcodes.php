<?php
/**
 * [elallas_guarantee_notice] and [elallas_garan_label] shortcodes.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

use LightweightPlugins\Elallas\Integrations\Multilingual;
use LightweightPlugins\Elallas\Options;

/**
 * Free placement of the compliance output (also the documented fallback for
 * Elementor Pro and customised block templates, via the Shortcode block/widget).
 * Both return '' while their module is switched off.
 */
final class ComplianceShortcodes {

	/**
	 * Register the shortcodes.
	 */
	public function __construct() {
		add_shortcode( 'elallas_guarantee_notice', [ $this, 'guarantee_notice' ] );
		add_shortcode( 'elallas_garan_label', [ $this, 'garan_label' ] );
	}

	/**
	 * [elallas_guarantee_notice label="" mode="toggle|inline"].
	 *
	 * @param array<string, mixed>|string $atts Attributes.
	 * @return string
	 */
	public function guarantee_notice( $atts ): string {
		if ( ! Options::get( 'notice_enabled' ) ) {
			return '';
		}

		$atts = shortcode_atts(
			[
				'label' => '',
				'mode'  => 'toggle',
			],
			(array) $atts,
			'elallas_guarantee_notice'
		);

		return NoticeRenderer::render(
			[
				'context' => self::is_notice_page() ? 'page' : 'shortcode',
				'mode'    => 'inline' === $atts['mode'] ? 'inline' : 'toggle',
				'label'   => (string) $atts['label'],
			]
		);
	}

	/**
	 * [elallas_garan_label] — rendered by GaranRenderer.
	 *
	 * @param array<string, mixed>|string $atts Attributes.
	 * @return string
	 */
	public function garan_label( $atts ): string {
		if ( ! Options::get( 'garan_enabled' ) ) {
			return '';
		}

		return GaranRenderer::shortcode( is_array( $atts ) ? $atts : [] );
	}

	/**
	 * Whether the current page is the standalone notice page (no self-link then).
	 *
	 * @return bool
	 */
	private static function is_notice_page(): bool {
		$page_id = (int) Options::get( 'notice_page_id' );

		return $page_id > 0 && is_page( Multilingual::object_id( $page_id, 'page' ) );
	}
}

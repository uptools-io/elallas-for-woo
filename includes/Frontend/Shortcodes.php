<?php
/**
 * Front-end shortcodes.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Frontend;

use LightweightPlugins\Elallas\Options;

/**
 * Registers the [elallas_form] and [elallas_button] shortcodes.
 */
final class Shortcodes {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'elallas_form', [ $this, 'render_form' ] );
		add_shortcode( 'elallas_button', [ $this, 'render_button' ] );
	}

	/**
	 * Render the withdrawal form.
	 *
	 * @param array<string, mixed>|string $atts Attributes.
	 * @return string
	 */
	public function render_form( $atts ): string {
		$atts = shortcode_atts( [ 'mode' => 'full' ], (array) $atts, 'elallas_form' );

		return WithdrawalForm::render( $atts );
	}

	/**
	 * Render a button/link to the withdrawal page.
	 *
	 * @param array<string, mixed>|string $atts Attributes.
	 * @return string
	 */
	public function render_button( $atts ): string {
		if ( ! Options::get( 'enabled' ) ) {
			return '';
		}

		$atts  = shortcode_atts( [ 'label' => (string) Options::get( 'button_label' ) ], (array) $atts, 'elallas_button' );
		$url   = self::page_url();
		$label = '' !== $atts['label'] ? $atts['label'] : __( 'Elállás a szerződéstől', 'elallas-for-woo' );

		if ( '' === $url ) {
			return '';
		}

		return sprintf(
			'<a class="elallas-button button" href="%1$s">%2$s</a>',
			esc_url( $url ),
			esc_html( $label )
		);
	}

	/**
	 * URL of the configured withdrawal page.
	 *
	 * @return string
	 */
	public static function page_url(): string {
		$page_id = (int) Options::get( 'withdrawal_page_id' );

		return $page_id > 0 ? (string) get_permalink( $page_id ) : '';
	}
}

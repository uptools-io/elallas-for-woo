<?php
/**
 * Withdrawal form renderer.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Frontend;

use LightweightPlugins\Elallas\Security\Honeypot;
use LightweightPlugins\Elallas\Options;

/**
 * Drives the front-end flow: processes the request, renders the right step.
 */
final class WithdrawalForm {

	/**
	 * Render the form for the current request state.
	 *
	 * @param array<string, mixed> $atts Shortcode/block attributes.
	 * @return string
	 */
	public static function render( array $atts = [] ): string {
		if ( ! Options::get( 'enabled' ) ) {
			return '<div class="elallas-form elallas-disabled"><p>' . esc_html__( 'Az online elállási funkció jelenleg nem érhető el.', 'elallas-for-woo' ) . '</p></div>';
		}

		$state = ( new FormHandler() )->handle();
		$view  = (string) $state['view'];
		$data  = (array) $state['data'];

		$data['atts']          = $atts;
		$data['form_action']   = esc_url( remove_query_arg( [ 'elallas_done' ] ) );
		$data['nonce_field']   = wp_nonce_field( 'elallas_form', 'elallas_nonce', true, false );
		$data['honeypot']      = Honeypot::field();

		$html = TemplateLoader::render( 'frontend/' . $view . '.php', $data );

		return '<div class="elallas-form elallas-step-' . esc_attr( $view ) . '">' . $html . '</div>';
	}
}

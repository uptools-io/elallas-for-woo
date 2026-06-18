<?php
/**
 * Elementor integration.
 *
 * Registers an Elementor widget that renders the [elallas_form] shortcode.
 * Guarded so it is a no-op when Elementor is not active.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Integrations;

/**
 * Wires the elállás form into Elementor as a widget.
 */
final class Elementor {

	/**
	 * Register hooks when Elementor is available.
	 */
	public function __construct() {
		if ( ! $this->has_elementor() ) {
			return;
		}

		add_action( 'elementor/widgets/register', [ $this, 'register_widget' ] );
	}

	/**
	 * Whether Elementor is loaded.
	 *
	 * @return bool
	 */
	private function has_elementor(): bool {
		return did_action( 'elementor/loaded' ) > 0 || class_exists( '\Elementor\Widget_Base' );
	}

	/**
	 * Register the elállás form widget.
	 *
	 * @param object $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widget( $widgets_manager ): void {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			return;
		}

		if ( ! class_exists( '\LightweightPlugins\Elallas\Integrations\ElallasElementorWidget' ) ) {
			return;
		}

		if ( ! is_object( $widgets_manager ) || ! method_exists( $widgets_manager, 'register' ) ) {
			return;
		}

		$widgets_manager->register( new ElallasElementorWidget() );
	}
}

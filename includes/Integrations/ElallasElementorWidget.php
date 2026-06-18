<?php
/**
 * Elementor widget rendering the elállás form.
 *
 * Loaded only when Elementor (\Elementor\Widget_Base) is present; the class
 * extends the base widget and outputs the [elallas_form] shortcode.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Integrations;

/**
 * Elementor widget that renders the withdrawal (elállás) form.
 *
 * @phpcs:disable PHPCompatibility.Classes.NewClasses.elementor_widget_baseFound
 */
final class ElallasElementorWidget extends \Elementor\Widget_Base {

	/**
	 * Widget machine name.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'elallas_form';
	}

	/**
	 * Widget display title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Elállási űrlap', 'elallas-for-woo' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-form-horizontal';
	}

	/**
	 * Widget categories.
	 *
	 * @return array<int, string>
	 */
	public function get_categories(): array {
		return [ 'general' ];
	}

	/**
	 * Search keywords.
	 *
	 * @return array<int, string>
	 */
	public function get_keywords(): array {
		return [ 'elallas', 'withdrawal', 'woocommerce', 'elállás' ];
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * @return void
	 */
	protected function render(): void {
		echo do_shortcode( '[elallas_form]' );
	}
}

<?php
/**
 * Field Renderer Trait.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin\Settings;

use LightweightPlugins\Elallas\Options;

/**
 * Trait for rendering settings form fields that read from Options.
 */
trait FieldRendererTrait {

	/**
	 * Render a text input field.
	 *
	 * @param string $name Option key.
	 * @param string $desc Optional description.
	 * @return void
	 */
	protected function render_text( string $name, string $desc = '' ): void {
		printf(
			'<input type="text" id="%1$s" name="%2$s[%1$s]" value="%3$s" class="regular-text" />',
			esc_attr( $name ),
			esc_attr( Options::OPTION_NAME ),
			esc_attr( (string) Options::get( $name ) )
		);
		$this->render_desc( $desc );
	}

	/**
	 * Render a checkbox field.
	 *
	 * @param string $name  Option key.
	 * @param string $label Checkbox label.
	 * @return void
	 */
	protected function render_checkbox( string $name, string $label = '' ): void {
		printf(
			'<label><input type="checkbox" id="%1$s" name="%2$s[%1$s]" value="1" %3$s /> %4$s</label>',
			esc_attr( $name ),
			esc_attr( Options::OPTION_NAME ),
			checked( (bool) Options::get( $name ), true, false ),
			esc_html( $label )
		);
	}

	/**
	 * Render a select field.
	 *
	 * @param string                $name    Option key.
	 * @param array<string, string> $options Choices.
	 * @param string                $desc    Optional description.
	 * @return void
	 */
	protected function render_select( string $name, array $options, string $desc = '' ): void {
		$value = (string) Options::get( $name );
		printf( '<select id="%1$s" name="%2$s[%1$s]">', esc_attr( $name ), esc_attr( Options::OPTION_NAME ) );
		foreach ( $options as $key => $label ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( (string) $key ), selected( $value, (string) $key, false ), esc_html( $label ) );
		}
		echo '</select>';
		$this->render_desc( $desc );
	}

	/**
	 * Render a textarea field.
	 *
	 * @param string $name Option key.
	 * @param string $desc Optional description.
	 * @return void
	 */
	protected function render_textarea( string $name, string $desc = '' ): void {
		printf(
			'<textarea id="%1$s" name="%2$s[%1$s]" rows="5" class="large-text">%3$s</textarea>',
			esc_attr( $name ),
			esc_attr( Options::OPTION_NAME ),
			esc_textarea( (string) Options::get( $name ) )
		);
		$this->render_desc( $desc );
	}

	/**
	 * Render an optional description paragraph.
	 *
	 * @param string $desc Description text.
	 * @return void
	 */
	private function render_desc( string $desc ): void {
		if ( '' !== $desc ) {
			printf( '<p class="description">%s</p>', esc_html( $desc ) );
		}
	}
}

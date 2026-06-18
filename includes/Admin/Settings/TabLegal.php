<?php
/**
 * Legal Texts Settings Tab.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin\Settings;

use LightweightPlugins\Elallas\Data\DefaultTexts;

/**
 * Handles editable legal declaration and confirmation texts.
 */
final class TabLegal implements TabInterface {

	use FieldRendererTrait;

	/**
	 * Tab slug.
	 *
	 * @return string
	 */
	public function id(): string {
		return 'legal';
	}

	/**
	 * Tab label.
	 *
	 * @return string
	 */
	public function label(): string {
		return __( 'Jogi szövegek', 'elallas-for-woo' );
	}

	/**
	 * Option keys handled by this tab.
	 *
	 * @return array<string, string>
	 */
	public function fields(): array {
		return [
			'legal_declaration'  => 'textarea',
			'legal_confirmation' => 'textarea',
		];
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Jogi szövegek', 'elallas-for-woo' ); ?></h2>
		<div class="notice notice-warning inline" style="margin:0 0 16px;">
			<p><strong><?php echo esc_html( DefaultTexts::disclaimer() ); ?></strong></p>
		</div>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="legal_declaration"><?php esc_html_e( 'Elállási nyilatkozat szövege', 'elallas-for-woo' ); ?></label></th>
				<td><?php $this->render_textarea( 'legal_declaration' ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="legal_confirmation"><?php esc_html_e( 'Visszaigazolás szövege', 'elallas-for-woo' ); ?></label></th>
				<td>
					<?php
					$this->render_textarea(
						'legal_confirmation',
						__( 'Helyettesítők: {submitted_at}, {case_number}.', 'elallas-for-woo' )
					);
					?>
				</td>
			</tr>
		</table>
		<?php
	}
}

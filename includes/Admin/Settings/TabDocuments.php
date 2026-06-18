<?php
/**
 * Documents Settings Tab.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin\Settings;

/**
 * Handles document/PDF generation settings.
 */
final class TabDocuments implements TabInterface {

	use FieldRendererTrait;

	/**
	 * Tab slug.
	 *
	 * @return string
	 */
	public function id(): string {
		return 'documents';
	}

	/**
	 * Tab label.
	 *
	 * @return string
	 */
	public function label(): string {
		return __( 'Dokumentumok', 'elallas-for-woo' );
	}

	/**
	 * Option keys handled by this tab.
	 *
	 * @return array<string, string>
	 */
	public function fields(): array {
		return [
			'pdf_enabled'    => 'bool',
			'retention_days' => 'int',
		];
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Dokumentumok', 'elallas-for-woo' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'PDF dokumentum', 'elallas-for-woo' ); ?></th>
				<td><?php $this->render_checkbox( 'pdf_enabled', __( 'PDF nyilatkozat generálása (tartós adathordozó)', 'elallas-for-woo' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="retention_days"><?php esc_html_e( 'Megőrzési idő (nap)', 'elallas-for-woo' ); ?></label></th>
				<td>
					<?php
					printf(
						'<input type="number" id="retention_days" name="%1$s[retention_days]" value="%2$s" min="0" class="small-text" />',
						esc_attr( \LightweightPlugins\Elallas\Options::OPTION_NAME ),
						esc_attr( (string) \LightweightPlugins\Elallas\Options::get( 'retention_days' ) )
					);
					?>
					<p class="description"><?php esc_html_e( '0 = korlátlan megőrzés.', 'elallas-for-woo' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}
}

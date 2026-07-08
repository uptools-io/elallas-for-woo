<?php
/**
 * General Settings Tab.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin\Settings;

/**
 * Handles the General settings tab.
 */
final class TabGeneral implements TabInterface {

	use FieldRendererTrait;

	/**
	 * Tab slug.
	 *
	 * @return string
	 */
	public function id(): string {
		return 'general';
	}

	/**
	 * Tab label.
	 *
	 * @return string
	 */
	public function label(): string {
		return __( 'Általános', 'elallas-for-woo' );
	}

	/**
	 * Option keys handled by this tab.
	 *
	 * @return array<string, string>
	 */
	public function fields(): array {
		return [
			'enabled'               => 'bool',
			'button_label'          => 'text',
			'confirm_label'         => 'text',
			'display_account'       => 'bool',
			'display_order_details' => 'bool',
			'display_order_email'   => 'bool',
			'withdrawal_page_id'    => 'int',
			'logging_enabled'       => 'bool',
		];
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Általános beállítások', 'elallas-for-woo' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Bekapcsolás', 'elallas-for-woo' ); ?></th>
				<td><?php $this->render_checkbox( 'enabled', __( 'Online elállási nyilatkozat engedélyezése', 'elallas-for-woo' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="button_label"><?php esc_html_e( 'Gomb felirata', 'elallas-for-woo' ); ?></label></th>
				<td><?php $this->render_text( 'button_label' ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="confirm_label"><?php esc_html_e( 'Megerősítés gomb felirata', 'elallas-for-woo' ); ?></label></th>
				<td><?php $this->render_text( 'confirm_label' ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="withdrawal_page_id"><?php esc_html_e( 'Elállási oldal', 'elallas-for-woo' ); ?></label></th>
				<td>
					<?php
					wp_dropdown_pages(
						[
							'name'              => \LightweightPlugins\Elallas\Options::OPTION_NAME . '[withdrawal_page_id]',
							'id'                => 'withdrawal_page_id',
							'selected'          => (int) \LightweightPlugins\Elallas\Options::get( 'withdrawal_page_id' ),
							'show_option_none'  => __( '— Válassz oldalt —', 'elallas-for-woo' ),
							'option_none_value' => 0,
						]
					);
					?>
					<p class="description"><?php esc_html_e( 'Az az oldal, amely tartalmazza az [elallas_form] rövidkódot.', 'elallas-for-woo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Megjelenítési helyek', 'elallas-for-woo' ); ?></th>
				<td>
					<?php $this->render_checkbox( 'display_account', __( 'Fiók oldalon', 'elallas-for-woo' ) ); ?><br />
					<?php $this->render_checkbox( 'display_order_details', __( 'Rendelés részleteinél', 'elallas-for-woo' ) ); ?><br />
					<?php $this->render_checkbox( 'display_order_email', __( 'Rendelési e-mailben', 'elallas-for-woo' ) ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Debug naplózás', 'elallas-for-woo' ); ?></th>
				<td>
					<?php $this->render_checkbox( 'logging_enabled', __( 'Részletes naplózás a WooCommerce → Állapot → Naplók alá (forrás: elallas-for-woo)', 'elallas-for-woo' ) ); ?>
					<p class="description"><?php esc_html_e( 'Hibák és figyelmeztetések bekapcsolás nélkül is naplózódnak. Bekapcsolva a részletes (info/debug) események is bekerülnek. Nem tartalmaz személyes adatot.', 'elallas-for-woo' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}
}

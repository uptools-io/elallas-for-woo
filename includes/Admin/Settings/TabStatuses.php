<?php
/**
 * Order Statuses Settings Tab.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin\Settings;

use LightweightPlugins\Elallas\Options;

/**
 * Handles eligible WooCommerce order status selection.
 */
final class TabStatuses implements TabInterface {

	use FieldRendererTrait;

	/**
	 * Tab slug.
	 *
	 * @return string
	 */
	public function id(): string {
		return 'statuses';
	}

	/**
	 * Tab label.
	 *
	 * @return string
	 */
	public function label(): string {
		return __( 'Rendelési státuszok', 'elallas-for-woo' );
	}

	/**
	 * Option keys handled by this tab.
	 *
	 * @return array<string, string>
	 */
	public function fields(): array {
		return [
			'eligible_statuses' => 'array_key',
			'use_wc_statuses'   => 'bool',
		];
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		$selected = (array) Options::get( 'eligible_statuses' );
		$statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : [];
		?>
		<h2><?php esc_html_e( 'Elállásra jogosult rendelési státuszok', 'elallas-for-woo' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Mely rendelési státuszú rendelésekhez engedélyezett az online elállás.', 'elallas-for-woo' ); ?>
		</p>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Jogosult státuszok', 'elallas-for-woo' ); ?></th>
				<td>
					<?php foreach ( $statuses as $key => $label ) : ?>
						<?php $clean = str_replace( 'wc-', '', (string) $key ); ?>
						<label style="display:block;margin-bottom:4px;">
							<input
								type="checkbox"
								name="<?php echo esc_attr( Options::OPTION_NAME ); ?>[eligible_statuses][]"
								value="<?php echo esc_attr( $clean ); ?>"
								<?php checked( in_array( $clean, $selected, true ), true ); ?> />
							<?php echo esc_html( (string) $label ); ?>
						</label>
					<?php endforeach; ?>
					<?php if ( empty( $statuses ) ) : ?>
						<p class="description"><?php esc_html_e( 'A WooCommerce nem érhető el a státuszok lekéréséhez.', 'elallas-for-woo' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Saját WooCommerce státuszok', 'elallas-for-woo' ); ?></th>
				<td><?php $this->render_checkbox( 'use_wc_statuses', __( 'Egyedi elállási rendelési státuszok használata', 'elallas-for-woo' ) ); ?></td>
			</tr>
		</table>
		<?php
	}
}

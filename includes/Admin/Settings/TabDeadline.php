<?php
/**
 * Deadline Settings Tab.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin\Settings;

/**
 * Handles the Deadline settings tab.
 */
final class TabDeadline implements TabInterface {

	use FieldRendererTrait;

	/**
	 * Tab slug.
	 *
	 * @return string
	 */
	public function id(): string {
		return 'deadline';
	}

	/**
	 * Tab label.
	 *
	 * @return string
	 */
	public function label(): string {
		return __( 'Határidő', 'elallas-for-woo' );
	}

	/**
	 * Option keys handled by this tab.
	 *
	 * @return array<string, string>
	 */
	public function fields(): array {
		return [
			'deadline_days'    => 'int',
			'deadline_start'   => 'key',
			'expired_handling' => 'key',
		];
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h2><?php esc_html_e( 'Elállási határidő', 'elallas-for-woo' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'A határidő csak jelzés (flag), soha nem blokkolja automatikusan a beküldést.', 'elallas-for-woo' ); ?>
		</p>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="deadline_days"><?php esc_html_e( 'Határidő (nap)', 'elallas-for-woo' ); ?></label></th>
				<td>
					<?php
					printf(
						'<input type="number" id="deadline_days" name="%1$s[deadline_days]" value="%2$s" min="0" max="365" class="small-text" />',
						esc_attr( \LightweightPlugins\Elallas\Options::OPTION_NAME ),
						esc_attr( (string) \LightweightPlugins\Elallas\Options::get( 'deadline_days' ) )
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="deadline_start"><?php esc_html_e( 'Határidő kezdete', 'elallas-for-woo' ); ?></label></th>
				<td>
					<?php
					$this->render_select(
						'deadline_start',
						[
							'order_created'   => __( 'Rendelés létrehozása', 'elallas-for-woo' ),
							'order_completed' => __( 'Rendelés teljesítése', 'elallas-for-woo' ),
							'delivery'        => __( 'Kézbesítés napja', 'elallas-for-woo' ),
							'manual'          => __( 'Manuális (ellenőrzést igényel)', 'elallas-for-woo' ),
						]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="expired_handling"><?php esc_html_e( 'Lejárt határidő kezelése', 'elallas-for-woo' ); ?></label></th>
				<td>
					<?php
					$this->render_select(
						'expired_handling',
						[
							'allow_with_warning' => __( 'Engedélyezés figyelmeztetéssel (ajánlott)', 'elallas-for-woo' ),
							'require_approval'   => __( 'Manuális jóváhagyás szükséges', 'elallas-for-woo' ),
							'block'              => __( 'Blokkolás (jogi kockázat!)', 'elallas-for-woo' ),
						]
					);
					?>
				</td>
			</tr>
		</table>
		<?php
	}
}

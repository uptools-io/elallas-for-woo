<?php
/**
 * Privacy Settings Tab.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin\Settings;

/**
 * Handles privacy / data retention settings.
 */
final class TabPrivacy implements TabInterface {

	use FieldRendererTrait;

	/**
	 * Tab slug.
	 *
	 * @return string
	 */
	public function id(): string {
		return 'privacy';
	}

	/**
	 * Tab label.
	 *
	 * @return string
	 */
	public function label(): string {
		return __( 'Adatvédelem', 'elallas-for-woo' );
	}

	/**
	 * Option keys handled by this tab.
	 *
	 * @return array<string, string>
	 */
	public function fields(): array {
		return [
			'store_ip'         => 'key',
			'store_user_agent' => 'key',
			'encrypt_email'    => 'bool',
			'retention_days'   => 'int',
		];
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		$modes = [
			'off'  => __( 'Ne tárolja', 'elallas-for-woo' ),
			'hash' => __( 'Hash (ajánlott)', 'elallas-for-woo' ),
			'full' => __( 'Teljes érték', 'elallas-for-woo' ),
		];
		?>
		<h2><?php esc_html_e( 'Adatvédelem és adatmegőrzés', 'elallas-for-woo' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="store_ip"><?php esc_html_e( 'IP cím tárolása', 'elallas-for-woo' ); ?></label></th>
				<td><?php $this->render_select( 'store_ip', $modes ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="store_user_agent"><?php esc_html_e( 'User agent tárolása', 'elallas-for-woo' ); ?></label></th>
				<td><?php $this->render_select( 'store_user_agent', $modes ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'E-mail titkosítás', 'elallas-for-woo' ); ?></th>
				<td><?php $this->render_checkbox( 'encrypt_email', __( 'Vásárlói e-mail cím titkosított tárolása', 'elallas-for-woo' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="retention_days"><?php esc_html_e( 'Adatmegőrzés (nap)', 'elallas-for-woo' ); ?></label></th>
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

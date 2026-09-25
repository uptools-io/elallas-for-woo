<?php
/**
 * GARAN section of the "Szavatosság és GARAN" settings tab.
 *
 * M0 skeleton: valid inputs for every GARAN option; the status rows
 * (e-mail image capability, file integrity, block-checkout check) come in M1a.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin\Settings;

use LightweightPlugins\Elallas\Options;

/**
 * Static renderer for the GARAN settings section.
 */
final class ComplianceGaranSection {

	/**
	 * Render the section.
	 *
	 * @return void
	 */
	public static function render(): void {
		?>
		<h3><?php esc_html_e( 'GARAN címke', 'elallas-for-woo' ); ?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Bekapcsolás', 'elallas-for-woo' ); ?></th>
				<td>
					<?php self::checkbox( 'garan_enabled', __( 'GARAN címke megjelenítése', 'elallas-for-woo' ) ); ?>
					<p class="description"><?php esc_html_e( 'Bekapcsolva a címke a rendelés gomb előtt mindig megjelenik (45/2014. 15. § (1), kötelező).', 'elallas-for-woo' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="garan_product_mode"><?php esc_html_e( 'Termékoldal', 'elallas-for-woo' ); ?></label></th>
				<td>
					<?php
					TabCompliance::render_limited_select(
						'garan_product_mode',
						[
							'nested'      => __( 'Beágyazott, kattintásra teljes', 'elallas-for-woo' ),
							'full'        => __( 'Teljes címke a kosárgomb alatt', 'elallas-for-woo' ),
							'description' => __( 'Teljes címke a leírás alatt', 'elallas-for-woo' ),
							'gallery'     => __( 'Galériában', 'elallas-for-woo' ),
						],
						[ 'nested', 'full' ]
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Megjelenítési helyek', 'elallas-for-woo' ); ?></th>
				<td>
					<?php self::checkbox( 'garan_display_cart', __( 'Kosároldal, az expressz fizetés és a pénztár gombok előtt', 'elallas-for-woo' ) ); ?><br />
					<?php self::checkbox( 'garan_display_email', __( 'Rendelési e-mail', 'elallas-for-woo' ) ); ?><br />
					<?php self::checkbox( 'garan_display_archive', __( 'Terméklista (listaoldal)', 'elallas-for-woo' ) ); ?>
					<p class="description"><?php esc_html_e( 'A címke csak azoknál a termékeknél jelenik meg, ahol a termékszerkesztőben be van kapcsolva. Ha a termékoldal Elementor Pro vagy testreszabott blokksablon, helyezd el az [elallas_garan_label] shortcode-ot a kosárgomb alá.', 'elallas-for-woo' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render a checkbox bound to an option key.
	 *
	 * @param string $name  Option key.
	 * @param string $label Checkbox label.
	 * @return void
	 */
	private static function checkbox( string $name, string $label ): void {
		printf(
			'<label><input type="checkbox" id="%1$s" name="%2$s[%1$s]" value="1" %3$s /> %4$s</label>',
			esc_attr( $name ),
			esc_attr( Options::OPTION_NAME ),
			checked( (bool) Options::get( $name ), true, false ),
			esc_html( $label )
		);
	}
}

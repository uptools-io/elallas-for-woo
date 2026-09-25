<?php
/**
 * GARAN section of the "Szavatosság és GARAN" settings tab.
 *
 * Inputs for every GARAN option plus status rows (e-mail image, official
 * file integrity, block-checkout check).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin\Settings;

use LightweightPlugins\Elallas\Compliance\GaranSource;
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
						[ 'nested', 'full', 'description' ]
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
			<tr>
				<th scope="row"><?php esc_html_e( 'Állapot', 'elallas-for-woo' ); ?></th>
				<td><?php self::render_status(); ?></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Status rows: e-mail image, file integrity, block-checkout check link.
	 *
	 * @return void
	 */
	private static function render_status(): void {
		$ok = GaranSource::integrity_ok();

		echo '<p>' . esc_html__( 'E-mail címke képként: 1.1.1-től; addig szöveges tájékoztatás (évek, gyártó, modell, Your Europe link, termékoldal-link).', 'elallas-for-woo' ) . '</p>';
		printf(
			'<p>%1$s <strong style="color:%2$s">%3$s</strong></p>',
			esc_html__( 'Hivatalos GARAN fájlok sértetlensége:', 'elallas-for-woo' ),
			esc_attr( $ok ? '#00703c' : '#b32d2e' ),
			$ok ? esc_html__( 'rendben', 'elallas-for-woo' ) : esc_html__( 'HIBA – a címke nem jelenik meg, telepítsd újra a plugint', 'elallas-for-woo' )
		);

		if ( function_exists( 'wc_get_checkout_url' ) ) {
			printf(
				'<p><a href="%1$s" target="_blank" rel="noopener">%2$s</a> <span class="description">%3$s</span></p>',
				esc_url( add_query_arg( 'elallas_slot_check', '1', wc_get_checkout_url() ) ),
				esc_html__( 'Blokkos pénztár ellenőrzése', 'elallas-for-woo' ),
				esc_html__( '(tegyél egy terméket a kosárba; az oldal alján megjelenő sáv jelzi, hogy az értesítés és a GARAN a rendelés gomb előtt van-e)', 'elallas-for-woo' )
			);
		}
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

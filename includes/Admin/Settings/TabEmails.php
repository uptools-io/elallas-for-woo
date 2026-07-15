<?php
/**
 * Emails Settings Tab.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin\Settings;

/**
 * Handles e-mail notification settings.
 */
final class TabEmails implements TabInterface {

	use FieldRendererTrait;

	/**
	 * Tab slug.
	 *
	 * @return string
	 */
	public function id(): string {
		return 'emails';
	}

	/**
	 * Tab label.
	 *
	 * @return string
	 */
	public function label(): string {
		return __( 'E-mailek', 'elallas-for-woo' );
	}

	/**
	 * Option keys handled by this tab.
	 *
	 * @return array<string, string>
	 */
	public function fields(): array {
		return [
			'email_customer_enabled' => 'bool',
			'email_admin_enabled'    => 'bool',
			'email_status_enabled'   => 'bool',
			'email_admin_recipient'  => 'email_list',
			'email_from_name'        => 'text',
			'email_from_address'     => 'text',
			'email_customer_extra'   => 'textarea',
			'email_order_text'       => 'textarea',
			'email_policy_enabled'   => 'bool',
			'email_policy_url'       => 'url',
			'email_policy_label'     => 'text',
		];
	}

	/**
	 * Render the tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h2><?php esc_html_e( 'E-mail értesítések', 'elallas-for-woo' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Vásárlói visszaigazolás', 'elallas-for-woo' ); ?></th>
				<td><?php $this->render_checkbox( 'email_customer_enabled', __( 'Visszaigazoló e-mail a vásárlónak (tartós adathordozó)', 'elallas-for-woo' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Admin értesítés', 'elallas-for-woo' ); ?></th>
				<td><?php $this->render_checkbox( 'email_admin_enabled', __( 'Értesítés az adminisztrátornak új elállásról', 'elallas-for-woo' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Státusz e-mail', 'elallas-for-woo' ); ?></th>
				<td><?php $this->render_checkbox( 'email_status_enabled', __( 'Vásárló értesítése státuszváltozáskor', 'elallas-for-woo' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="email_admin_recipient"><?php esc_html_e( 'Admin címzett', 'elallas-for-woo' ); ?></label></th>
				<td><?php $this->render_text( 'email_admin_recipient', __( 'Több címzett is megadható, vesszővel, pontosvesszővel vagy szóközzel elválasztva. Üresen hagyva az oldal adminisztrátori e-mail címe.', 'elallas-for-woo' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="email_customer_extra"><?php esc_html_e( 'Vásárlói e-mail extra szöveg', 'elallas-for-woo' ); ?></label></th>
				<td><?php $this->render_textarea( 'email_customer_extra', __( 'A visszaigazoló e-mail aljához fűzött szöveg (pl. visszaküldési cím, ügyfélszolgálat). A tárgyat és a fejlécet a WooCommerce → Beállítások → E-mailek alatt szabhatod testre, a teljes sablont pedig a témád elallas-for-woo/emails/ mappájában írhatod felül.', 'elallas-for-woo' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="email_order_text"><?php esc_html_e( 'Rendelési e-mail elállási szövege', 'elallas-for-woo' ); ?></label></th>
				<td>
					<?php $this->render_textarea( 'email_order_text', __( 'A WooCommerce rendelési e-mailekbe fűzött szöveg az elállási link köré (csak akkor jelenik meg, ha az Általános fülön a „Rendelési e-mailben" megjelenítés be van kapcsolva). A {link} helyőrző helyére a beállított elállási oldalra mutató hivatkozás kerül; ha nincs {link} a szövegben, a hivatkozás a szöveg után kerül. Üresen hagyva csak a hivatkozás jelenik meg.', 'elallas-for-woo' ) ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Tájékoztató / ÁSZF link', 'elallas-for-woo' ); ?></th>
				<td><?php $this->render_checkbox( 'email_policy_enabled', __( 'Hivatkozás elhelyezése minden elállási e-mail alján (új lapon nyílik)', 'elallas-for-woo' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="email_policy_url"><?php esc_html_e( 'Tájékoztató URL', 'elallas-for-woo' ); ?></label></th>
				<td><?php $this->render_text( 'email_policy_url', __( 'A hivatkozott oldal címe (pl. ÁSZF, elállási vagy adatkezelési tájékoztató). Üresen hagyva a WooCommerce → Beállítások → Speciális alatt beállított ÁSZF oldal érvényes.', 'elallas-for-woo' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="email_policy_label"><?php esc_html_e( 'Tájékoztató link felirata', 'elallas-for-woo' ); ?></label></th>
				<td><?php $this->render_text( 'email_policy_label', __( 'A hivatkozás megjelenő szövege az e-mailekben.', 'elallas-for-woo' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="email_from_name"><?php esc_html_e( 'Feladó neve', 'elallas-for-woo' ); ?></label></th>
				<td><?php $this->render_text( 'email_from_name', __( 'A plugin e-mailjeinek feladó neve. Üresen hagyva a WooCommerce beállítás érvényes.', 'elallas-for-woo' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="email_from_address"><?php esc_html_e( 'Feladó e-mail címe', 'elallas-for-woo' ); ?></label></th>
				<td><?php $this->render_text( 'email_from_address', __( 'A plugin e-mailjeinek feladó (From) címe. Állítsd egy figyelt postafiókra, hogy a vásárlói válaszok is megérkezzenek. Üresen hagyva a WooCommerce alapértelmezett feladó címe.', 'elallas-for-woo' ) ); ?></td>
			</tr>
		</table>
		<?php
	}
}

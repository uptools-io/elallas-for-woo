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
			'email_admin_recipient'  => 'text',
			'email_customer_extra'   => 'textarea',
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
				<td><?php $this->render_text( 'email_admin_recipient', __( 'Üresen hagyva az oldal adminisztrátori e-mail címe.', 'elallas-for-woo' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><label for="email_customer_extra"><?php esc_html_e( 'Vásárlói e-mail extra szöveg', 'elallas-for-woo' ); ?></label></th>
				<td><?php $this->render_textarea( 'email_customer_extra', __( 'A visszaigazoló e-mail aljához fűzött szöveg (pl. visszaküldési cím, ügyfélszolgálat). A tárgyat és a fejlécet a WooCommerce → Beállítások → E-mailek alatt szabhatod testre, a teljes sablont pedig a témád elallas-for-woo/emails/ mappájában írhatod felül.', 'elallas-for-woo' ) ); ?></td>
			</tr>
		</table>
		<?php
	}
}

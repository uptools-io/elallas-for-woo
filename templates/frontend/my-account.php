<?php
/**
 * My Account: customer's withdrawal cases.
 *
 * @package LightweightPlugins\Elallas
 *
 * @var array  $cases    Array of WithdrawalCase objects.
 * @var string $form_url Withdrawal page URL.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h2><?php esc_html_e( 'Elállási ügyeim', 'elallas-for-woo' ); ?></h2>

<?php if ( empty( $cases ) ) : ?>
	<p><?php esc_html_e( 'Még nincs rögzített elállási ügye.', 'elallas-for-woo' ); ?></p>
<?php else : ?>
	<table class="elallas-items shop_table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Ügyazonosító', 'elallas-for-woo' ); ?></th>
				<th><?php esc_html_e( 'Rendelés', 'elallas-for-woo' ); ?></th>
				<th><?php esc_html_e( 'Beküldve', 'elallas-for-woo' ); ?></th>
				<th><?php esc_html_e( 'Státusz', 'elallas-for-woo' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $cases as $elallas_case ) : ?>
				<tr>
					<td><?php echo esc_html( $elallas_case->case_number ); ?></td>
					<td><?php echo esc_html( $elallas_case->order_number ); ?></td>
					<td><?php echo esc_html( (string) $elallas_case->submitted_at ); ?></td>
					<td><?php echo esc_html( $elallas_case->status_label() ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>

<?php if ( '' !== (string) $form_url ) : ?>
	<p class="elallas-actions">
		<a class="button elallas-button" href="<?php echo esc_url( (string) $form_url ); ?>">
			<?php esc_html_e( 'Új elállási nyilatkozat', 'elallas-for-woo' ); ?>
		</a>
	</p>
<?php endif; ?>

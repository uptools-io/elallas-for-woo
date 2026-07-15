<?php
/**
 * Status update email (HTML).
 *
 * @package LightweightPlugins\Elallas
 *
 * @var \LightweightPlugins\Elallas\Models\WithdrawalCase     $case
 * @var array<int, \LightweightPlugins\Elallas\Models\CaseItem> $items
 * @var string                                                $email_heading
 * @var bool                                                  $sent_to_admin
 * @var bool                                                  $plain_text
 * @var string                                                $status_message
 * @var \WC_Email                                             $email
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

$elallas_status_message = isset( $status_message ) ? trim( (string) $status_message ) : '';

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p><?php esc_html_e( 'Tisztelt Vásárlónk!', 'elallas-for-woo' ); ?></p>

<p>
	<?php
	printf(
		/* translators: %s: case number */
		esc_html__( 'Tájékoztatjuk, hogy %s azonosítójú elállási ügyének állapota megváltozott.', 'elallas-for-woo' ),
		'<strong>' . esc_html( $case->case_number ) . '</strong>'
	);
	?>
</p>

<table cellspacing="0" cellpadding="6" border="1" style="width: 100%; border: 1px solid #e5e5e5; margin-bottom: 20px;">
	<tr>
		<th scope="row" style="text-align: left; padding: 12px;"><?php esc_html_e( 'Ügyazonosító', 'elallas-for-woo' ); ?></th>
		<td style="padding: 12px;"><?php echo esc_html( $case->case_number ); ?></td>
	</tr>
	<tr>
		<th scope="row" style="text-align: left; padding: 12px;"><?php esc_html_e( 'Jelenlegi státusz', 'elallas-for-woo' ); ?></th>
		<td style="padding: 12px;"><?php echo esc_html( $case->status_label() ); ?></td>
	</tr>
</table>

<?php if ( '' !== $elallas_status_message ) : ?>
<h3 style="margin-bottom:6px;"><?php esc_html_e( 'A kereskedő üzenete', 'elallas-for-woo' ); ?></h3>
<div style="border-left:4px solid #c3c4c7; background:#f6f7f7; padding:10px 14px; margin-bottom:20px;">
	<?php echo wp_kses_post( wpautop( $elallas_status_message ) ); ?>
</div>
<?php endif; ?>

<p><?php esc_html_e( 'Ha kérdése van az elállási ügyével kapcsolatban, kérjük, válaszoljon erre az e-mailre, vagy vegye fel a kapcsolatot ügyfélszolgálatunkkal. A vételárat legkésőbb az elállás kézhezvételétől számított 14 napon belül visszatérítjük.', 'elallas-for-woo' ); ?></p>

<?php
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_url() + esc_html().
echo \LightweightPlugins\Elallas\Emails\EmailManager::policy_link();

do_action( 'woocommerce_email_footer', $email );

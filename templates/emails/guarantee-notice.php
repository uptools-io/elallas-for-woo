<?php
/**
 * Harmonised legal-guarantee notice in customer order e-mails (HTML).
 *
 * Theme override: yourtheme/elallas-for-woo/emails/guarantee-notice.php.
 * The official image and the link to the QR-code target must stay unchanged.
 *
 * @package LightweightPlugins\Elallas
 *
 * @var string $label      Label (plain text).
 * @var string $png_url    Official colour PNG URL ('' when none is published).
 * @var bool   $show_image Whether to embed the image.
 * @var bool   $attached   Whether the PNG is attached to this e-mail.
 * @var string $alt        Image alternative text.
 * @var string $link_url   QR-code target URL.
 * @var string $link_label Visible short link text.
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<table class="elallas-guarantee-notice" role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 24px;">
	<tr>
		<td style="padding:0;">
			<h2 style="margin:0 0 12px;"><?php echo esc_html( $label ); ?></h2>
			<?php if ( $show_image ) : ?>
				<p style="margin:0 0 8px;">
					<a href="<?php echo esc_url( $png_url ); ?>" target="_blank">
						<img src="<?php echo esc_url( $png_url ); ?>" width="600" alt="<?php echo esc_attr( $alt ); ?>" style="display:block;width:100%;max-width:600px;height:auto;border:0;" />
					</a>
				</p>
				<p style="margin:0 0 8px;">
					<a href="<?php echo esc_url( $png_url ); ?>" target="_blank"><?php esc_html_e( 'Teljes méretben', 'elallas-for-woo' ); ?></a>
				</p>
			<?php endif; ?>
			<?php if ( $attached ) : ?>
				<p style="margin:0 0 8px;"><?php esc_html_e( 'A tájékoztatót mellékletben csatoltuk.', 'elallas-for-woo' ); ?></p>
			<?php endif; ?>
			<p style="margin:0;">
				<a href="<?php echo esc_url( $link_url ); ?>" target="_blank"><?php echo esc_html( $link_label ); ?></a>
			</p>
		</td>
	</tr>
</table>

<?php
/**
 * Customer confirmation email (plain text).
 *
 * @package LightweightPlugins\Elallas
 *
 * @var \LightweightPlugins\Elallas\Models\WithdrawalCase     $case
 * @var array<int, \LightweightPlugins\Elallas\Models\CaseItem> $items
 * @var string                                                $email_heading
 * @var bool                                                  $sent_to_admin
 * @var bool                                                  $plain_text
 * @var \WC_Email                                             $email
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

echo '= ' . esc_html( wp_strip_all_tags( $email_heading ) ) . " =\n\n";

echo esc_html__( 'Tisztelt Vásárlónk!', 'elallas-for-woo' ) . "\n\n";

echo esc_html__( 'Megerősítjük, hogy elállási nyilatkozatát tartós adathordozón rögzítettük.', 'elallas-for-woo' ) . "\n\n";

echo esc_html__( 'Ügyazonosító', 'elallas-for-woo' ) . ': ' . esc_html( $case->case_number ) . "\n";
echo esc_html__( 'Beérkezés időpontja', 'elallas-for-woo' ) . ': ' . esc_html( (string) $case->submitted_at ) . "\n";
echo esc_html__( 'Rendelésszám', 'elallas-for-woo' ) . ': ' . esc_html( $case->order_number ) . "\n\n";

if ( ! empty( $items ) ) {
	echo esc_html__( 'Az elállással érintett termékek', 'elallas-for-woo' ) . ":\n";

	foreach ( $items as $elallas_item ) {
		echo '- ' . esc_html( $elallas_item->product_name_snapshot ) . ' x ' . esc_html( (string) $elallas_item->qty_withdrawn ) . "\n";
	}

	echo "\n";
}

printf(
	/* translators: %s: shop name */
	esc_html__( 'A nyilatkozatot a következő kereskedő felé tette meg: %s.', 'elallas-for-woo' ),
	esc_html( get_bloginfo( 'name' ) )
);
echo "\n\n";

echo esc_html__( 'Az elállás feldolgozásáról és a visszatérítésről a kereskedő külön értesíti Önt. A vételárat legkésőbb az elállás kézhezvételétől számított 14 napon belül visszatérítjük.', 'elallas-for-woo' ) . "\n\n";

echo esc_html__( 'Kérjük, őrizze meg ezt az e-mailt, amely az elállás visszaigazolásaként szolgál.', 'elallas-for-woo' ) . "\n\n";

$elallas_extra = \LightweightPlugins\Elallas\Integrations\Multilingual::translate_option_string( 'email_customer_extra' );
if ( '' !== trim( $elallas_extra ) ) {
	echo esc_html( wp_strip_all_tags( $elallas_extra ) ) . "\n\n";
}

echo esc_html( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );

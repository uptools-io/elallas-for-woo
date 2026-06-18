<?php
/**
 * Default legal and message texts.
 *
 * Bundled samples — NOT legal advice. Merchants must validate with their own
 * terms of service (ÁSZF) and a Hungarian e-commerce lawyer.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Data;

/**
 * Provides default Hungarian texts used across the plugin.
 */
final class DefaultTexts {

	/**
	 * Default consumer-facing withdrawal declaration text.
	 *
	 * @return string
	 */
	public static function declaration(): string {
		return __( 'Alulírott fogyasztó kijelentem, hogy a megjelölt rendelésben szereplő termékek / szolgáltatások vonatkozásában elállási jogomat gyakorlom.', 'elallas-for-woo' );
	}

	/**
	 * Default submission acknowledgement text. Placeholders: {submitted_at}, {case_number}.
	 *
	 * @return string
	 */
	public static function confirmation(): string {
		return __( 'Elállási nyilatkozatát rendszerünk rögzítette. A nyilatkozat beérkezésének időpontja: {submitted_at}. Az ügy azonosítója: {case_number}.', 'elallas-for-woo' );
	}

	/**
	 * Legal disclaimer shown in admin next to editable legal texts.
	 *
	 * @return string
	 */
	public static function disclaimer(): string {
		return __( 'A szövegminta nem minősül jogi tanácsadásnak. Használat előtt ellenőriztesd a webshopodra vonatkozó ÁSZF-fel és jogi szakértővel.', 'elallas-for-woo' );
	}

	/**
	 * Neutral identification error (never reveals which field was wrong).
	 *
	 * @return string
	 */
	public static function neutral_error(): string {
		return __( 'A megadott adatok alapján nem található elállásra alkalmas rendelés, vagy a rendelés nem jogosult online elállási nyilatkozat beküldésére.', 'elallas-for-woo' );
	}

	/**
	 * Admin warning shown for cases received past the configured deadline.
	 *
	 * @return string
	 */
	public static function past_deadline_warning(): string {
		return __( 'A rendszer számítása szerint az elállási nyilatkozat a beállított határidőn túl érkezett. Kérjük, manuálisan ellenőrizze a teljesítés, átadás vagy kézbesítés időpontját, valamint az ÁSZF és a vonatkozó jogszabályok alapján alkalmazandó szabályokat.', 'elallas-for-woo' );
	}

	/**
	 * Warning shown when a merchant marks a product as a withdrawal exception.
	 *
	 * @return string
	 */
	public static function exception_warning(): string {
		return __( 'A kivétel beállítása jogi kockázatot hordozhat. Ellenőriztesd az ÁSZF-fel és jogásszal.', 'elallas-for-woo' );
	}
}

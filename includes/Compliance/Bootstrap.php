<?php
/**
 * Compliance module wiring (harmonised notice + GARAN label).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

use LightweightPlugins\Elallas\Options;

/**
 * Instantiates the compliance modules according to their toggles.
 */
final class Bootstrap {

	/**
	 * Front-end / shared modules (called from Plugin::__construct()).
	 *
	 * @return void
	 */
	public static function boot(): void {
		new EmailContext();          // Always: only tracks state.
		new GaranSnapshot();         // Always: the line-item snapshot is independent of the module toggle.
		new ComplianceAssets();      // Registers only.
		new CheckoutSlot();          // Prints only when it received markup.
		new ComplianceShortcodes();  // Returns '' when the module is off.

		if ( Options::get( 'notice_enabled' ) ) {
			new NoticeHooks();
			new NoticeEmail();
		}
		if ( Options::get( 'garan_enabled' ) ) {
			new GaranHooks();
			new GaranEmail();
		}

		/**
		 * Fires after the compliance modules were instantiated.
		 */
		do_action( 'elallas_compliance_boot' );
	}

	/**
	 * Admin modules (called at the end of Plugin::init_admin()).
	 *
	 * @return void
	 */
	public static function boot_admin(): void {
		new \LightweightPlugins\Elallas\Admin\GaranProductFields(); // Independent of garan_enabled.
		new \LightweightPlugins\Elallas\Admin\ComplianceNotice();
		new \LightweightPlugins\Elallas\Admin\GaranVariationFields(); // P1; later: Admin\ProductInfoFields (1.1.1), Admin\GaranImportExport (1.2.0).
	}
}

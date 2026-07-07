<?php
/**
 * Block registrar.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Blocks;

/**
 * Registers Gutenberg blocks for the elallas-for-woo plugin.
 */
final class BlockRegistrar {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register' ] );
	}

	/**
	 * Register all blocks from their block.json metadata.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! function_exists( 'register_block_type_from_metadata' ) ) {
			return;
		}

		$block_path = ELALLAS_FOR_WOO_PATH . 'blocks/withdrawal-form';

		if ( ! file_exists( $block_path . '/block.json' ) ) {
			return;
		}

		register_block_type_from_metadata( $block_path );

		$this->set_script_translations();
	}

	/**
	 * Load JavaScript translations for the block editor script.
	 *
	 * The editor script uses wp.i18n.__(); this tells WordPress to fetch the
	 * matching `elallas-for-woo-{locale}-{md5}.json` translation file from the
	 * languages directory for the current locale.
	 *
	 * @return void
	 */
	private function set_script_translations(): void {
		if ( ! function_exists( 'wp_set_script_translations' ) || ! function_exists( 'generate_block_asset_handle' ) ) {
			return;
		}

		$handle = generate_block_asset_handle( 'elallas/withdrawal-form', 'editorScript' );

		wp_set_script_translations(
			$handle,
			'elallas-for-woo',
			ELALLAS_FOR_WOO_PATH . 'languages'
		);
	}
}

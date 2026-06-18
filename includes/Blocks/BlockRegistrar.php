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
	}
}

<?php
/**
 * LegacyTemplateScope tests.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\Elallas\Compliance\LegacyTemplateScope;

/**
 * @covers \LightweightPlugins\Elallas\Compliance\LegacyTemplateScope
 */
final class LegacyTemplateScopeTest extends TestCase {

	protected function tearDown(): void {
		LegacyTemplateScope::reset();
	}

	public function test_outside_by_default(): void {
		$this->assertFalse( LegacyTemplateScope::inside() );
	}

	public function test_legacy_template_block_enters_and_render_leaves(): void {
		$block = [ 'blockName' => 'woocommerce/legacy-template' ];

		$this->assertSame( $block, LegacyTemplateScope::on_block_data( $block ) );
		$this->assertTrue( LegacyTemplateScope::inside() );

		$this->assertSame( '<div>x</div>', LegacyTemplateScope::on_rendered( '<div>x</div>' ) );
		$this->assertFalse( LegacyTemplateScope::inside() );
	}

	public function test_other_blocks_are_ignored(): void {
		foreach ( [ [ 'blockName' => 'woocommerce/add-to-cart-form' ], [ 'blockName' => null ], [], 'not-an-array' ] as $block ) {
			LegacyTemplateScope::on_block_data( $block );
		}

		$this->assertFalse( LegacyTemplateScope::inside() );
	}

	public function test_nesting_is_counted(): void {
		$block = [ 'blockName' => 'woocommerce/legacy-template' ];

		LegacyTemplateScope::on_block_data( $block );
		LegacyTemplateScope::on_block_data( $block );
		LegacyTemplateScope::on_rendered( '' );
		$this->assertTrue( LegacyTemplateScope::inside() );

		LegacyTemplateScope::on_rendered( '' );
		$this->assertFalse( LegacyTemplateScope::inside() );
	}

	public function test_leave_never_goes_negative(): void {
		LegacyTemplateScope::leave();
		LegacyTemplateScope::leave();
		LegacyTemplateScope::enter();

		$this->assertTrue( LegacyTemplateScope::inside() );
	}
}

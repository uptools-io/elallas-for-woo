<?php
/**
 * EmailContext tests.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\Elallas\Compliance\EmailContext;

/**
 * @covers \LightweightPlugins\Elallas\Compliance\EmailContext
 */
final class EmailContextTest extends TestCase {

	protected function tearDown(): void {
		EmailContext::reset();
	}

	public function test_customer_ids(): void {
		foreach ( [ 'customer_processing_order', 'customer_completed_order', 'customer_on_hold_order', 'customer_invoice' ] as $id ) {
			$this->assertTrue( EmailContext::is_customer_email( $id ), $id );
		}
	}

	public function test_admin_and_unknown_ids(): void {
		foreach ( [ 'new_order', 'cancelled_order', 'failed_order', 'customer_note', 'customer_reset_password', '' ] as $id ) {
			$this->assertFalse( EmailContext::is_customer_email( $id ), $id );
		}
	}

	public function test_capture_and_reset(): void {
		$this->assertSame( '', EmailContext::current() );
		$this->assertFalse( EmailContext::in_customer_email() );

		$email     = new \stdClass();
		$email->id = 'customer_processing_order';
		EmailContext::capture( null, false, false, $email );
		$this->assertSame( 'customer_processing_order', EmailContext::current() );
		$this->assertTrue( EmailContext::in_customer_email() );

		// A following admin e-mail overwrites the id.
		EmailContext::set( 'new_order' );
		$this->assertFalse( EmailContext::in_customer_email() );

		EmailContext::reset();
		$this->assertSame( '', EmailContext::current() );
	}

	public function test_capture_without_email_object(): void {
		EmailContext::set( 'customer_invoice' );
		EmailContext::capture( null, false, false, null );
		$this->assertSame( '', EmailContext::current() );
	}

	public function test_email_render_decision(): void {
		$this->assertFalse( EmailContext::is_email_render( '', false, 0, 0 ) );
		$this->assertFalse( EmailContext::is_email_render( '', false, 2, 2 ), 'Closed e-mails earlier in the request.' );
		$this->assertTrue( EmailContext::is_email_render( 'new_order', false, 0, 0 ) );
		$this->assertTrue( EmailContext::is_email_render( '', true, 0, 0 ) );
		$this->assertTrue( EmailContext::is_email_render( '', false, 1, 0 ), 'Header opened, footer not yet.' );
		$this->assertTrue( EmailContext::is_email_render( '', false, 3, 2 ) );
	}
}

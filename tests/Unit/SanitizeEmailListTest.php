<?php
/**
 * Tests for Options::sanitize_email_list() — the admin-recipient normaliser.
 *
 * Reproduces issue #25: a comma/semicolon/space separated list of admin
 * e-mail addresses must be normalised into a validated, comma-separated
 * string so every recipient is notified, not only the first.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\Elallas\Options;

/**
 * @covers \LightweightPlugins\Elallas\Options::sanitize_email_list
 */
final class SanitizeEmailListTest extends TestCase {

	public function test_single_address_is_returned_unchanged(): void {
		$this->assertSame( 'shop@example.com', Options::sanitize_email_list( 'shop@example.com' ) );
	}

	/**
	 * The bug: only the first of several comma-separated addresses was used.
	 */
	public function test_comma_separated_addresses_are_all_kept(): void {
		$this->assertSame(
			'a@example.com, b@example.com',
			Options::sanitize_email_list( 'a@example.com, b@example.com' )
		);
	}

	/**
	 * Different separators are all normalised to ", ".
	 *
	 * @dataProvider provide_separators
	 *
	 * @param string $raw Raw input.
	 */
	public function test_separators_are_normalised( string $raw ): void {
		$this->assertSame(
			'a@example.com, b@example.com',
			Options::sanitize_email_list( $raw )
		);
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function provide_separators(): array {
		return array(
			'comma'            => array( 'a@example.com,b@example.com' ),
			'comma + space'    => array( 'a@example.com, b@example.com' ),
			'semicolon'        => array( 'a@example.com;b@example.com' ),
			'space'            => array( 'a@example.com b@example.com' ),
			'mixed + newlines' => array( "a@example.com;  b@example.com\n" ),
		);
	}

	public function test_invalid_addresses_are_dropped(): void {
		$this->assertSame(
			'valid@example.com',
			Options::sanitize_email_list( 'valid@example.com, not-an-email, @nope' )
		);
	}

	public function test_duplicates_are_removed_case_insensitively(): void {
		$this->assertSame(
			'a@example.com',
			Options::sanitize_email_list( 'a@example.com, A@example.com, a@Example.com' )
		);
	}

	/**
	 * @dataProvider provide_empty
	 *
	 * @param string $raw Raw input.
	 */
	public function test_empty_or_all_invalid_yields_empty_string( string $raw ): void {
		$this->assertSame( '', Options::sanitize_email_list( $raw ) );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function provide_empty(): array {
		return array(
			'empty'        => array( '' ),
			'whitespace'   => array( "  \t\n" ),
			'separators'   => array( ' , ; , ' ),
			'all invalid'  => array( 'nope, also-nope' ),
		);
	}
}

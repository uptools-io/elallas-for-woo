<?php
/**
 * NoticeUrlPolicy tests.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LightweightPlugins\Elallas\Compliance\NoticeUrlPolicy;

/**
 * @covers \LightweightPlugins\Elallas\Compliance\NoticeUrlPolicy
 */
final class NoticeUrlPolicyTest extends TestCase {

	private const LINK = 'https://europa.eu/youreurope/citizens/consumers/shopping/guarantees-returns/index_hu.htm';
	private const IMG  = 'https://shop.test/wp-content/plugins/elallas-for-woo/assets/notice/notice-hu.svg';

	/**
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public function linkProvider(): array {
		return [
			'unchanged'          => [ self::LINK, self::LINK ],
			'europa.eu https'    => [ 'https://europa.eu/youreurope/guarantees', 'https://europa.eu/youreurope/guarantees' ],
			'subdomain'          => [ 'https://commission.europa.eu/x', 'https://commission.europa.eu/x' ],
			'uppercase host'     => [ 'https://EUROPA.EU/x', 'https://EUROPA.EU/x' ],
			'http rejected'      => [ 'http://europa.eu/x', self::LINK ],
			'lookalike rejected' => [ 'https://evileuropa.eu/x', self::LINK ],
			'suffix trick'       => [ 'https://europa.eu.evil.test/x', self::LINK ],
			'userinfo trick'     => [ 'https://europa.eu@evil.test/x', self::LINK ],
			'javascript'         => [ 'javascript:alert(1)', self::LINK ],
			'relative'           => [ '/youreurope', self::LINK ],
			'non-string'         => [ [ 'x' ], self::LINK ],
			'empty'              => [ '', self::LINK ],
		];
	}

	/**
	 * @dataProvider linkProvider
	 *
	 * @param mixed  $candidate Filtered value.
	 * @param string $expected  Expected result.
	 */
	public function test_link( $candidate, string $expected ): void {
		$this->assertSame( $expected, NoticeUrlPolicy::link( $candidate, self::LINK ) );
	}

	/**
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public function imageProvider(): array {
		return [
			'unchanged'        => [ self::IMG, self::IMG ],
			'cdn same name'    => [ 'https://cdn.test/a/b/notice-hu.svg', 'https://cdn.test/a/b/notice-hu.svg' ],
			'cdn with query'   => [ 'https://cdn.test/notice-hu.svg?ver=1', 'https://cdn.test/notice-hu.svg?ver=1' ],
			'other language'   => [ 'https://cdn.test/notice-de.svg', self::IMG ],
			'other file'       => [ 'https://cdn.test/my-notice.png', self::IMG ],
			'png instead'      => [ 'https://cdn.test/notice-hu.png', self::IMG ],
			'data uri'         => [ 'data:image/svg+xml;base64,AAAA', self::IMG ],
			'javascript'       => [ 'javascript:notice-hu.svg', self::IMG ],
			'non-string'       => [ null, self::IMG ],
		];
	}

	/**
	 * @dataProvider imageProvider
	 *
	 * @param mixed  $candidate Filtered value.
	 * @param string $expected  Expected result.
	 */
	public function test_image( $candidate, string $expected ): void {
		$this->assertSame( $expected, NoticeUrlPolicy::image( $candidate, self::IMG ) );
	}
}

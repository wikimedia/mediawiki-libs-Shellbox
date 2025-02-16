<?php

namespace Shellbox\Tests;

use Shellbox\Shellbox;

/**
 * @coversNothing
 */
class ShellboxTest extends ShellboxTestCase {
	public static function provideEscape() {
		return [
			[
				[],
				''
			],
			[
				'',
				"''"
			],
			[
				'foo',
				"'foo'"
			],
			[
				'☎',
				"'☎'"
			],
			[
				"'",
				"''\'''"
			],
			[
				"a'b'c",
				"'a'\''b'\''c'"
			],
		];
	}

	/** @dataProvider provideEscape */
	public function testEscape( $input, $expected ) {
		$result = Shellbox::escape( $input );
		$this->assertSame( $expected, $result );
	}
}

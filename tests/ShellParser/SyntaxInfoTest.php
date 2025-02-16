<?php

namespace Shellbox\Tests\ShellParser;

use PHPUnit\Framework\TestCase;
use Shellbox\ShellParser\ShellParser;

/**
 * @coversNothing
 */
class SyntaxInfoTest extends TestCase {
	public static function provideGetFeatureList() {
		return [
			[
				'a',
				[]
			],
			[
				'a&',
				[ 'background' ]
			],
			[
				'"a\'"b&c',
				[ 'list', 'background' ]
			],
			[
				'a|b',
				[ 'pipeline' ],
			],
			[
				'"a|b"',
				[]
			],
			[
				'for a in b; do d; done',
				[ 'compound' ]
			],
			[
				'while a; do b; done',
				[ 'compound' ]
			],
			[
				'for p in a; do b & done',
				[ 'compound', 'background' ]
			],
			[
				'(a;b)',
				[ 'compound' ]
			],
			[
				"a\nb",
				[], // fixme?
			],
			[
				'a>b',
				[ 'redirect' ]
			],
			[
				'a<b',
				[ 'redirect' ]
			],
			[
				'<a b',
				[ 'redirect' ]
			],
			[
				'a 2>&1',
				[ 'redirect' ] // fixme?
			],
			[
				'a "$(command)" b',
				[ 'command_expansion' ]
			],
			[
				'a $b',
				[ 'parameter' ]
			],
			[
				'a $@',
				[ 'parameter' ]
			],
			[
				'a ${b%%x}',
				[ 'exotic_expansion', 'parameter' ]
			],
			[
				'a=b c',
				[ 'assignment' ]
			],
			[
				'a b=c',
				[]
			]
		];
	}

	/** @dataProvider provideGetFeatureList */
	public function testGetFeatureList( $input, $expected ) {
		$parser = new ShellParser;
		$tree = $parser->parse( $input );
		$this->assertSame( $expected, $tree->getInfo()->getFeatureList() );
	}

	public static function provideGetLiteralArgv() {
		return [
			// Bare syntax
			[
				'',
				null
			],
			[
				'a b c',
				[ 'a', 'b', 'c' ]
			],
			[
				'$a',
				null
			],
			[
				'a 2>&1',
				[ 'a' ]
			],
			[
				'a|b',
				null
			],
			[
				'a \\\\ b',
				[ 'a', '\\', 'b' ]
			],
			[
				'a \b c',
				[ 'a', 'b', 'c' ]
			],
			[
				'a=b c d',
				[ 'c', 'd' ]
			],
			[
				'for a in b; do d; done',
				null
			],
			[
				'a;b',
				null
			],
			[
				'(a)',
				null
			],
			[
				'${b%%x}',
				null
			],

			// Single quotes
			[
				'\'a\'b c',
				[ 'ab', 'c' ]
			],
			[
				'a \'\\\' b',
				[ 'a', '\\', 'b' ]
			],

			// Double quotes
			[
				'a"b"c',
				[ 'abc' ]
			],
			[
				'"a\\"b"',
				[ 'a"b' ]
			],
			[
				'"a$b"',
				null
			],
			[
				'"$(command)"',
				null
			],
		];
	}

	/**
	 * @dataProvider provideGetLiteralArgv
	 */
	public function testGetLiteralArgv( $input, $expected ) {
		$parser = new ShellParser;
		$tree = $parser->parse( $input );
		$this->assertSame( $expected, $tree->getInfo()->getLiteralArgv() );
	}
}

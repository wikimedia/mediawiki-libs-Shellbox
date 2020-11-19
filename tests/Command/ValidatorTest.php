<?php

namespace Shellbox\Tests\Command;

use PHPUnit\Framework\TestCase;
use Shellbox\Command\BoxedCommand;
use Shellbox\Command\ValidationError;
use Shellbox\Command\Validator;

class ValidatorTest extends TestCase {
	public static function provideValidate() {
		return [
			'echo' => [
				// Input
				[
					'command' => 'echo a'
				],
				// Config
				[
				],
				// Error
				false
			],
			'echo strictly' => [
				// Input
				[
					'command' => 'echo a'
				],
				// Config
				[
					'options' => [],
					'argv' => [ 'echo', 'a' ],
					'inputFiles' => [],
					'outputFiles' => [],
					'outputGlobs' => [],
				],
				// Error
				false
			],
			'echo command fail' => [
				// Input
				[
					'command' => 'echo a'
				],
				// Config
				[
					'argv' => [ 'echo', 'b' ],
				],
				// Error
				'argv[1] does not match the expected value "b"'
			],
			'unextractable argv allowed' => [
				// Input
				[
					'command' => '(a)',
				],
				// Config
				[],
				// Error
				false
			],
			'unextractable argv fails' => [
				// Input
				[
					'command' => '(a)',
				],
				// Config
				[
					'argv' => [ 'a' ]
				],
				// Error
				'argv may only contain literal strings'
			],
			'cat' => [
				// Input
				[
					'command' => 'cat a b',
					'inputFiles' => [
						'a' => [],
						'b' => []
					]
				],
				// Config
				[
					'inputFiles' => [
						'a' => [],
						'b' => []
					],
				],
				// Error
				false
			],
			'cat with input file order reversed' => [
				// Input
				[
					'command' => 'cat a b',
					'inputFiles' => [
						'b' => [],
						'a' => []
					]
				],
				// Config
				[
					'inputFiles' => [
						'a' => [],
						'b' => []
					],
				],
				// Error
				false
			],
			'cat with invalid input file' => [
				// Input
				[
					'command' => 'cat a b',
					'inputFiles' => [
						'a' => [],
						'b' => [],
						'c' => []
					]
				],
				// Config
				[
					'inputFiles' => [
						'a' => []
					],
				],
				// Error
				'Unexpected input file "b"'
			],
			'expected output file' => [
				// Input
				[
					'command' => 'cat a b',
					'outputFiles' => [
						'a' => [],
					]
				],
				// Config
				[
					'outputFiles' => [ 'a' => [] ],
				],
				// Error
				false
			],
			'failure due to unexpected output file' => [
				// Input
				[
					'command' => 'cat a b',
					'outputFiles' => [
						'a' => [],
					]
				],
				// Config
				[
					'outputFiles' => [],
				],
				// Error
				'Unexpected output file "a"'
			],
			'expected output glob' => [
				// Input
				[
					'command' => 'cmd',
					'outputGlobs' => [
						'command.com' => [
							'prefix' => 'command',
							'extension' => 'com',
						],
					]
				],
				// Config
				[
					'outputGlobs' => [ 'command*.com' => [] ],
				],
				// Error
				false
			],
			'failure due to unexpected output glob' => [
				// Input
				[
					'command' => 'cmd',
					'outputGlobs' => [
						'command.com' => [
							'prefix' => 'command',
							'extension' => 'com',
						],
					]
				],
				// Config
				[
					'outputGlobs' => [ 'command*.exe' => [] ],
				],
				// Error
				'Unexpected glob "command*.com"',
			],
			'failure due to unexpected option' => [
				// Input
				[
					'command' => 'echo a',
					'cpuLimit' => 1,
				],
				// Config
				[
					'options' => [],
					'argv' => [ 'echo', 'a' ],
				],
				// Error
				'unexpected option cpuLimit'
			],
			'option matches allow' => [
				// Input
				[
					'command' => 'echo a',
					'cpuLimit' => 1,
				],
				// Config
				[
					'options' => [ 'cpuLimit' => [ 'allow' => 'integer' ] ],
					'argv' => [ 'echo', 'a' ],
				],
				// Error
				false
			],
			'option matches allow with float in array' => [
				// Input
				[
					'command' => 'echo a',
					'cpuLimit' => 1.1,
				],
				// Config
				[
					'options' => [
						'cpuLimit' => [
							'allow' => [ 'integer', 'float' ]
						]
					],
					'argv' => [ 'echo', 'a' ],
				],
				// Error
				false
			],
			'option fails array allow' => [
				// Input
				[
					'command' => 'echo a',
					'cpuLimit' => 'thirty',
				],
				// Config
				[
					'options' => [
						'cpuLimit' => [
							'allow' => [ 'integer', 'float' ]
						]
					],
					'argv' => [ 'echo', 'a' ],
				],
				// Error
				'cpuLimit must be one of: integer, float',
			],
			'option matches literal' => [
				// Input
				[
					'command' => 'echo a',
					'cpuLimit' => 1,
				],
				// Config
				[
					'options' => [ 'cpuLimit' => 1 ],
					'argv' => [ 'echo', 'a' ],
				],
				// Error
				false
			],
			'failure due to non-matching option' => [
				// Input
				[
					'command' => 'echo a',
					'cpuLimit' => 1,
				],
				// Config
				[
					'options' => [],
					'argv' => [ 'echo', 'a' ],
				],
				// Error
				'unexpected option cpuLimit'
			],
			'shell feature pass' => [
				// Input
				[
					'command' => 'a > b 2>&1',
				],
				// Config
				[
					'shellFeatures' => [ 'redirect' ]
				],
				// Error
				false
			],
			'shell feature multi pass' => [
				// Input
				[
					'command' => 'a > b && c',
				],
				// Config
				[
					'shellFeatures' => [ 'redirect', 'list' ]
				],
				// Error
				false
			],
			'shell feature fail' => [
				// Input
				[
					'command' => 'a && b',
				],
				// Config
				[
					'shellFeatures' => [ 'pipeline' ]
				],
				// Error
				'Command uses unexpected shell feature: list'
			],
			'target typo fails' => [
				// Input
				[
					'command' => 'a',
				],
				// Config
				[
					'shellFeetures' => [ 'pipeline' ]
				],
				// Error
				'Unknown validation target "shellFeetures"'
			],
			'failure due to allow-like wishful thinking' => [
				// Input
				[
					'command' => 'a',
				],
				// Config
				[
					'argv' => [ [ 'regexMatch' => '/a/' ] ]
				],
				// Error
				'Unknown configured restriction type "regexMatch"'
			],
			'literal match' => [
				// Input
				[
					'command' => 'a',
				],
				// Config
				[
					'argv' => [ [ 'allow' => 'literal' ] ]
				],
				// Error
				false
			],
			'literal mismatch' => [
				// Input
				[
					'command' => 'a',
				],
				// Config
				[
					'argv' => [
						[ 'allow' => 'literal' ],
						[ 'allow' => 'literal' ]
					]
				],
				// Error
				'argv[1] must be of type literal'
			],
			'relative match' => [
				// Input
				[
					'command' => 'a b',
				],
				// Config
				[
					'argv' => [
						'a',
						[ 'allow' => 'relative' ],
					]
				],
				// Error
				false
			],
			'relative mismatch' => [
				// Input
				[
					'command' => 'a con.txt',
				],
				// Config
				[
					'argv' => [
						'a',
						[ 'allow' => 'relative' ],
					]
				],
				// Error
				'argv[1] must be of type relative'
			],
			'any matches missing' => [
				// Input
				[
					'command' => 'a',
				],
				// Config
				[
					'argv' => [
						[ 'allow' => 'any' ],
						[ 'allow' => 'any' ],
					]
				],
				// Error
				false
			],
			'allow fails due to unknown type' => [
				// Input
				[
					'command' => 'a',
				],
				// Config
				[
					'argv' => [
						[ 'allow' => 'complex' ],
					]
				],
				// Error
				'unknown validation type "complex"'
			],
		];
	}

	/** @dataProvider provideValidate */
	public function testValidate( $input, $config, $expectedMessage ) {
		$command = new class extends BoxedCommand {
			public function __construct() {
			}
		};
		$command->setClientData( $input );
		$command->routeName( 'test' );
		$validator = new Validator( [
			'allowedRoutes' => [ 'test' ],
			'routeSpecs' => [ 'test' => $config ]
		] );
		if ( !$expectedMessage ) {
			$validator->validate( $command );
			$this->assertTrue( true );
		} else {
			try {
				$validator->validate( $command );
				$this->assertFalse( true, "Expected ValidationError" );
			} catch ( ValidationError $e ) {
				$this->assertSame(
					"Shellbox command validation error: $expectedMessage",
					$e->getMessage() );
			}
		}
	}

	public function testInvalidRoute() {
		$command = new class extends BoxedCommand {
			public function __construct() {
			}
		};
		$command->params( 'a' );
		$command->routeName( 'test1' );
		$validator = new Validator( [
			'allowedRoutes' => [ 'test2' ],
		] );
		$this->expectExceptionMessage( 'Shellbox command validation error: ' .
			'The route "test1" is not in the list of allowed routes' );
		$validator->validate( $command );
	}
}

<?php

namespace Shellbox\Tests\RPC;

use Shellbox\ShellboxError;

/**
 * TODO: https://gerrit.wikimedia.org/r/c/mediawiki/tools/codesniffer/+/733852
 * phpcs:disable MediaWiki.Commenting.FunctionComment.MissingParamTag
 */
trait RpcClientTests {

	public static function provideTestRpcClient() {
		yield 'No sources' => [
			'functionName' => 'str_repeat',
			'params' => [ 'a', 3 ],
			'options' => [],
			'expected' => 'aaa',
		];
		yield 'Function call with sources' => [
			'functionName' => 'wfTestFunction',
			'params' => [ 'aaa' ],
			'options' => [
				'sources' => [
					__DIR__ . '/sample_script.php',
				],
			],
			'expected' => 'test_aaa',
		];
		yield 'Function call using autoloaded class' => [
			'functionName' => 'wfTestFunctionUsingClass',
			'params' => [ 'aaa' ],
			'options' => [
				'sources' => [
					__DIR__ . '/sample_script.php',
				],
			],
			'expected' => 'using_class_test_aaa',
		];
		require_once __DIR__ . '/NotAutoloadedClass.php';
		yield 'Function call using not autoloaded class' => [
			'functionName' => 'wfTestFunctionUsingNonAutoloadedClass',
			'params' => [ 'aaa' ],
			'options' => [
				'sources' => [
					__DIR__ . '/sample_script.php',
				],
				'classes' => [
					\NotAutoloadedClass::class
				]
			],
			'expected' => 'aaa_BOO!',
		];
		yield 'With binary' => [
			'functionName' => 'strpos',
			'params' => [ 123, 1 ],
			'options' => [
				'binary' => true,
			],
			'expected' => '0',
		];
	}

	/**
	 * @suppress PhanUndeclaredMethod
	 * @dataProvider provideTestRpcClient
	 */
	public function testRpcClient(
		string $functionName,
		array $params,
		array $options,
		$expected
	) {
		$this->assertSame(
			$expected,
			$this->createClient()->call(
				'test_route',
				$functionName,
				$params,
				$options
		) );
	}

	/**
	 * @suppress PhanUndeclaredMethod
	 */
	public function testRpcClientError() {
		$this->expectException( ShellboxError::class );
		$this->createClient()->call(
			'test_route',
			'wfError',
			[],
			[
				'sources' => [
					__DIR__ . '/sample_script.php',
				],
			]
		);
	}

	abstract protected function createClient( $key = null );
}

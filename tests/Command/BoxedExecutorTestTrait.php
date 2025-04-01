<?php

namespace Shellbox\Tests\Command;

use GuzzleHttp\Psr7\Utils;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use PHPUnit\Framework\Assert;
use Psr\Log\LoggerInterface;
use Shellbox\Command\BoxedCommand;
use Shellbox\Command\BoxedExecutor;
use Shellbox\Shellbox;
use Shellbox\TempDirManager;

/**
 * A trait providing tests for both client/server and local modes
 */
trait BoxedExecutorTestTrait {
	/**
	 * Parameters for running fake-shell.php
	 * @return array
	 */
	private function getFakeShellParams() {
		return [ PHP_BINARY, '-d', 'variables_order=EGPCS', dirname( __DIR__ ) . '/fake-shell.php' ];
	}

	abstract protected function createExecutor( ?LoggerInterface $logger = null ): BoxedExecutor;

	/**
	 * @param string $path
	 */
	abstract protected function getFileServerUrl( $path ): string;

	/**
	 * @param LoggerInterface|null $logger
	 * @return BoxedCommand
	 */
	private function createFakeShellCommand( ?LoggerInterface $logger = null ) {
		return $this->createExecutor( $logger )->createCommand()
			->routeName( 'test' )
			->params( $this->getFakeShellParams() );
	}

	private function createTestLogger(): array {
		$logger = new Logger( 'shellbox' );
		$logger->pushProcessor( new PsrLogMessageProcessor );
		$handler = new TestHandler;
		$logger->pushHandler( $handler );
		return [ $logger, $handler ];
	}

	public function testExecuteEcho() {
		$command = $this->createFakeShellCommand()
			->params( 'echo', 'hello' );
		$result = $command->execute();
		Assert::assertSame( "hello\n", $result->getStdout() );
		Assert::assertSame( '', $result->getStderr() );
		Assert::assertSame( 0, $result->getExitCode() );
		Assert::assertSame( [], $result->getFileNames() );
	}

	public function testCopy() {
		$result = $this->createFakeShellCommand()
			->inputFileFromString( 'src', 'foo' )
			->outputFileToString( 'dest' )
			->params( 'cp', 'src', 'dest' )
			->execute();
		Assert::assertSame( '', $result->getStdout() );
		Assert::assertSame( '', $result->getStderr() );
		Assert::assertSame( 0, $result->getExitCode() );
		Assert::assertSame( 'foo', $result->getFileContents( 'dest' ) );
	}

	public function testCopyWithDirs() {
		$result = $this->createFakeShellCommand()
			->inputFileFromString( 'i/src', 'foo' )
			->outputFileToString( 'o/dest' )
			->params( 'cp', 'i/src', 'o/dest' )
			->execute();
		Assert::assertSame( '', $result->getStdout() );
		Assert::assertSame( '', $result->getStderr() );
		Assert::assertSame( 0, $result->getExitCode() );
		Assert::assertSame( 'foo', $result->getFileContents( 'o/dest' ) );
	}

	public function testGlob() {
		$result = $this->createFakeShellCommand()
			->inputFileFromString( 'i/src1.txt', '1' )
			->inputFileFromString( 'i/src2.txt', '2' )
			->outputGlobToString( 'o/src', 'txt' )
			->params( 'cp', 'i/src1.txt', 'i/src2.txt', 'o' )
			->execute();
		Assert::assertSame( '', $result->getStdout() );
		Assert::assertSame( '', $result->getStderr() );
		Assert::assertSame( 0, $result->getExitCode() );
		Assert::assertSame( '1', $result->getFileContents( 'o/src1.txt' ) );
		Assert::assertSame( '2', $result->getFileContents( 'o/src2.txt' ) );
	}

	public function testInputFileFromFile() {
		$manager = new TempDirManager(
			sys_get_temp_dir() . '/test-' . Shellbox::getUniqueString() );
		$inputPath = $manager->preparePath( 'input' );
		file_put_contents( $inputPath, 'hello' );
		$result = $this->createFakeShellCommand()
			->inputFileFromFile( 'input', $inputPath )
			->params( 'cat', 'input' )
			->execute();
		Assert::assertSame( 'hello', $result->getStdout() );
		Assert::assertSame( '', $result->getStderr() );
		Assert::assertSame( 0, $result->getExitCode() );
	}

	public function testInputFileFromStream() {
		$stream = Utils::streamFor( fopen( 'php://memory', 'w+' ) );
		$stream->write( 'hello' );
		$result = $this->createFakeShellCommand()
			->inputFileFromStream( 'input', $stream )
			->params( 'cat', 'input' )
			->execute();
		Assert::assertSame( 'hello', $result->getStdout() );
		Assert::assertSame( '', $result->getStderr() );
		Assert::assertSame( 0, $result->getExitCode() );
	}

	public function testInputFileFromUrl() {
		$result = $this->createFakeShellCommand()
			->inputFileFromUrl( 'input', $this->getFileServerUrl( 'download/hello' ) )
			->params( 'cat', 'input' )
			->execute();
		Assert::assertSame( 'hello', $result->getStdout() );
		Assert::assertSame( '', $result->getStderr() );
		Assert::assertSame( 0, $result->getExitCode() );
	}

	public function testInputFileFromUrlWithHeaders() {
		[ $logger, $handler ] = $this->createTestLogger();
		$cmd = $this->createFakeShellCommand( $logger );
		$cmd
			->inputFile(
				'input',
				$cmd->newInputFileFromUrl( $this->getFileServerUrl( 'validate-headers/input1' ) )
					->headers( [ 'foo' => 'bar' ] )
			)
			->params( 'echo' )
			->execute();
		Assert::assertTrue( $handler->hasRecordThatContains(
			'/validate-headers/input1 -> 200', Logger::INFO ) );
	}

	public function testOutputFileToFile() {
		$manager = new TempDirManager(
			sys_get_temp_dir() . '/test-' . Shellbox::getUniqueString() );
		$outPath = $manager->preparePath( 'client-out' );
		$result = $this->createFakeShellCommand()
			->outputFileToFile( 'server-out', $outPath )
			->unsafeParams( 'echo test > server-out' )
			->execute();
		Assert::assertSame( '', $result->getStdout() );
		Assert::assertSame( '', $result->getStderr() );
		Assert::assertSame( 0, $result->getExitCode() );
		Assert::assertSame( "test\n", file_get_contents( $outPath ) );
		Assert::assertSame( "test\n", $result->getFileContents( 'server-out' ) );
	}

	public function testOutputFileToUrl() {
		[ $logger, $handler ] = $this->createTestLogger();
		$result = $this->createFakeShellCommand( $logger )
			->inputFileFromString( 'input', 'hello' )
			->outputFileToUrl( 'output', $this->getFileServerUrl( 'upload/hello' ) )
			->unsafeParams( 'cp input output' )
			->execute();
		Assert::assertSame( 0, $result->getExitCode() );
		Assert::assertTrue( $handler->hasRecordThatContains(
			'/upload/hello -> 204', Logger::INFO ) );
	}

	public function testOutputFileToUrlWithHeaders() {
		[ $logger, $handler ] = $this->createTestLogger();
		$cmd = $this->createFakeShellCommand( $logger );
		$result = $cmd
			->inputFileFromString( 'input', 'hello' )
			->outputFile(
				'output',
				$cmd
					->newOutputFileToUrl( $this->getFileServerUrl( 'validate-headers/output1' ) )
					->enableMwContentHash()
					->headers( [ 'foo' => 'bar' ] )
			)
			->unsafeParams( 'cp input output' )
			->execute();
		Assert::assertSame( 0, $result->getExitCode() );
		Assert::assertTrue( $handler->hasRecordThatContains(
			'/validate-headers/output1 -> 202', Logger::INFO ) );
	}

	public function testOutputGlobToFile() {
		$manager = new TempDirManager(
			sys_get_temp_dir() . '/test-' . Shellbox::getUniqueString() );
		$outPath = $manager->preparePath( 'out1.txt' );
		$result = $this->createFakeShellCommand()
			->outputGlobToFile( 'out', 'txt', $manager->prepareBasePath() )
			->unsafeParams( 'echo test > out1.txt' )
			->execute();
		Assert::assertSame( '', $result->getStdout() );
		Assert::assertSame( '', $result->getStderr() );
		Assert::assertSame( 0, $result->getExitCode() );
		Assert::assertSame( "test\n", file_get_contents( $outPath ) );
		Assert::assertSame( "test\n", $result->getFileContents( 'out1.txt' ) );
	}

	public function testOutputGlobToUrl() {
		[ $logger, $handler ] = $this->createTestLogger();
		$result = $this->createFakeShellCommand( $logger )
			->inputFileFromString( 'inputs/f1.txt', 'out/f1.txt' )
			->inputFileFromString( 'inputs/f2.txt', 'out/f2.txt' )
			->outputGlobToUrl( 'outputs/f', 'txt', $this->getFileServerUrl( 'upload/out/' ) )
			->unsafeParams( 'cp inputs/f1.txt inputs/f2.txt outputs/' )
			->execute();
		Assert::assertSame( 0, $result->getExitCode() );
		Assert::assertTrue( $handler->hasRecordThatContains(
			'/upload/out/f1.txt -> 204', Logger::INFO ) );
		Assert::assertTrue( $handler->hasRecordThatContains(
			'/upload/out/f2.txt -> 204', Logger::INFO ) );
	}

	public function testMissingOutput() {
		$result = $this->createFakeShellCommand()
			->outputFileToString( 'dest' )
			->params( 'echo' )
			->execute();
		Assert::assertSame( "\n", $result->getStdout() );
		Assert::assertSame( '', $result->getStderr() );
		Assert::assertSame( 0, $result->getExitCode() );
		Assert::assertSame( null, $result->getFileContents( 'dest' ) );
	}

	public function testInputIsOutput() {
		$command = $this->createFakeShellCommand();
		$result = $command
			->inputFileFromString( 'dest', 'hello' )
			->outputFile(
				'dest',
				$command->newOutputFileToString()
			)
			->outputFileToString( 'dest' )
			->params( 'echo' )
			->execute();
		Assert::assertSame( 0, $result->getExitCode() );
		Assert::assertSame( true, $result->wasReceived( 'dest' ) );
		Assert::assertSame( 'hello', $result->getFileContents( 'dest' ) );
	}

	public function testRequiredExitCode() {
		$command = $this->createFakeShellCommand();
		$result = $command
			->inputFileFromString( 'dest', 'hello' )
			->outputFile(
				'dest',
				$command->newOutputFileToString()
					->requireExitCode( 0 )
			)
			->params( 'false' )
			->execute();
		Assert::assertSame( 1, $result->getExitCode() );
		Assert::assertSame( false, $result->wasReceived( 'dest' ) );
	}

	public function testStdin() {
		$input = '';
		for ( $i = 0; $i < 256; $i++ ) {
			$input .= chr( $i );
		}
		$result = $this->createFakeShellCommand()
			->stdin( $input )
			->params( 'cat' )
			->execute();
		Assert::assertSame( $input, $result->getStdout() );
		Assert::assertSame( '', $result->getStderr() );
		Assert::assertSame( 0, $result->getExitCode() );
	}

	public function testStderr() {
		$result = $this->createFakeShellCommand()
			->stdin( 'hello' )
			->unsafeParams( 'cat 1>&2' )
			->execute();
		Assert::assertSame( 'hello', $result->getStderr() );
		Assert::assertSame( '', $result->getStdout() );
		Assert::assertSame( 0, $result->getExitCode() );
	}

	public function testEnvironment() {
		$result = $this->createFakeShellCommand()
			->environment( [ 'SB_FOO' => 'bar' ] )
			->params( 'env' )
			->execute();
		Assert::assertStringContainsString( 'SB_FOO=bar', $result->getStdout() );
	}

	public function testT69870() {
		// Test for T69870 and T199989
		$command = $this->createFakeShellCommand();
		$output = $command
			->params( 'string-repeat', '*', 333333 )
			->execute()
			->getStdout();
		Assert::assertEquals( 333333, strlen( trim( $output ) ) );
	}

	public function testLogStderr() {
		[ $logger, $handler ] = $this->createTestLogger();
		$executor = $this->createExecutor( $logger );
		$command = $executor->createCommand()
			->routeName( 'test' )
			->params( $this->getFakeShellParams() )
			->params( 'echo', 'this is stderr' )
			->unsafeParams( '1>&2' );

		$command->execute();
		Assert::assertFalse( $handler->hasRecords( Logger::ERROR ) );

		$command->logStderr();
		$command->execute();
		Assert::assertTrue( $handler->hasRecordThatContains(
			'Error running', Logger::ERROR ) );
		$found = false;
		foreach ( $handler->getRecords() as $record ) {
			if ( ( $record['context']['error'] ?? '' ) === "this is stderr\n" ) {
				$found = true;
			}
		}
		Assert::assertTrue( $found );
	}

	public function testIncludeStderr() {
		$output = $this->createFakeShellCommand()
			->params( 'echo-x2', 'a', 'b' )
			->includeStderr()
			->execute()
			->getStdout();
		Assert::assertSame( "a\nb\n", $output );
	}

	public function testBadLocale() {
		$output = $this->createFakeShellCommand()
			->environment( [ 'LC_CTYPE' => 'zh_CN.gbk' ] )
			->params( 'env' )
			->execute()
			->getStdout();
		Assert::assertStringNotContainsStringIgnoringCase( 'zh_CN.gbk', $output );
	}
}

<?php

namespace Shellbox\Tests\Command;

use Shellbox\Command\Command;
use Shellbox\Shellbox;
use Shellbox\Tests\ShellboxTestCase;

class CommandTest extends ShellboxTestCase {
	/**
	 * Test that null values are skipped by params() and unsafeParams()
	 */
	public function testNullsAreSkipped() {
		// phpcs:disable Squiz.WhiteSpace.ScopeClosingBrace.ContentBefore
		$command = new class extends Command {};
		$command->params( 'echo', 'a', null, 'b' );
		$command->unsafeParams( 'c', null, 'd' );

		if ( PHP_OS_FAMILY === 'Windows' ) {
			$this->assertEquals( '"echo" "a" "b" c d', $command->getCommandString() );
		} else {
			$this->assertEquals( "'echo' 'a' 'b' c d", $command->getCommandString() );
		}
	}

	public function testUnsafeParams() {
		$command = new class extends Command {};
		$command->unsafeParams( ';;' );
		$this->assertEquals( ';;', $command->getCommandString() );
	}

	public function testUnsafeParamsArray() {
		$command = new class extends Command {};
		$command->unsafeParams( [ 'a;', 'b;' ] );
		$this->assertEquals( 'a; b;', $command->getCommandString() );
	}

	public function testReplaceParams() {
		$command = new class extends Command {};
		$command->params( 'a' );
		$command->replaceParams( 'b' );
		$this->assertEquals( Shellbox::escape( 'b' ), $command->getCommandString() );
	}

	public function testUnsafeCommand() {
		$command = new class extends Command {};
		$command->params( 'a' );
		$command->unsafeCommand( ';' );
		$this->assertEquals( ';', $command->getCommandString() );
	}
}

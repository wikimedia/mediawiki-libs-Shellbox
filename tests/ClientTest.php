<?php

namespace Shellbox\Tests;

use GuzzleHttp\Psr7\MultipartStream;
use RuntimeException;
use Shellbox\Multipart\MultipartReader;
use Shellbox\Shellbox;
use Shellbox\ShellboxError;

class ClientTest extends ClientServerTestCase {
	public function testSimpleCall() {
		$client = $this->createClient();
		$result = $client->call( 'test', 'pow', [ 2, 3 ] );
		$this->assertSame( 8, $result );
	}

	public static function triangular( $n ) {
		return $n * ( $n + 1 ) / 2;
	}

	protected function callSelf( $func, $args = [], $options = [] ) {
		$client = $this->createClient();
		return $client->call( 'test', [ self::class, $func ],
			$args, $options + [ 'classes' => [ self::class ] ] );
	}

	public function testCallClass() {
		$result = $this->callSelf( 'triangular', [ 5 ] );
		$this->assertSame( 15, $result );
	}

	public static function exception() {
		throw new RuntimeException( 'eee' );
	}

	public function testCallException() {
		$this->expectException( ShellboxError::class );
		$this->expectExceptionMessage( 'eee' );
		$this->callSelf( 'exception' );
	}

	public static function error() {
		trigger_error( 'fff', E_USER_ERROR );
	}

	public function testCallError() {
		$this->expectException( ShellboxError::class );
		$this->expectExceptionMessageMatches(
			'/PHP error in .* line \d*: fff/' );
		$this->callSelf( 'error' );
	}

	public static function force500() {
		header( 'HTTP/1.1 500 Internal Server Error' );
		exit;
	}

	public function testForce500() {
		$this->expectException( ShellboxError::class );
		$this->expectExceptionMessage(
			'Shellbox server returned status code 500' );
		$this->callSelf( 'force500' );
	}

	public static function forceBad200() {
		exit;
	}

	public function testForceBad200() {
		$this->expectException( ShellboxError::class );
		$this->expectExceptionMessage(
			'Shellbox server returned incorrect Content-Type' );
		$this->callSelf( 'forceBad200' );
	}

	public static function badContentDisposition( $disposition ) {
		$parts = [ [
			'name' => 'unused',
			'headers' => [ 'Content-Disposition' => $disposition ],
			'contents' => ''
		] ];
		$boundary = Shellbox::getUniqueString();
		$stream = new MultipartStream( $parts, $boundary );
		header( "Content-Type: multipart/mixed; boundary=\"$boundary\"" );
		echo $stream->getContents();
		exit;
	}

	public function testBadContentDisposition() {
		$this->expectException( ShellboxError::class );
		$this->expectExceptionMessage(
			'Unknown content disposition type' );
		$this->callSelf( 'badContentDisposition', [ 'garbage' ] );
	}

	public static function identity( $x ) {
		return $x;
	}

	public function testInvalidUtf8() {
		$input = '';
		for ( $i = 32; $i < 256; $i++ ) {
			$input .= chr( $i );
		}
		$this->expectException( ShellboxError::class );
		$this->expectExceptionMessageMatches( '/cannot be converted to JSON/' );
		$this->callSelf( 'identity', [ $input ] );
	}

	public function testBinary() {
		$input = '';
		for ( $i = 0; $i < 256; $i++ ) {
			$input .= chr( $i );
		}
		$result = $this->callSelf( 'identity', [ $input ], [ 'binary' => true ] );
		$this->assertSame( $input, $result );
	}

	public function testBoundaryCrossingChunkEnd() {
		$input = '';
		for ( $i = 0; $i < MultipartReader::CHUNK_SIZE - 4; $i++ ) {
			$input .= chr( 48 + $i % 32 );
		}
		$result = $this->callSelf( 'identity', [ $input ] );
		$this->assertSame( $input, $result );
	}

	public function testHmacFailure() {
		$this->expectException( ShellboxError::class );
		$this->expectExceptionMessageMatches( '/HMAC signature verification failed/' );
		$client = $this->createClient( 'fake key' );
		$client->call( 'test', [ self::class, 'identity' ],
			[ 1 ], [ 'classes' => [ self::class ] ] );
	}

}

<?php

namespace Shellbox\Tests;

use SebastianBergmann\CodeCoverage\CodeCoverage;
use Shellbox\FileUtils;
use Shellbox\Server;
use Shellbox\Shellbox;
use Shellbox\ShellboxError;

class TestServer {
	/**
	 * Test server entry point
	 *
	 * @param string $configPath
	 */
	public static function main( $configPath ) {
		if ( isset( $_SERVER['HTTP_X_SHELLBOX_COVER'] ) ) {
			$json = FileUtils::getContents( $configPath );
			$config = Shellbox::jsonDecode( $json );
			if ( !isset( $config['tempDir'] ) ) {
				throw new ShellboxError( 'tempDir is required' );
			}
			$tempDir = rtrim( $config['tempDir'], '/' );
			$suffix = Shellbox::getUniqueString();
			$coverPath = "$tempDir/sb-cover-$suffix";
			header( "X-Shellbox-Cover: $suffix" );
			$coverage = new CodeCoverage;
			// This ID will be discarded when the data is appended to the
			// client's coverage object
			$coverage->start( 'server' );
			Server::main( $configPath );
			$data = $coverage->stop();
			FileUtils::putContents( $coverPath, Shellbox::jsonEncode( $data ) );
		} else {
			Server::main( $configPath );
		}
	}
}

<?php

namespace Shellbox\Tests;

use RuntimeException;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Driver\PcovDriver;
use SebastianBergmann\CodeCoverage\Driver\XdebugDriver;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\Environment\Runtime;
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
			$coverageFilter = new Filter();
			$coverage = new CodeCoverage( self::selectDriver( $coverageFilter ), $coverageFilter );
			// This ID will be discarded when the data is appended to the
			// client's coverage object
			$coverage->start( 'server' );
			Server::main( $configPath );
			$data = $coverage->stop();
			FileUtils::putContents( $coverPath, serialize( $data ) );
		} else {
			Server::main( $configPath );
		}
	}

	/**
	 * originally cf https://github.com/sebastianbergmann/php-code-coverage/blob/8.0.2/src/CodeCoverage.php#L888-L908
	 *
	 * @param Filter $filter
	 *
	 * @return Driver
	 */
	private static function selectDriver( Filter $filter ): Driver {
		$runtime = new Runtime;

		if ( $runtime->hasPCOV() ) {
			return new PcovDriver( $filter );
		}

		if ( $runtime->hasXdebug() ) {
			return new XdebugDriver( $filter );
		}

		throw new RuntimeException( 'No code coverage driver available' );
	}
}

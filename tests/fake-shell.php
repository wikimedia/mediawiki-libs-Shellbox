<?php

namespace Shellbox\Tests;

require __DIR__ . '/../vendor/autoload.php';

use Shellbox\FileUtils;

/**
 * @param array $argv
 */
function fakeShellMain( $argv ) {
	if ( PHP_SAPI !== 'cli' ) {
		exit( 1 );
	}
	// @phan-file-suppress SecurityCheck-XSS

	array_shift( $argv );

	switch ( array_shift( $argv ) ) {
		case 'echo':
			foreach ( $argv as $i => $arg ) {
				if ( $i > 0 ) {
					echo ' ';
				}
				echo $arg;
			}
			echo "\n";
			exit( 0 );

		case 'cp':
			if ( count( $argv ) < 2 ) {
				fwrite( STDERR, "Source and dest are required\n" );
				exit( 1 );
			}
			$sources = array_slice( $argv, 0, -1 );
			$dest = array_slice( $argv, -1 )[0];
			$isDestDir = is_dir( $dest );
			if ( count( $sources ) > 1 && !$isDestDir ) {
				fwrite( STDERR, "Destination directory required\n" );
				exit( 1 );
			}
			if ( $isDestDir ) {
				$dest = rtrim( $dest, '/' ) . '/';
			}
			foreach ( $sources as $source ) {
				if ( $isDestDir ) {
					$destPath = $dest . basename( $source );
				} else {
					$destPath = $dest;
				}
				if ( !copy( $source, $destPath ) ) {
					fwrite( STDERR, "Error copying file\n" );
					exit( 1 );
				}
			}
			exit( 0 );

		case 'cat':
			if ( count( $argv ) === 0 ) {
				$argv[] = 'php://stdin';
			}
			foreach ( $argv as $arg ) {
				$stream = FileUtils::openInputFileStream( $arg );
				while ( !$stream->eof() ) {
					echo $stream->read( 4096 );
				}
				$stream->close();
			}
			exit( 0 );

		case 'env':
			foreach ( $_ENV as $name => $value ) {
				echo "$name=$value\n";
			}
			exit( 0 );

		case 'string-repeat':
			if ( count( $argv ) < 2 ) {
				fwrite( STDERR, "String and count are required\n" );
				exit( 1 );
			}
			echo str_repeat( $argv[0], (int)$argv[1] ) . "\n";
			exit( 0 );

		case 'echo-x2':
			if ( count( $argv ) < 2 ) {
				fwrite( STDERR, "stdout and stderr are required\n" );
				exit( 1 );
			}
			fwrite( STDOUT, $argv[0] . "\n" );
			fwrite( STDERR, $argv[1] . "\n" );
			exit( 0 );

		default:
			fwrite( STDERR, "Unrecognised command\n" );
			exit( 1 );
	}
}

fakeShellMain( $argv );

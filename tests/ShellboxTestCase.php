<?php
declare( strict_types = 1 );

namespace Shellbox\Tests;

use PHPUnit\Framework\TestCase;
use Shellbox\FileUtils;
use Shellbox\Shellbox;
use Shellbox\ShellboxError;

class ShellboxTestCase extends TestCase {
	private static ?array $config = null;

	/**
	 * Get a configuration variable from test-config.json
	 */
	protected static function getConfig( string $name ): mixed {
		$config = self::getAllConfig();
		if ( !isset( $config[$name] ) ) {
			throw new ShellboxError( "The configuration variable $name must be present in test-config.json" );
		}
		return $config[$name];
	}

	/**
	 * Get all config from test-config.json
	 */
	protected static function getAllConfig(): array {
		if ( self::$config === null ) {
			$configPath = __DIR__ . '/../config/test-config.json';
			$defaults = [
				'tempDir' => sys_get_temp_dir(),
				'port' => 8033,
				'fileServerPort' => 8034,
				'firejailProfile' => '',
				'allowUrlFiles' => true,
			];
			if ( file_exists( $configPath ) ) {
				self::$config = Shellbox::jsonDecode( FileUtils::getContents( $configPath ) )
					+ $defaults;
			} else {
				self::$config = $defaults;
			}
		}
		return self::$config;
	}
}

<?php

namespace Shellbox\Tests;

use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestResult;
use PHPUnit\Util\Test as TestUtil;
use RuntimeException;
use Shellbox\Client;
use Shellbox\FileUtils;
use Shellbox\Shellbox;

class ClientServerTestCase extends ShellboxTestCase {
	/** @var bool */
	private $collectCodeCoverage = false;
	/** @var string|null */
	private $serverCoveragePath;
	/** @var string|null */
	private static $secretKey;
	/** @var string|null */
	private static $url;
	/** @var BuiltinServerManager|null */
	private static $server;
	/** @var BuiltinServerManager|null */
	private static $fileServer;
	/** @var string|null */
	private static $fileServerUrl;

	protected function getAndDestroyServerCoverage() {
		if ( $this->serverCoveragePath !== null && file_exists( $this->serverCoveragePath ) ) {
			$data = unserialize( FileUtils::getContents( $this->serverCoveragePath ) );
			// phpcs:ignore Generic.PHP.NoSilencedErrors
			@unlink( $this->serverCoveragePath );
			$this->serverCoveragePath = null;
			return $data;
		} else {
			return null;
		}
	}

	protected function createHttpClient() {
		return new TestHttpClient( $this->collectCodeCoverage ?
			function ( $suffix ) {
				$this->serverCoveragePath = self::getConfig( 'tempDir' ) .
					'/sb-cover-' . $suffix;
			} : null
		);
	}

	/**
	 * @param string|null $key Override the client secret key to cause auth errors
	 * @return Client
	 */
	protected function createClient( $key = null ) {
		return new Client(
			$this->createHttpClient(),
			new Uri( self::$url ),
			(string)( $key ?? self::$secretKey ),
			[ 'allowUrlFiles' => true ]
		);
	}

	public function run( TestResult $result = null ): TestResult {
		if ( $result === null ) {
			$result = $this->createResult();
		}
		$coverage = $result->getCodeCoverage();
		$this->collectCodeCoverage = $coverage !== null &&
			TestUtil::requiresCodeCoverageDataCollection( $this );

		$result = parent::run( $result );
		if ( $this->collectCodeCoverage ) {
			$data = $this->getAndDestroyServerCoverage();
			if ( $data ) {
				$coverage->append( $data, $this );
			}
		}
		return $result;
	}

	/**
	 * phpcs:ignore MediaWiki.Commenting.FunctionAnnotations -- works for me
	 * @beforeClass
	 */
	public static function clientServerSetUpBeforeClass() {
		$port = self::getConfig( 'port' );
		self::$server = new BuiltinServerManager(
			$port,
			self::getConfig( 'tempDir' ) );

		$tempManager = self::$server->getTempDirManager();
		$configPath = $tempManager->preparePath( 'test-config.json' );
		self::$secretKey = Shellbox::getUniqueString();
		self::$url = "http://localhost:$port/sbtest.php";
		FileUtils::putContents(
			$configPath,
			Shellbox::jsonEncode( [
				'secretKey' => self::$secretKey,
				'url' => self::$url
			] + self::getAllConfig() )
		);

		$encConfigPath = var_export( $configPath, true );
		$encAutoloadPath = var_export( __DIR__ . '/../vendor/autoload.php', true );

		$entryPath = $tempManager->preparePath( 'sbtest.php' );
		FileUtils::putContents( $entryPath, <<<PHP
<?php
require $encAutoloadPath;
\Shellbox\Tests\TestServer::main( $encConfigPath );
PHP
		);

		self::$server->start();

		$client = new \GuzzleHttp\Client;
		$response = $client->request( 'GET',
			self::$url . "/healthz" );
		$ctype = $response->getHeaderLine( 'Content-Type' );
		if ( $ctype !== 'application/json' ) {
			throw new RuntimeException( "Invalid healthz response type: $ctype" );
		}
		$data = Shellbox::jsonDecode( $response->getBody()->getContents() );
		self::$server->setPid( $data['pid'] );
	}

	/**
	 * @after
	 */
	public function clientServerTearDown() {
		self::$server->checkIfRunning();
	}

	/**
	 * phpcs:ignore MediaWiki.Commenting.FunctionAnnotations -- works for me
	 * @afterClass
	 */
	public static function clientServerTearDownAfterClass() {
		if ( !self::$server ) {
			return;
		}
		self::$server->stop();
		self::$server = null;
	}

	/**
	 * phpcs:ignore MediaWiki.Commenting.FunctionAnnotations -- works for me
	 * @beforeClass
	 */
	public static function fileServerSetUpBeforeClass() {
		$port = self::getConfig( 'fileServerPort' );
		self::$fileServer = new BuiltinServerManager(
			$port,
			self::getConfig( 'tempDir' ) );
		self::$fileServerUrl = "http://localhost:$port/file-server.php/!";

		$tempManager = self::$fileServer->getTempDirManager();

		$fileServerEntryPath = $tempManager->preparePath( 'file-server.php' );
		$encAutoloadPath = var_export( __DIR__ . '/../vendor/autoload.php', true );
		FileUtils::putContents( $fileServerEntryPath, <<<PHP
<?php
require $encAutoloadPath;
\Shellbox\Tests\FileServer::main();
PHP
		);

		self::$fileServer->start();
		$client = new \GuzzleHttp\Client;
		$response = $client->request( 'GET', self::$fileServerUrl . "/healthz" );
		self::$fileServer->setPid( (int)$response->getBody()->getContents() );
	}

	/**
	 * @after
	 */
	public function fileServerTearDown() {
		self::$fileServer->checkIfRunning();
	}

	/**
	 * phpcs:ignore MediaWiki.Commenting.FunctionAnnotations -- works for me
	 * @afterClass
	 */
	public static function fileServerTearDownAfterClass() {
		if ( !self::$fileServer ) {
			return;
		}
		self::$fileServer->stop();
		self::$fileServer = null;
	}

	protected function getFileServerUrl( $path ): string {
		return self::$fileServerUrl . '/' . $path;
	}
}

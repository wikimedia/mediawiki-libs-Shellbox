<?php
declare( strict_types = 1 );

namespace Shellbox\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Uri;
use RuntimeException;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use Shellbox\Client;
use Shellbox\FileUtils;
use Shellbox\RPC\RpcClient;
use Shellbox\Shellbox;

class ClientServerTestCase extends ShellboxTestCase {
	private bool $collectCodeCoverage = false;
	private static ?string $serverCoveragePath;
	private static ?string $secretKey;
	private static string $url = '';
	private static ?BuiltinServerManager $server;
	private static ?BuiltinServerManager $fileServer;
	private static ?string $fileServerUrl;

	public static function collectAndMergeServerCoverage( CodeCoverage $coverage ): void {
		if ( self::$serverCoveragePath !== null && file_exists( self::$serverCoveragePath ) ) {
			$data = unserialize( FileUtils::getContents( self::$serverCoveragePath ) );
			unlink( self::$serverCoveragePath );
			self::$serverCoveragePath = null;
			if ( is_array( $data ) && isset( $data['codeCoverage'] ) ) {
				$coverage->getData( true )->merge( $data['codeCoverage'] );
				$coverage->setTests( array_merge( $coverage->getTests(), $data['testResults'] ) );
			}
		}
	}

	protected function createHttpClient(): TestHttpClient {
		return new TestHttpClient( $this->collectCodeCoverage ?
			function ( $suffix ) {
				self::$serverCoveragePath = self::getConfig( 'tempDir' ) .
					'/sb-cover-' . $suffix;
			} : null
		);
	}

	/**
	 * @param string|null $key Override the client secret key to cause auth errors
	 * @return Client
	 */
	protected function createClient( ?string $key = null ): RpcClient {
		return new Client(
			$this->createHttpClient(),
			new Uri( self::$url ),
			(string)( $key ?? self::$secretKey ),
			[ 'allowUrlFiles' => true ]
		);
	}

	/**
	 * @beforeClass
	 */
	public static function clientServerSetUpBeforeClass(): void {
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

		$client = new GuzzleClient;
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
	public function clientServerTearDown(): void {
		self::$server->checkIfRunning();
	}

	/**
	 * @afterClass
	 */
	public static function clientServerTearDownAfterClass(): void {
		if ( !self::$server ) {
			return;
		}
		self::$server->stop();
		self::$server = null;
	}

	protected function setUp(): void {
		$this->collectCodeCoverage = extension_loaded( 'xdebug' ) || extension_loaded( 'pcov' );
	}

	/**
	 * @beforeClass
	 */
	public static function fileServerSetUpBeforeClass(): void {
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
		$client = new GuzzleClient;
		$response = $client->request( 'GET', self::$fileServerUrl . "/healthz" );
		self::$fileServer->setPid( (int)$response->getBody()->getContents() );
	}

	/**
	 * @after
	 */
	public function fileServerTearDown(): void {
		self::$fileServer->checkIfRunning();
	}

	/**
	 * @afterClass
	 */
	public static function fileServerTearDownAfterClass(): void {
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

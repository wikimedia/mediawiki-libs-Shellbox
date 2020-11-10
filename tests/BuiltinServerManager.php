<?php

namespace Shellbox\Tests;

use Shellbox\Shellbox;
use Shellbox\TempDirManager;

class BuiltinServerManager {
	/** @var int */
	private $port;
	/** @var resource|null */
	private $proc;
	/** @var TempDirManager */
	private $tempDirManager;
	/** @var resource|null */
	private $outputFile;
	/** @var int|null */
	private $pid;

	/**
	 * @param int $port The port to listen on
	 * @param string $tempDirBase The directory in which a server root
	 *   directory will be created
	 */
	public function __construct( $port, $tempDirBase ) {
		$this->port = $port;
		$this->tempDirManager = new TempDirManager(
			$tempDirBase . '/sb-test-server-' . Shellbox::getUniqueString() );
	}

	/**
	 * Shut down the server when the object is destroyed
	 */
	public function __destruct() {
		$this->stop();
		$this->tempDirManager->teardown();
	}

	/**
	 * Start the server
	 */
	public function start() {
		// Make sure we can't connect to the server before we start it, that
		// indicates a server is still running from a previous invocation
		// phpcs:ignore Generic.PHP.NoSilencedErrors
		$sock = @fsockopen( 'localhost', $this->port, $errno, $errstr, 1 );
		if ( $sock ) {
			fclose( $sock );
			throw new \RuntimeException( "Found another server running on port {$this->port}" );
		}

		// Start the server
		$wdPath = $this->getTempDirManager()->prepareBasePath();
		$cmd = Shellbox::escape(
			PHP_BINARY,
			'-S', "localhost:{$this->port}",
			'-t', $wdPath
		);
		if ( PHP_OS_FAMILY !== 'Windows' ) {
			$cmd = "exec $cmd";
		}

		$this->outputFile = fopen(
			$this->getTempDirManager()->preparePath( 'server-out' ), 'w+' );
		$desc = [
			1 => $this->outputFile,
			2 => $this->outputFile
		];
		$pipes = [];
		$this->proc = proc_open( $cmd, $desc, $pipes, $wdPath );
		if ( !$this->proc ) {
			throw new \RuntimeException( "Unable to create server process" );
		}

		// Wait for the socket
		$startTime = microtime( true );
		$started = false;
		$sleepInterval = 10000;
		do {
			// phpcs:ignore Generic.PHP.NoSilencedErrors
			$sock = @fsockopen( 'localhost', $this->port, $errno, $errstr, 1 );
			if ( $sock ) {
				$started = true;
				fclose( $sock );
			} else {
				usleep( $sleepInterval );
				$sleepInterval *= 2;
			}
		} while ( !$started && microtime( true ) < $startTime + 10 );
		if ( !$started ) {
			$this->stop();
			throw new \RuntimeException( "Gave up waiting for server to start" );
		}

		$this->checkIfRunning();
	}

	/**
	 * Set the PID. It's difficult to determine the PID of the server as
	 * opposed to the controlling terminal, so ClientServerTestCase sends a
	 * healthz request to the server to determine the PID. This class is
	 * supposed to be independent of the Shellbox protocol so we don't
	 * implement the healthz ping here.
	 *
	 * @param int $pid
	 */
	public function setPid( $pid ) {
		$this->pid = $pid;
	}

	/**
	 * Check if the server is still running. If it's not, throw an exception.
	 * If setPid() has not been called yet, the PID is set here as a last resort.
	 *
	 * @throws \RuntimeException
	 */
	public function checkIfRunning() {
		$status = proc_get_status( $this->proc );
		if ( !$status ) {
			$this->clearProc();
			throw new \RuntimeException( 'Unable to get server status' );
		}
		if ( !$status['running'] ) {
			fseek( $this->outputFile, 0 );
			$output = stream_get_contents( $this->outputFile );
			$this->clearProc();
			throw new \RuntimeException(
				"Server terminated with status {$status['exitcode']}: " .
				$output
			);
		}
		if ( !$this->pid ) {
			$this->pid = $status['pid'];
		}
	}

	/**
	 * Clear status variables related to a running process
	 */
	private function clearProc() {
		$this->proc = null;
		$this->outputFile = null;
		$this->pid = null;
	}

	/**
	 * Stop the server. Either setPid() or checkIfRunning() must be called
	 * before this is called, in order to have a PID to kill.
	 *
	 * On Windows, taskkill is used. Otherwise, SIGTERM is sent. Then we use
	 * proc_close() to wait for the process to exit.
	 *
	 * proc_terminate() is completely non-functional per https://bugs.php.net/bug.php?id=33505
	 */
	public function stop() {
		if ( $this->proc ) {
			if ( !$this->pid ) {
				throw new \RuntimeException( 'Can\'t kill the server if we don\'t know its PID' );
			}
			if ( PHP_OS_FAMILY === 'Windows' ) {
				Shellbox::createUnboxedExecutor()->createCommand()
					->params( 'taskkill', '/pid', $this->pid )
					->execute();
			} elseif ( function_exists( 'posix_kill' ) ) {
				posix_kill( $this->pid, SIGTERM );
			} else {
				Shellbox::createUnboxedExecutor()->createCommand()
					->params( 'kill', $this->pid )
					->execute();
			}
			proc_close( $this->proc );
			$this->clearProc();
		}
	}

	/**
	 * Get the TempDirManager used to manage the server root
	 *
	 * @return TempDirManager
	 */
	public function getTempDirManager() {
		return $this->tempDirManager;
	}
}

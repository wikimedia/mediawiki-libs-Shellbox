<?php

namespace Shellbox\Tests;

use GuzzleHttp;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class TestHttpClient implements ClientInterface {
	/** @var callable|null */
	private $coverCallback;

	/**
	 * @param callable|null $coverCallback A function to call after a response
	 *   is received which will process the coverage data from the server. If
	 *   this is null, coverage data will not be requested from the server.
	 */
	public function __construct( $coverCallback = null ) {
		$this->coverCallback = $coverCallback;
	}

	protected function modifyRequest( RequestInterface $request ): RequestInterface {
		if ( $this->coverCallback ) {
			$request = $request->withHeader( 'X-Shellbox-Cover', '1' );
		}
		$request = $request->withHeader( 'User-Agent', 'Shellbox test client' );
		return $request;
	}

	protected function modifyResponse( ResponseInterface $response ): ResponseInterface {
		$header = $response->getHeader( 'X-Shellbox-Cover' );
		if ( $this->coverCallback && isset( $header[0] )
			&& preg_match( '/^[0-9a-z]+$/', $header[0] )
		) {
			( $this->coverCallback )( $header[0] );
		}
		return $response;
	}

	public function sendRequest( RequestInterface $request ): ResponseInterface {
		$request = $this->modifyRequest( $request );
		$xdebug = boolval( ini_get( 'xdebug.remote_enable' ) );
		$guzzleClient = new GuzzleHttp\Client( [ 'timeout' => $xdebug ? 0 : 5 ] );
		try {
			$response = $guzzleClient->send( $request );
		} catch ( RequestException $e ) {
			if ( $e->getResponse() ) {
				$response = $e->getResponse();
			} else {
				throw $e;
			}
		}
		return $this->modifyResponse( $response );
	}
}

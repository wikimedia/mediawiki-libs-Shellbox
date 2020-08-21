<?php

namespace Shellbox\Tests;

use GuzzleHttp;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Shellbox\HttpClientInterface;

class TestHttpClient implements HttpClientInterface {
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

	public function send( RequestInterface $request ): ResponseInterface {
		if ( $this->coverCallback ) {
			$request = $request->withHeader( 'X-Shellbox-Cover', '1' );
		}
		$guzzleClient = new GuzzleHttp\Client( [ 'timeout' => 5 ] );
		try {
			$response = $guzzleClient->send(
				$request->withHeader( 'User-Agent', 'Shellbox test client' )
			);
		} catch ( RequestException $e ) {
			if ( $e->getResponse() ) {
				$response = $e->getResponse();
			} else {
				throw $e;
			}
		}
		$header = $response->getHeader( 'X-Shellbox-Cover' );
		if ( $this->coverCallback && isset( $header[0] )
			&& preg_match( '/^[0-9a-z]+$/', $header[0] )
		) {
			( $this->coverCallback )( $header[0] );
		}
		return $response;
	}
}

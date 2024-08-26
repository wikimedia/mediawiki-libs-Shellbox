<?php
namespace Shellbox\Tests;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * File server to test the URL file upload/download feature (allowUrlFiles).
 *
 * In the integrated test (RemoteBoxedExecutorTest), this class is used as an
 * actual server, invoked with main().
 *
 * In the local test (LocalBoxedExecutorTest), this class is used as a helper
 * for a mock HTTP client.
 */
class FileServer {
	public static function main() {
		$request = ServerRequest::fromGlobals();
		$response = ( new self )->respond( $request );
		foreach ( $response->getHeaders() as $name => $values ) {
			foreach ( $values as $value ) {
				header( "$name: $value" );
			}
		}
		http_response_code( $response->getStatusCode() );
		echo $response->getBody()->getContents();
	}

	/**
	 * @param RequestInterface $request
	 * @return ResponseInterface
	 */
	public function respond( RequestInterface $request ) {
		if ( !preg_match( '#/!/([^/]+)(?:/(.*))?$#', $request->getUri()->getPath(), $m ) ) {
			return $this->error( 404, $request->getUri()->getPath() );
		}

		$action = $m[1];
		$path = $m[2];
		if ( $action === 'download' ) {
			if ( $request->getMethod() !== 'GET' ) {
				return $this->error( 405 );
			}
			return $this->success( $path );
		} elseif ( $action === 'upload' ) {
			if ( $request->getMethod() !== 'PUT' ) {
				return $this->error( 405 );
			}
			$contents = $request->getBody()->getContents();
			if ( $contents === $path ) {
				return new Response( 204, [], '' );
			} else {
				return $this->error( 409 );
			}
		} elseif ( $action === 'validate-headers' ) {
			if ( $path === 'output1' ) {
				if ( $request->getHeader( 'Content-Length' )[0] == 5
					&& $request->getHeader( 'ETag' )[0] === '5d41402abc4b2a76b9719d911017c592'
					&& $request->getHeader( 'X-Object-Meta-Sha1Base36' )[0]
						=== 'jywkymwk5kel4plcu17bdqhwzuuz3nx'
					&& $request->getHeader( 'Foo' )[0] === 'bar'
				) {
					return new Response( 202, [], '' );
				} else {
					return $this->error( 406 );
				}
			} elseif ( $path === 'input1' ) {
				if ( $request->getHeader( 'Foo' )[0] === 'bar' ) {
					return new Response( 200, [], '' );
				} else {
					return $this->error( 406 );
				}
			} else {
				return $this->error( 404 );
			}
		} elseif ( $action === 'healthz' ) {
			return $this->success( getmypid() . "\n" );
		} else {
			return $this->error( 400 );
		}
	}

	/**
	 * @param int $code
	 * @param string $message
	 * @return Response
	 */
	private function error( int $code, string $message = '' ): Response {
		return new Response( $code, [], "Error: $code\n" . $message );
	}

	/**
	 * @param string $body
	 * @return Response
	 */
	private function success( string $body ): Response {
		return new Response( 200, [], $body );
	}
}

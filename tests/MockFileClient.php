<?php

namespace Shellbox\Tests;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MockFileClient implements ClientInterface {
	public function sendRequest( RequestInterface $request ): ResponseInterface {
		return ( new FileServer )->respond( $request );
	}
}

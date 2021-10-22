<?php

namespace Shellbox\Tests\RPC;

use Shellbox\RPC\LocalRpcClient;
use Shellbox\Tests\ShellboxTestCase;

/**
 * @covers \Shellbox\RPC\LocalRpcClient
 */
class LocalRpcClientTest extends ShellboxTestCase {
	use RpcClientTests;

	protected function createClient( $key = null ) {
		return new LocalRpcClient();
	}
}

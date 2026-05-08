<?php
declare( strict_types = 1 );

namespace Shellbox\Tests\RPC;

use Shellbox\RPC\LocalRpcClient;
use Shellbox\RPC\RpcClient;
use Shellbox\Tests\ShellboxTestCase;

/**
 * @covers \Shellbox\RPC\LocalRpcClient
 */
class LocalRpcClientTest extends ShellboxTestCase {
	use RpcClientTests;

	protected function createClient( ?string $key = null ): RpcClient {
		return new LocalRpcClient();
	}
}

<?php

namespace Shellbox\Tests\Command;

use Psr\Log\LoggerInterface;
use Shellbox\Command\BoxedExecutor;
use Shellbox\Command\RemoteBoxedExecutor;
use Shellbox\Tests\ClientServerTestCase;

class RemoteBoxedExecutorTest extends ClientServerTestCase {
	use BoxedExecutorTestTrait;

	protected function createExecutor( ?LoggerInterface $logger = null ): BoxedExecutor {
		$executor = new RemoteBoxedExecutor( $this->createClient() );
		if ( $logger ) {
			$executor->setLogger( $logger );
		}
		return $executor;
	}
}

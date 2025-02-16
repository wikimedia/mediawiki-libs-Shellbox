<?php

namespace Shellbox\Tests\Command;

use Psr\Log\LoggerInterface;
use Shellbox\Command\BoxedExecutor;
use Shellbox\Shellbox;
use Shellbox\Tests\MockFileClient;
use Shellbox\Tests\ShellboxTestCase;

/**
 * @coversNothing
 */
class LocalBoxedExecutorTest extends ShellboxTestCase {
	use BoxedExecutorTestTrait;

	protected function createExecutor( ?LoggerInterface $logger = null ): BoxedExecutor {
		return Shellbox::createBoxedExecutor( $this->getAllConfig(), $logger, new MockFileClient );
	}

	protected function getFileServerUrl( $path ): string {
		return "http://mock/!/$path";
	}
}

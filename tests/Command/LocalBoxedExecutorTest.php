<?php

namespace Shellbox\Tests\Command;

use Psr\Log\LoggerInterface;
use Shellbox\Command\BoxedExecutor;
use Shellbox\Shellbox;
use Shellbox\Tests\ShellboxTestCase;

class LocalBoxedExecutorTest extends ShellboxTestCase {
	use BoxedExecutorTestTrait;

	protected function createExecutor( LoggerInterface $logger = null ): BoxedExecutor {
		return Shellbox::createBoxedExecutor( $this->getAllConfig(), $logger );
	}
}

<?php

namespace Shellbox\Command;

/**
 * Base class for things that execute BoxedCommands
 */
abstract class BoxedExecutor {
	/**
	 * Execute a boxed command.
	 *
	 * @param BoxedCommand $command
	 * @return BoxedResult
	 */
	abstract public function execute( BoxedCommand $command );

	/**
	 * Create an empty command linked to this executor
	 *
	 * @return BoxedCommand
	 */
	public function createCommand() {
		return new BoxedCommand( $this );
	}
}

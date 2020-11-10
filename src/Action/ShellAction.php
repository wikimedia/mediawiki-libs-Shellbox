<?php

namespace Shellbox\Action;

use Shellbox\Command\BoxedCommand;
use Shellbox\Command\ServerBoxedExecutor;
use Shellbox\Command\ServerUnboxedExecutor;

/**
 * Shell command handler
 */
class ShellAction extends MultipartAction {
	/**
	 * @param string[] $pathParts @phan-unused-param
	 */
	protected function execute( $pathParts ) {
		$command = $this->createCommand();
		$result = $command->execute();
		$binaryData = [];
		if ( $result->getStdout() !== null ) {
			$binaryData['stdout'] = $result->getStdout();
		}
		if ( $result->getStderr() !== null ) {
			$binaryData['stderr'] = $result->getStderr();
		}
		$this->writeResult(
			[ 'exitCode' => $result->getExitCode() ],
			$binaryData,
			$result->getFileNames()
		);
	}

	protected function getActionName() {
		return 'shell';
	}

	/**
	 * @return ServerBoxedExecutor
	 */
	private function createExecutor() {
		$unboxedExecutor = new ServerUnboxedExecutor( $this->tempDirManager );
		$unboxedExecutor->setLogger( $this->logger );
		$unboxedExecutor->addWrappersFromConfiguration( [
			'useSystemd' => $this->getConfig( 'useSystemd' ),
			'useBashWrapper' => $this->getConfig( 'useBashWrapper' ),
			'useFirejail' => $this->getConfig( 'useFirejail' ),
			'firejailPath' => $this->getConfig( 'firejailPath' )
		] );

		$executor = new ServerBoxedExecutor( $unboxedExecutor, $this->tempDirManager );
		$executor->setLogger( $this->logger );
		return $executor;
	}

	/**
	 * @return BoxedCommand
	 */
	private function createCommand() {
		$executor = $this->createExecutor();
		$command = $executor->createCommand();
		$command->setClientData( $this->getRequiredParam( 'command' ) );
		$stdin = $this->getParam( 'stdin' );
		if ( $stdin !== null ) {
			$command->stdin( $stdin );
		}
		return $command;
	}

}

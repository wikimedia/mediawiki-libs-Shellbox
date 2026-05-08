<?php
declare( strict_types = 1 );

namespace Shellbox\Tests;

use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;
use PHPUnit\Runner\CodeCoverage;

readonly class ServerCoverageSubscriber implements FinishedSubscriber {

	public function __construct( private CodeCoverage $codeCoverage ) {
	}

	public function notify( Finished $event ): void {
		if ( !$this->codeCoverage->isActive() ) {
			return;
		}
		ClientServerTestCase::collectAndMergeServerCoverage( $this->codeCoverage->codeCoverage() );
	}
}

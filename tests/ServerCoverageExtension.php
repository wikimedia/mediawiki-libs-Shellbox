<?php
declare( strict_types = 1 );

namespace Shellbox\Tests;

use PHPUnit\Runner\CodeCoverage;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

readonly class ServerCoverageExtension implements Extension {
	public function bootstrap(
		Configuration $configuration,
		Facade $facade,
		ParameterCollection $parameters
	): void {
		$facade->registerSubscriber(
			new ServerCoverageSubscriber( CodeCoverage::instance() )
		);
	}
}

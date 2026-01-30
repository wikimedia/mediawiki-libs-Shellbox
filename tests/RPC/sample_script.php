<?php

function wfTestFunction( string $parameter ) {
	return 'test_' . $parameter;
}

function wfTestFunctionUsingClass( string $parameter ) {
	$client = new \Shellbox\RPC\LocalRpcClient();
	return 'using_class_' . $client->call(
		'test_route',
		'wfTestFunction',
		[ $parameter ],
		[
			'sources' => [ __FILE__ ]
		]
	);
}

function wfTestFunctionUsingNonAutoloadedClass( string $parameter ) {
	return $parameter . '_' . NotAutoloadedClass::test();
}

/** @return never */
function wfError() {
	throw new InvalidArgumentException( 'test error' );
}

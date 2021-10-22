<?php

/**
 * Helper for testing 'classes' feature in RpcClient.
 *
 * This is incorrectly namespaced on purpose to check that class sources
 * are passed to remote shellbox and not autoloaded.
 */
class NotAutoloadedClass {
	public static function test() {
		return 'BOO!';
	}
}

<?php

/*
 * This entry point is enabled when config/config.json exists.
 *
 * Note: You don't need to use this script. You can make your own entry
 * point script containing these two lines of code, and customise them as
 * necessary.
 */
require __DIR__ . '/../vendor/autoload.php';
\Shellbox\Server::main( dirname( __DIR__ ) . '/config/config.json' );

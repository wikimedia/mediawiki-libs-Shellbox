<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = [
	'src',
	'vendor',
	'tests'
];
$cfg['exclude_analysis_directory_list'][] = 'vendor';
$cfg['exclude_file_regex'] = '@/vendor/(phan|mediawiki|php-parallel-lint)/@';
$cfg['suppress_issue_types'] = [
	// It's a library, methods don't have to be called
	'PhanUnreferencedPublicMethod',
	// It means internal to Shellbox
	'PhanAccessMethodInternal',
];
return $cfg;

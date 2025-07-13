<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config-library.php';

$cfg['directory_list'][] = 'tests';

$cfg['exception_classes_with_optional_throws_phpdoc'] = [
	...$cfg['exception_classes_with_optional_throws_phpdoc'],
	\Shellbox\ShellboxError::class,
	\Shellbox\Multipart\MultipartError::class,
];

// Remove the exclusion of phpunit/php-code-coverage, which is needed here
$cfg['exclude_file_regex'] = preg_replace(
	'@\|(?:phpunit/php-code-coverage)@',
	'',
	$cfg['exclude_file_regex']
);

// Exclude peg-generated output
$cfg['exclude_file_list'][] = "src/ShellParser/PEGParser.php";

$cfg['suppress_issue_types'] = array_merge( $cfg['suppress_issue_types'], [
	// It's a library, methods don't have to be called
	'PhanUnreferencedPublicMethod',
	// It means internal to Shellbox
	'PhanAccessMethodInternal',
] );

return $cfg;

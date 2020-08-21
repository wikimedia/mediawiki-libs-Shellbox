<?php

namespace Shellbox\Command;

use Psr\Http\Message\StreamInterface;

/**
 * The base class for input files
 * @internal
 */
abstract class InputFile {
	/**
	 * Copy the contents of the input file to a fully-qualified path
	 *
	 * @param string $destPath
	 */
	abstract public function copyTo( $destPath );

	/**
	 * Get the contents of the file as either a PSR-7 StreamInterface or a
	 * string.
	 *
	 * @return StreamInterface|string
	 */
	abstract public function getStreamOrString();
}

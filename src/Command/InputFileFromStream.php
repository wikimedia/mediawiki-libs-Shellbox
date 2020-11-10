<?php

namespace Shellbox\Command;

use Psr\Http\Message\StreamInterface;
use Shellbox\FileUtils;
use function GuzzleHttp\Psr7\copy_to_stream;

class InputFileFromStream extends InputFile {
	/** @var StreamInterface */
	private $stream;

	public function __construct( StreamInterface $stream ) {
		$this->stream = $stream;
	}

	public function copyTo( $destPath ) {
		copy_to_stream( $this->stream, FileUtils::openOutputFileStream( $destPath ) );
	}

	public function getStreamOrString() {
		return $this->stream;
	}
}

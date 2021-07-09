<?php

namespace OCA\Libresign\Handler;

use OCP\Files\File;

interface SignEngineInterface {
	/**
	 * Sign a file
	 *
	 * @param File $inputFile
	 * @param File $certificate
	 * @param string $password
	 * @return string string of signed file
	 */
	public function sign(
		File $inputFile,
		File $certificate,
		string $password
	): string;
}

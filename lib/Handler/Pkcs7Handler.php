<?php

namespace OCA\Libresign\Handler;

use OCP\Files\File;

/**
 * @codeCoverageIgnore
 */
class Pkcs7Handler {
	public function sign(
		File $fileToSign,
		File $certificate,
		string $passphrase
	): File {
		$newName = $fileToSign->getName() . '.p7s';
		$p7sFile = $fileToSign
			->getParent()
			->newFile($newName);
		openssl_pkcs12_read($certificate->getContent(), $certificateData, $passphrase);
		$tempntam = tempnam('/temp', 'pkey');
		file_put_contents($tempntam, $certificateData['pkey']);
		openssl_pkcs7_sign(
			$fileToSign->getInternalPath(),
			$p7sFile->getInternalPath(),
			'file:/' . $tempntam,
			$passphrase,
			[]
		);
		return $p7sFile;
	}
}

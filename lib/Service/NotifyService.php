<?php

namespace OCA\Libresign\Service;

use OCA\Libresign\Db\FileUserMapper;
use OCA\Libresign\Helper\ValidateHelper;
use OCP\IUserSession;

class NotifyService {
	/** @var ValidateHelper */
	private $validateHelper;
	/** @var IUserSession */
	private $userSession;
	/** @var MailService */
	private $mailService;
	/** @var FileUserMapper */
	private $fileUserMapper;

	public function __construct(
		ValidateHelper $validateHelper,
		IUserSession $userSession,
		MailService $mailService,
		FileUserMapper $fileUserMapper
	) {
		$this->validateHelper = $validateHelper;
		$this->userSession = $userSession;
		$this->mailService = $mailService;
		$this->fileUserMapper = $fileUserMapper;
	}

	public function signers(int $nodeId, array $signers) {
		$this->validateHelper->canRequestSign($this->userSession->getUser());
		$this->validateHelper->validateLibreSignNodeId($nodeId);
		$this->validateHelper->iRequestedSignThisFile($this->userSession->getUser(), $nodeId);
		foreach ($signers as $signer) {
			$this->validateHelper->haveValidMail($signer);
			$this->validateHelper->signerWasAssociated($signer);
			$this->validateHelper->notSigned($signer);
		}
		foreach ($signers as $signer) {
			$fileUser = $this->fileUserMapper->getByFileIdAndEmail($nodeId, $signer['email']);
			$this->mailService->notifyUnsignedUser($fileUser);
		}
	}
}

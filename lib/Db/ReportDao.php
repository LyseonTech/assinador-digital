<?php

namespace OCA\Libresign\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IURLGenerator;

class ReportDao {

	/** @var IDBConnection */
	private $db;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var FileUserMapper */
	private $fileUserMapper;

	public function __construct(
		IDBConnection $db,
		IURLGenerator $urlGenerator,
		FileUserMapper $fileUserMapper
	) {
		$this->db = $db;
		$this->urlGenerator = $urlGenerator;
		$this->fileUserMapper = $fileUserMapper;
	}

	public function getTotalFilesAssociatedFilesWithMe($userId) {
		$stmt = $this->getFilesAssociatedFilesWithMeStmt($userId, true);
		$row = $stmt->fetch();
		return (int) $row['total'];
	}

	public function getFilesAssociatedFilesWithMeFormatted($userId, $page = null, $limit = 15) {
		$stmt = $this->getFilesAssociatedFilesWithMeStmt($userId);

		$url = $this->urlGenerator->linkToRoute('libresign.page.getPdfUser', ['uuid' => '_replace_']);
		$url = str_replace('_replace_', '', $url);

		$return = [];
		$fileIds = [];
		while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
			$fileIds[] = $row['id'];
			$return[] = $this->formatListRow($row, $url);
		}
		$signers = $this->fileUserMapper->getByMultipleFileId($fileIds);
		$return = $this->assocFileToFileUserAndFormat($userId, $return, $signers);
		return $return;
	}

	private function getFilesAssociatedFilesWithMeStmt($userId, $count = false) {
		$qb = $this->db->getQueryBuilder();

		if ($count) {
			$qb->selectAlias($qb->func()->count(), 'total')
				->where(
					$qb->expr()->orX(
						$qb->expr()->eq('f.user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR))
					)
				);
		} else {
			$qb->select(
					'f.id',
					'f.uuid',
					'f.name',
					'f.callback',
					'f.node_id'
				)
				->selectAlias('u.uid_lower', 'requested_by_uid')
				->selectAlias('u.displayname', 'requested_by_dislpayname')
				->selectAlias('f.created_at', 'request_date')
				->selectAlias($qb->func()->max('fu.signed'), 'status_date')
				->leftJoin('f', 'users', 'u', 'f.user_id = u.uid')
				->where(
					$qb->expr()->orX(
						$qb->expr()->eq('f.user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)),
						$qb->expr()->eq('fu.user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR))
					)
				)
				->groupBy(
					'f.id',
					'f.uuid',
					'f.name',
					'f.callback',
					'f.node_id',
					'f.created_at',
					'u.uid_lower',
					'u.displayname'
				);
		}
		$qb->from('libresign_file', 'f')
			->leftJoin('f', 'libresign_file_user', 'fu', 'fu.file_id = f.id');

		return $qb->execute();
	}

	private function assocFileToFileUserAndFormat($userId, $files, $signers) {
		foreach ($files as $key => $file) {
			$totalSigned = 0;
			foreach ($signers as $signerKey => $signer) {
				if ($signer->getFileId() === $file['id']) {
					$data = [
						'email' => $signer->getEmail(),
						'description' => $signer->getDescription(),
						'displayName' => $signer->getDisplayName(),
						'request_sign_date' => (new \DateTime())
							->setTimestamp($signer->getCreatedAt())
							->format('Y-m-d H:i:s'),
						'sign_date' => null,
						'uid' => $signer->getUserId(),
						'me' => $userId === $signer->getUserId()
					];
					if ($signer->getSigned()) {
						$data['sign_date'] = (new \DateTime())
							->setTimestamp($signer->getSigned())
							->format('Y-m-d H:i:s');
						$totalSigned++;
					}
					$files[$key]['signers'][] = $data;
					unset($signers[$signerKey]);
				}
			}
			if (empty($files[$key]['signers'])) {
				$files[$key]['signers'] = [];
				$files[$key]['status'] = 'no signers';
			} elseif ($totalSigned === count($files[$key]['signers'])) {
				$files[$key]['status'] = 'signed';
			} else {
				$files[$key]['status'] = 'pending';
			}
			unset($files[$key]['id']);
		}
		return $files;
	}

	private function formatListRow(array $row, string $url) {
		$row['id'] = (int) $row['id'];
		$row['requested_by'] = [
			'uid' => $row['requested_by_uid'],
			'displayName' => $row['requested_by_dislpayname']
		];
		$row['request_date'] = (new \DateTime())
			->setTimestamp($row['request_date'])
			->format('Y-m-d H:i:s');
		if (!empty($row['status_date'])) {
			$row['status_date'] = (new \DateTime())
				->setTimestamp($row['status_date'])
				->format('Y-m-d H:i:s');
		}
		$row['file'] = [
			'type' => 'pdf',
			'url' => $url . $row['uuid'],
			'nodeId' => (int) $row['node_id']
		];
		unset(
			$row['node_id'],
			$row['requested_by_uid'],
			$row['requested_by_dislpayname']
		);
		return $row;
	}
}

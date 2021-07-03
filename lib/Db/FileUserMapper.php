<?php

namespace OCA\Libresign\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Class FileUserMapper
 *
 * @package OCA\Libresign\DB
 *
 * @codeCoverageIgnore
 * @method FileUser insert(FileUser $entity)
 * @method FileUser update(FileUser $entity)
 * @method FileUser insertOrUpdate(FileUser $entity)
 * @method FileUser delete(FileUser $entity)
 */
class FileUserMapper extends QBMapper {

	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'libresign_file_user');
	}

	/**
	 * Returns all users who have not signed
	 *
	 * @return Entity[] all fetched entities
	 */
	public function findUnsigned() {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->isNull('signed')
			);

		return $this->findEntities($qb);
	}

	/**
	 * Get file user by UUID
	 *
	 * @param string $uuid
	 * @return FileUser
	 */
	public function getByUuid(string $uuid): FileUser {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('uuid', $qb->createNamedParameter($uuid, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntity($qb);
	}

	public function getByEmailAndFileId(string $email, int $fileId) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('email', $qb->createNamedParameter($email, IQueryBuilder::PARAM_STR))
			)
			->andWhere(
				$qb->expr()->eq('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT))
			);

		return $this->findEntity($qb);
	}

	/**
	 * Get all signers by fileId
	 *
	 * @param string $fileId
	 * @return FileUser[]
	 */
	public function getByFileId(int $fileId) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT))
			);

		return $this->findEntities($qb);
	}

	/**
	 * Get all signers by multiple fileId
	 *
	 * @param array $fileId
	 * @return FileUser[]
	 */
	public function getByMultipleFileId(array $fileId) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->in('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT_ARRAY))
			);

		return $this->findEntities($qb);
	}

	/**
	 * Get all signers by fileId
	 *
	 * @param string $nodeId
	 * @return FileUser[]
	 */
	public function getByNodeId(int $nodeId) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('fu.*')
			->from($this->getTableName(), 'fu')
			->join('fu', 'libresign_file', 'f', 'fu.file_id = f.id')
			->where(
				$qb->expr()->eq('f.node_id', $qb->createNamedParameter($nodeId, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntities($qb);
	}

	/**
	 * Get all signers by File Uuid
	 *
	 * @param string $nodeId
	 * @return FileUser[]
	 */
	public function getByFileUuid(string $uuid) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('fu.*')
			->from($this->getTableName(), 'fu')
			->join('fu', 'libresign_file', 'f', 'fu.file_id = f.id')
			->where(
				$qb->expr()->eq('f.uuid', $qb->createNamedParameter($uuid, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntities($qb);
	}

	public function getByUuidAndUserId(string $uuid, string $userId) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('uuid', $qb->createNamedParameter($uuid, IQueryBuilder::PARAM_STR))
			)
			->andWhere(
				$qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntity($qb);
	}

	public function getByFileIdAndUserId(string $file_id, string $userId) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('fu.*')
			->from($this->getTableName(), 'fu')
			->join('fu', 'libresign_file', 'f', 'fu.file_id = f.id')
			->where(
				$qb->expr()->eq('f.node_id', $qb->createNamedParameter($file_id, IQueryBuilder::PARAM_STR))
			)
			->andWhere(
				$qb->expr()->eq('fu.user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntity($qb);
	}

	public function getByFileIdAndEmail(string $file_id, string $email) {
		$qb = $this->db->getQueryBuilder();

		$qb->select('fu.*')
			->from($this->getTableName(), 'fu')
			->join('fu', 'libresign_file', 'f', 'fu.file_id = f.id')
			->where(
				$qb->expr()->eq('f.node_id', $qb->createNamedParameter($file_id, IQueryBuilder::PARAM_STR))
			)
			->andWhere(
				$qb->expr()->eq('fu.email', $qb->createNamedParameter($email, IQueryBuilder::PARAM_STR))
			);

		return $this->findEntity($qb);
	}
}

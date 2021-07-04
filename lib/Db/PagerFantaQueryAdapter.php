<?php

namespace OCA\Libresign\Db;

use Doctrine\DBAL\Query\QueryBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Exception\InvalidArgumentException;

/**
 * Adapter which calculates pagination from a Doctrine DBAL QueryBuilder.
 */
class PagerFantaQueryAdapter implements AdapterInterface {
	private IQueryBuilder $queryBuilder;

	/**
	 * @var callable
	 * @phpstan-var callable(QueryBuilder): void
	 */
	private $countQueryBuilderModifier;

	/**
	 * @phpstan-param callable(QueryBuilder): void $countQueryBuilderModifier
	 *
	 * @throws InvalidArgumentException if a non-SELECT query is given
	 */
	public function __construct(IQueryBuilder $queryBuilder, callable $countQueryBuilderModifier) {
		$queryBuilder->getType();
		if (QueryBuilder::SELECT !== $queryBuilder->getType()) {
			throw new InvalidArgumentException('Only SELECT queries can be paginated.');
		}

		$this->queryBuilder = clone $queryBuilder;
		$this->countQueryBuilderModifier = $countQueryBuilderModifier;
	}

	public function getNbResults(): int {
		$qb = $this->prepareCountQueryBuilder();
		$result = $qb->execute()->fetch();
		$values = array_values($result);
		return (int) $values[0];
	}

	public function getSlice(int $offset, int $length): iterable {
		$qb = clone $this->queryBuilder;

		return $qb->setMaxResults($length)
			->setFirstResult($offset)
			->execute()
			->fetchAll();
	}

	private function prepareCountQueryBuilder(): IQueryBuilder {
		$qb = clone $this->queryBuilder;
		$callable = $this->countQueryBuilderModifier;

		$callable($qb);

		return $qb;
	}
}

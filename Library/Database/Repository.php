<?php
namespace Database;

use InvalidArgumentException;
use Database\Entity\Entity;
use RuntimeException;
use Exception;
use PDO;

class Repository {

	protected const FETCH_MODE = [
		'fetch',
		'fetchAll'
	];
	protected const LIMIT = 50;

	public function __construct(
		protected PDO $pdo,
		protected string $table_name,
		protected string $entity_name
	) {
		$this->table_name = strtolower($this->table_name);
	}

	public function getTablename(): string {
		return $this->table_name;
	}

	/**
	 * @param string $query
	 * @param string $fetch_mode
	 * @param array $prepared_data
	 * @return bool|array
	 */
	public function query(string $query, string $fetch_mode, array $prepared_data = []): bool|array {
		$stmt = $this->pdo->prepare($query);

		if (!$stmt) {
			throw new RuntimeException('Query failed to run');
		}

		$stmt->execute($prepared_data);

		if ($fetch_mode !== '' && !in_array($fetch_mode, self::FETCH_MODE)) {
			throw new InvalidArgumentException(sprintf("'%s' is not a valid fetch", $fetch_mode));
		}

		if ($fetch_mode === '') {
			$return = true;
		}
		else {
			$return = $stmt->$fetch_mode();
		}

		if ($fetch_mode === 'fetch' && $return === false) {
			// If no row was found, return empty array instead of false
			$return = [];
		}

		return $return;
	}

	/**
	 * @param int $id
	 * @return Entity
	 * @throws Exception|RuntimeException|InvalidArgumentException
	 */
	public function find(int $id): Entity {
		$object = $this->query(sprintf('SELECT *, (SELECT COUNT(*) FROM %1$s WHERE id = ?) AS total_rows FROM %s WHERE id = ?', $this->table_name), 'fetch', [
			$id,
			$id
		]);

		$entity = new $this->entity_name();
		if (!empty($object)) {
			/** @var Entity $entity */
			$entity->populate($object);
		}

		return $entity;
	}

	public function options(array $options): array {
		$options = array_merge([
			'limit' => self::LIMIT,
			'offset' => null,
			'order_by' => null,
			'exclude_deleted' => true,
		], $options);

		$wheres = [];

		if ($options['exclude_deleted']) {
			$wheres['AND'][] = 'deleted_at IS NULL';
		}

		$order_by = '';
		if ($options['order_by'] !== null) {
			$order_by = sprintf('ORDER BY %s', $options['order_by']);
		}

		$limit = '';
		if (is_int($options['limit']) && $options['limit'] > 0) {
			$limit = sprintf(' LIMIT %d', $options['limit']);

			if (is_int($options['offset'])) {
				$limit = sprintf(
					' LIMIT %d OFFSET %d',
					$options['limit'],
					$options['offset']
				);
			}
		}

		return [$wheres, $limit, $order_by];
	}

	/**
	 * Converts a where-array to a where-string for use in a query
	 *
	 * @param $wheres
	 * @return string
	 */
	public function wheres($wheres): string {
		$wheresSQL = '';

		foreach ($wheres as $eq => $statements) {
			foreach ($statements as $where) {
				if ($wheresSQL !== '') {
					$wheresSQL .= sprintf(' %s ', $eq);
				}

				$wheresSQL .= ' ' . $where;
			}
		}

		return sprintf(
			'%s%s',
			(($wheresSQL !== '') ? ' WHERE ' : ''),
			$wheresSQL
		);
	}

	/**
	 * @return Entity[]
	 * @throws Exception|RuntimeException|InvalidArgumentException
	 */
	public function findAll(array $options = []): array {
		[$wheres, $limit, $order_by] = $this->options($options);
		$wheresSQL = $this->wheres($wheres);

		$sql = sprintf(
			'SELECT *, (SELECT COUNT(*) FROM %1$s%2$s) AS total_rows FROM %s%s%s%s',
			$this->table_name,
			$wheresSQL,
			$limit,
			$order_by
		);

		$objects = $this->query($sql, 'fetchAll');

		$entities = [];
		foreach ($objects as $object) {
			if (!empty($object)) {
				$entity = new $this->entity_name();

				/** @var Entity $entity */
				$entity->populate($object);
				$entities[] = $entity;
			}
		}

		return $entities;
	}

	/**
	 * @param string $row
	 * @param mixed $value
	 * @param array $options
	 * @return Entity[]
	 * @throws Exception
	 */
	public function findBy(string $row, mixed $value, array $options =  []): array {
		[$wheres, $limit, $order_by] = $this->options($options);

		$objects = $this->query(sprintf(
			'SELECT *, (SELECT COUNT(*) FROM %1$s WHERE %2$s = "%3$s") AS total_rows FROM %s WHERE %s = "%s"%s%s',
			$this->table_name,
			$row,
			$value,
			$order_by,
			$limit,
		), 'fetchAll');

		$entities = [];
		foreach ($objects as $object) {
			if (!empty($object)) {
				$entity = new $this->entity_name();

				/** @var Entity $entity */
				$entity->populate($object);
				$entities[] = $entity;
			}
		}

		return $entities;
	}

	/**
	 * Same as findBy(), but searches for string matching & can execute multiple rows
	 *
	 * @return Entity[]
	 * @throws Exception
	 */
	public function search(array|string $rows, mixed $value, array $options = []): array {
		[$wheres, $limit, $order_by] = $this->options($options);

		if (is_string($rows)) {
			$wheres['AND'][] = sprintf('%s LIKE \'%%%s%%\'', $rows, $value);
		}

		if (is_array($rows)) {
			$is_first = true;
			foreach ($rows as $row) {
				$eq = 'OR';
				if ($options['exclude_deleted'] && $is_first) {
					$eq = 'AND';
				}

				$wheres[$eq][] = sprintf('%s LIKE \'%%%s%%\'', $row, $value);
				$is_first = false;
			}
		}

		$wheresSQL = $this->wheres($wheres);

		$sql = sprintf(
			'SELECT *, (SELECT COUNT(*) FROM %1$s%2$s) AS total_rows FROM %s%s%s%s',
			$this->table_name,
			$wheresSQL,
			$order_by,
			$limit
		);

		$objects = $this->query($sql, 'fetchAll');

		$entities = [];
		foreach ($objects as $object) {
			if (!empty($object)) {
				$entity = new $this->entity_name();

				/** @var Entity $entity */
				$entity->populate($object);
				$entities[] = $entity;
			}
		}

		return $entities;
	}

	public function count(array $tables): array {
		$sql = 'SELECT';
		foreach ($tables as $table) {
			$sql .= sprintf(' (SELECT COUNT(*) FROM `%s`) AS %1$s_count,', strtolower($table));
		}
		$sql = rtrim($sql, ',');

		return $this->query($sql, 'fetchAll')[0];
	}

	/**
	 * @param Entity $entity
	 * @throws Exception
	 */
	public function flush(Entity $entity): void {
		if (method_exists($entity, 'preSave')) {
			$entity->preSave();
		}

		$rows = $entity->rowMapping();
		$values = $entity->getValues();

		if ($entity::has('id') && $entity->getId() !== null) {
			$this->update($rows, $values, $entity->getId());
			return;
		}

		$this->insert($rows, $values);

		if (method_exists($entity, 'postSave')) {
			$entity->postSave();
		}
	}

	/**
	 * @param array $rows
	 * @param array $values
	 * @return void
	 * @throws Exception
	 */
	public function insert(array $rows, array $values): void {
		$sql = sprintf(
			'INSERT INTO %s (%s) VALUES (%s)',
			$this->table_name,
			implode(',', $rows),
			rtrim(str_repeat('?,', count($rows)), ',')
		);

		$this->query($sql, '', $values);
	}

	/**
	 * @param array $entity_rows
	 * @param array $values
	 * @param int $id
	 * @return void
	 * @throws Exception
	 */
	public function update(array $entity_rows, array $values, int $id): void {
		$rows = [];
		foreach ($entity_rows as $entity_row) {
			$rows[] = sprintf('%s=?', $entity_row);
		}

		$sql = sprintf(
			'UPDATE %s SET %s WHERE id = %d',
			$this->table_name,
			implode(',', $rows),
			$id
		);

		$this->query($sql, '', $values);
	}

}
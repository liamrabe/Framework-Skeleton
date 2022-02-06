<?php
namespace Database;

use Database\Repository;
use RuntimeException;
use JsonException;
use PDOException;
use PDO;

class Factory {

	protected const PDO_OPTIONS = [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	];

	/**
	 * @return array
	 * @throws JsonException
	 */
	protected static function getConfig(): array {
		$config = file_get_contents(sprintf('%s/Config/Database.json', BASE_DIR));
		return json_decode($config, true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return PDO
	 * @throws JsonException
	 * @throws PDOException
	 */
	public static function createInstance(): PDO {
		$config = self::getConfig();

		return new PDO(
			sprintf(
				'mysql:host=%s;port=%s;dbname=%s',
				$config['hostname'],
				$config['port'],
				$config['database']
			),
			$config['username'], $config['password'],
			self::PDO_OPTIONS
		);
	}

	/**
	 * @param string $table_name
	 * @param string $entity_name
	 * @return Repository
	 * @throws JsonException
	 */
	public static function getRepository(string $table_name, string $entity_name): Repository {
		return new Repository(self::createInstance(), $table_name, $entity_name);
	}

}
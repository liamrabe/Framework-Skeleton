<?php
namespace Database\Entity;

use DateTime;
use Exception;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

abstract class Entity implements JsonSerializable {

	protected int $total_rows;

	/**
	 * @param array $ignore
	 * @return array
	 */
	public function jsonSerialize(array $ignore = []): array {
		$return = [];
		foreach (self::getProps() as $prop) {
			if (
				!isset($this->{$prop->getName()}) ||
				in_array($prop->getName(), $ignore, true)
			) {
				continue;
			}

			$return[$prop->getName()] = $this->toNonObject($prop);
		}

		return $return;
	}

	public function getTotalRows(): int {
		return $this->total_rows ?? 0;
	}

	/**
	 * @return array
	 */
	public function rowMapping(): array {
		$rows = [];

		foreach (self::getProps() as $prop) {
			if (
				$prop->getName() === 'id' ||
				!isset($this->{$prop->getName()})
			) {
				continue;
			}

			$name = $prop->getName();
			$rows[$name] = $name;
		}

		return $rows;
	}

	/**
	 * @param string $type
	 * @param mixed $value
	 * @return mixed
	 * @throws Exception
	 */
	protected function typeHandling(string $type, mixed $value): mixed {
		return match($type) {
			'bool' => (bool) $value,
			'int' => (int) $value,
			'double' => (double) $value,
			'string' => (string) $value,
			'array' => (array) $value,
			'object' => (object) $value,
			'DateTime' => (($value === null) ? null : new DateTime($value)),
			default => $value,
		};
	}

	/**
	 * @param ReflectionProperty $property
	 * @return mixed
	 */
	protected function toNonObject(ReflectionProperty $property): mixed {
		$value = $this->{$property->getName()};
		if ($value instanceof DateTime) {
			$value = $value->format('Y-m-d H:i:s');
		}

		return $value;
	}

	/**
	 * @param array $rows
	 * @throws Exception
	 */
	public function populate(array $rows): void {
		foreach (self::getProps() as $prop) {
			$this->{$prop->getName()} = $this->typeHandling(
				$prop->getType(),
				$rows[$prop->getName()]
			);
		}
	}

	/**
	 * @return array
	 */
	public function getValues(): array {
		$return = [];

		foreach (self::getProps() as $prop) {
			if (
				$prop->getName() === 'id' ||
				!isset($this->{$prop->getName()})
			) {
				continue;
			}

			$return [] = $this->toNonObject($prop);
		}

		return $return;
	}

	public function wasLoaded(): bool {
		return self::has('id') && (isset($this->id) && !empty($this->id));
	}

	/* Static methods */

	/**
	 * @return ReflectionClass
	 */
	public static function getReflection(): ReflectionClass {
		return new ReflectionClass(new static());
	}

	/**
	 * @return ReflectionProperty[]
	 */
	public static function getProps(): array {
		return self::getReflection()->getProperties();
	}

	/**
	 * Check if table has specific row
	 *
	 * @param string $row
	 * @return bool
	 */
	public static function has(string $row): bool {
		$props = self::getProps();

		foreach ($props as $prop) {
			if ($prop->getName() === $row) {
				return true;
			}
		}

		return false;
	}

}
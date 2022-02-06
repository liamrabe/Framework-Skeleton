<?php
namespace Database;

use Database\Entity\Entity;

class Pagination {

	/**
	 * @param int $total_rows
	 * @param int $items_per_page
	 * @return int
	 */
	public static function getTotalPages(int $total_rows, int $items_per_page): int {
		if ($total_rows === 0) {
			return 0;
		}
		return floor((($total_rows - 1) / $items_per_page) + 1);
	}

	/**
	 * @param int $page_number
	 * @param int $items_per_page
	 * @return int
	 */
	public static function getPageOffset(int $page_number, int $items_per_page): int {
		return ($items_per_page * ($page_number - 1));
	}

	/**
	 * @param Entity[] $entities
	 * @param int $items_per_page
	 * @return int
	 */
	public static function getFromEntityArray(array $entities, int $items_per_page): int {
		if (count($entities) > 0) {
			$total_rows = $entities[0]->getTotalRows();
			return self::getTotalPages($total_rows, $items_per_page);
		}

		return 1;
	}

}
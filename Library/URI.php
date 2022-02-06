<?php
class URI {

	public static function create(string $path, array $params = [], string $domain = ''): string {
		$path = ltrim($path, '/');

		if ($domain === '') {
			$domain = $_SERVER['SERVER_NAME'];
		}

		$parameters = '';
		if (!empty($params)) {
			$parameters = http_build_query($params);
		}

		return sprintf(
			'https://%s/%s%s%s',
			$domain,
			$path,
			(($parameters !== '') ? '?': ''),
			$parameters
		);
	}

}
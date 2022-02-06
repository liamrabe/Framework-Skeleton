<?php
/**
 * Send user to selected location
 *
 * @param string $location
 * @param int $status
 */
function redirect(string $location, int $status = 200): void {
	header(sprintf('Location: %s', $location), $status);
}

/**
 * Alias for sprintf with htmlspecialchars on the format
 *
 * @param $_
 * @return string
 */
function __($_): string {
	$args = func_get_args();
	$format = array_shift($args);

	foreach ($args as &$arg) {
		$arg = htmlspecialchars($arg, ENT_COMPAT, 'utf-8');
	}

	return vsprintf($format, $args);
}

/**
 * @param int $number
 * @return string
 */
function numberFormat(int $number): string {
	return number_format($number, 0, '', ' ');
}

/**
 * Gets environment variable or returns default value
 *
 * @param string $env
 * @param mixed $default
 * @return mixed
 */
function getEnvDefault(string $env, mixed $default): mixed {
	return ((getenv($env)) ?: $default);
}

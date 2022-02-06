<?php
const VERSION = '1.0.0';

const IS_SCRIPT = PHP_SAPI === 'cli';

if (IS_SCRIPT) {
	error_reporting(E_ALL);
	ini_set('display_errors', '1');
}

const BASE_DIR = __DIR__ . '/..';

require BASE_DIR . '/Library/Autoload.php';
Autoload::register();

define("DEV", (bool) getEnvDefault('DEV', false));

require BASE_DIR . '/Public/Routing.php';

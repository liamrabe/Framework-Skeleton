<?php
class Autoload {

	protected const LIBRARY_PATH = BASE_DIR . '/Library';

	/**
	 * Register autoloader function
	 */
	public static function register(): void {
		try {
			spl_autoload_register([self::class, 'load']);

			$vendor_path = BASE_DIR . '/vendor/autoload.php';
			$functions_path = BASE_DIR . '/Public/Functions.php';

			if (file_exists($vendor_path)) {
				require $vendor_path;
			}

			if (file_exists($functions_path)) {
				require $functions_path;
			}
		}
		catch (TypeError $ex) {
			// Stop application here, nothing will work without this.
			exit;
		}
	}

	public static function load(string $class_name): void {
		$class_path = self::getPath($class_name);

		if (!file_exists($class_path)) {
			return;
		}

		require $class_path;
	}

	public static function getPath(string $class_name): string {
		$class_name = str_replace('\\', '/', $class_name);
		return sprintf('%s/%s.php', self::LIBRARY_PATH, $class_name);
	}

}
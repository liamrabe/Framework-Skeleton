<?php
namespace Controller;

use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;

abstract class Controller {

	public static function respond(string|array $data, int $status): void {
		try {
			header('Content-Type: application/json');
			http_response_code($status);

			$response = [
				'status' => http_response_code(),
			];

			if (is_string($data)) {
				$response['message'] = $data;
			}
			else {
				$response['objects'] = count($data);
				$response['data'] = $data;
			}

			echo json_encode($response, JSON_THROW_ON_ERROR);
			exit;
		}
		catch(JsonException $ex) {
			http_response_code(500);
			exit;
		}
	}

}
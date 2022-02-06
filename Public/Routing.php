<?php

if (IS_SCRIPT) {
	return;
}

use Pecee\Http\Middleware\Exceptions\TokenMismatchException;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Pecee\SimpleRouter\Exceptions\HttpException;
use Pecee\SimpleRouter\SimpleRouter as Route;

try {
	Route::redirect('/', '/home');
	Route::start();
}
catch (NotFoundHttpException|TokenMismatchException|HttpException $ex) {
	echo $ex->getMessage();
	exit;
}
catch (Exception $e) {}

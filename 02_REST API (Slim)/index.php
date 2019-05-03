<?php
/**
 * Шаг 1: вызов основного ядра фреймворка
 */
require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();

/**
 * Шаг 2: Создаем экземпляр Slim приложения
 */
$app = new \Slim\Slim();

/**
 * Шаг 3: Конфигурация приложения
 */
$app->config('debug', true);

/**
 * Шаг 4: Объявляем маршретизацию приложения
 */


/*
 * Верификация кода
 * В этой функции мне потребуется проверять параметры GET, для этого нужно передать экземпляр приложения
 * в противном случае у меня не будет к ним доступа
 */
$app->get( '/verifyCode', function () use ($app)
{
	$code = $app->request()->get('code');

	$result = array(
			'status' => 'ok',
			'errorCode' => 0
		);

	// Простейшая проверка кода, только для примера
	if ( $code < 100 ) {
		// Возвращаем статус 403 - доступ запрещен
		$app->response->setStatus(403);
	} else {
		$app->response->setStatus(200);
		$app->response['Content-Type'] = 'application/json';
		echo json_encode( $result );
	}
});


/*
 * Выдача списка товаров
 */
$app->get( '/menu', function () use ($app)
{
	$errocode = 0;

	// Artificial delay
	sleep(5);

	// Открываем файл с данными
	// В реальной аппликации тут будет запрос в базу данных, но я пока не хочу усложнять
	try {
		$menu_json_file = fopen("menu.json", "r");
		if (! $menu_json_file) {
			$status = 'Could not open the file!';
			// Внутренняя ошибка сервера
			$errocode = 500;
		}
	} catch (Exception $e) {
		$status =  "Error (File: ".$e->getFile().", line ". $e->getLine()."): ".$e->getMessage();
		// Внутренняя ошибка сервера
		$errocode = 500;
	}
	if ( $errocode == 0 ) {
		// Если все в порядке, то открываем файл и выдаем его клиенту
		$menu_json_file = fopen("menu.json", "r") or die("Unable to open file!");
		$menu_json = fread($menu_json_file,filesize("menu.json"));
		echo $menu_json;
		$app->response->setStatus(200);
		$app->response['Content-Type'] = 'application/json';
		fclose($menu_json_file);
	} else {
		echo json_encode(array(
				'status' => $status,
				'errorCode' => $errocode
			));
		$app->response->setStatus($errocode);
		$app->response['Content-Type'] = 'application/json';
	}
	
});

/*
 * Эта функция предназначена для серверной проверки может ли клиент продолжить работу
 * В реальной ситуации может стоять задача закрывать приложение, если в течении нескольких минут не был сделан заказ
 */
$app->get( '/sessionStatus', function () use ($app)
{
	$status = true;

	if ( $status ) {
		$result = array(
			'status' => 'ok',
			'errorCode' => 0
		);
		$app->response->setStatus(200);
	} else {
		$result = array(
			'status' => 'closed',
			'errorCode' => 0
		);
		// Возвращаем статус 403 - доступ запрещен
		$app->response->setStatus(403);
	}
	
	$app->response['Content-Type'] = 'application/json';
	echo json_encode( $result );
});

/*
 * Функция принимает заказы через POST и выдает статус заказа
 */
$app->post( '/checkOut', function () use ($app)
{
	$result = array(
			'status' => 'ok',
			'errorCode' => 0
		);
	$app->response->setStatus(200);
	$app->response['Content-Type'] = 'application/json';
	echo json_encode( $result );
});

/**
 * Шаг 5: Запсукаем приложение
 *
 * Этот метод должен вызываться последним, он запскает приложение и выдает HTTP ответ клиенту
 */
$app->run();

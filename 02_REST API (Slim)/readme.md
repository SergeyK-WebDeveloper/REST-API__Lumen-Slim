Cоздание REST API сервера на Slim micro-фреймворк
===================================================

Slim – это micro-фреймворк. “Микро” в первую очередь означает, что фреймворк дает базовые инструменты, вся кастомизация уже зависит от разработчика. Никакой работы с базами данных, никаких обработчиков форм, никакой встроенной аутентификации, только запросы и только ответ. Вот список возможностей фреймворка:

*   Powerful router
*   Template rendering with custom views
*   Flash messages
*   Secure cookies with AES-256 encryption
*   HTTP caching
*   Logging with custom log writers
*   Error handling and debugging
*   Middleware and hook architecture
*   Simple configuration

Разумеется, все недостающие модули можно будет прикрутить позже - они не входят в стандартную коробку Slim.

Если вам нужно более комплексное решение, то можно посмотреть в сторону полноценного фреймворка, например, [laravel framework](http://laravel.com/).

Чтоб начать заходим на [официальный сайт Slim](http://www.slimframework.com/) и скачаем последний релиз. Установить Slim можно как через композер, так и просто скачав дистрибутив с гит-хаба. Для простоты качаем версию с гит-хаба. После скачивания Slim сразу готов к работе, достаточно просто открыть директорию в которую вы распаковали фреймворк на вашем сервере (локальном или удаленном).

(!!!) Важно – проверьте, что у вас установлен и работает rewrite\_mod, без него будет не то.

Основные элементы файла index.php, который запускает фреймворк:


```php
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
 
// GET route
$app->get(
	'/',
	function () {
		echo 'This is home page';
	}
);
 
// POST route
$app->post(
	'/post',
	function () {
		echo 'This is a POST route';
	}
);
 
// PUT route
$app->put(
	'/put',
	function () {
		echo 'This is a PUT route';
	}
);
 
// DELETE route
$app->delete(
	'/delete',
	function () {
		echo 'This is a DELETE route';
	}
);
 
/**
 * Шаг 5: Запсукаем приложение
 *
 * Этот метод должен вызываться последним, он запскает приложение и выдает HTTP ответ клиенту
 */
$app->run();
```

Теперь немного изменим раутинг. Давайте сделаем API сервис, который умел бы выполнять следующие команды:

1.  Принимать код верификации со стороны клиента, проверять его и выдавать ответ.
2.  Выдавать список товаров для отображения в приложении.
3.  Выдавать ответ открыта ли еще сессия.
4.  Принимать заказанные товары и выдавать ответ обработан ли заказ.

В этой статье не буду уделяется внимание верификации запроса (в следующий раз).

```php
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
 * В реальной ситуации может стаять задача закрывать приложение, если в течении нескольких минут не был сделан заказ
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
```

Всё – серверная часть готова к тестированию.

Ссылки по теме:  
[http://www.slimframework.com/](http://www.slimframework.com/)  

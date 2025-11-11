<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

$app = AppFactory::create();
$app->addBodyParsingMiddleware(); // parse json/form body

(require __DIR__ . '/../src/routes.php')($app);
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});
$app->add(function ($req, $handler) {
    $resp = $handler->handle($req);
    return $resp
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

$app->run();

<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Create Slim app
$app = AppFactory::create();

// Middleware to parse JSON / form bodies
$app->addBodyParsingMiddleware();

// -----------------------------------------------------------
// ✅ CORS middleware (allows Shopify frontend to call the API)
// -----------------------------------------------------------
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function ($request, $handler) {
    // Get origin of the request
    $origin = $request->getHeaderLine('Origin');
    $allowed = [
        'https://brainx-wishlist.myshopify.com',  // ✅ your Shopify store domain
        'https://admin.shopify.com'               // (optional) Shopify admin preview
    ];

    $response = $handler->handle($request);

    // Only allow specific origins
    if (in_array($origin, $allowed)) {
        $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
    }

    return $response
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
});

// -----------------------------------------------------------
// ✅ Optional root route for Render health check
// -----------------------------------------------------------
$app->get('/', function ($req, $res) {
    $res->getBody()->write(json_encode(['status' => 'ok', 'app' => 'PHP Wishlist API']));
    return $res->withHeader('Content-Type', 'application/json');
});

// -----------------------------------------------------------
// ✅ Load routes (wishlist endpoints)
// -----------------------------------------------------------
(require __DIR__ . '/../src/routes.php')($app);

// -----------------------------------------------------------
// ✅ Run the Slim app
// -----------------------------------------------------------
$app->run();

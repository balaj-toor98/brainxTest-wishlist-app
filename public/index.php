<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Create Slim app
$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// -----------------------------------------------------------
// ✅ CORS Middleware (handles preflight + all responses)
// -----------------------------------------------------------
$app->add(function (Request $request, RequestHandler $handler): Response {
    $response = $handler->handle($request);

    $origin = $request->getHeaderLine('Origin');
    $allowedOrigins = [
        'https://brainx-wishlist.myshopify.com',
        'https://admin.shopify.com'
    ];

    // Always add CORS headers
    if (in_array($origin, $allowedOrigins)) {
        $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
    } else {
        // For debugging, allow temporarily all origins (uncomment for testing)
        // $response = $response->withHeader('Access-Control-Allow-Origin', '*');
    }

    return $response
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// Preflight OPTIONS handler for any route
$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response->withStatus(200);
});

// -----------------------------------------------------------
// ✅ Root route (for testing / healthcheck)
// -----------------------------------------------------------
$app->get('/', function ($req, $res) {
    $data = ['status' => 'ok', 'app' => 'Wishlist API running'];
    $res->getBody()->write(json_encode($data));
    return $res->withHeader('Content-Type', 'application/json');
});

// -----------------------------------------------------------
// ✅ Include API routes
// -----------------------------------------------------------
(require __DIR__ . '/../src/routes.php')($app);

// -----------------------------------------------------------
// ✅ Run the Slim app
// -----------------------------------------------------------
$app->run();

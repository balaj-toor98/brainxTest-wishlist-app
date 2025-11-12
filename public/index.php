<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

// Load .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Create Slim app
$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// ------------------------------------------------------------
// ✅ Global CORS middleware (handles all responses & errors)
// ------------------------------------------------------------
$app->add(function (Request $request, RequestHandler $handler): Response {
    try {
        $response = $handler->handle($request);
    } catch (Throwable $e) {
        // Handle exceptions manually so CORS headers still get added
        $response = new Slim\Psr7\Response();
        $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        $response = $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }

    $origin = $request->getHeaderLine('Origin');
    $allowed = [
        'https://brainx-wishlist.myshopify.com',
        'https://admin.shopify.com',
    ];

    if (in_array($origin, $allowed)) {
        $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
    } else {
        // for testing only — uncomment temporarily if still blocked
        // $response = $response->withHeader('Access-Control-Allow-Origin', '*');
    }

    return $response
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// Preflight handler for OPTIONS requests
$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response->withStatus(200);
});

// ------------------------------------------------------------
// ✅ Root route (healthcheck)
// ------------------------------------------------------------
$app->get('/', function ($req, $res) {
    $data = ['status' => 'ok', 'app' => 'Wishlist API running'];
    $res->getBody()->write(json_encode($data));
    return $res->withHeader('Content-Type', 'application/json');
});

// ------------------------------------------------------------
// ✅ Register API routes
// ------------------------------------------------------------
(require __DIR__ . '/../src/routes.php')($app);

// ------------------------------------------------------------
// ✅ Error Middleware (ensures JSON + CORS on 404)
// ------------------------------------------------------------
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setDefaultErrorHandler(function (
    Request $request,
    Throwable $exception,
    bool $displayErrorDetails
) use ($app) {
    $payload = ['error' => $exception->getMessage()];
    $response = $app->getResponseFactory()->createResponse();
    $response->getBody()->write(json_encode($payload));

    $origin = $request->getHeaderLine('Origin');
    $allowed = [
        'https://brainx-wishlist.myshopify.com',
        'https://admin.shopify.com',
    ];
    if (in_array($origin, $allowed)) {
        $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
    }

    return $response
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Content-Type', 'application/json');
});

// ------------------------------------------------------------
// Run app
// ------------------------------------------------------------
$app->run();

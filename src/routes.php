<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\ShopifyClient;

return function($app) {
    // CORS: whitelist exact Shopify origin(s)
    $allowedOrigins = [
        'https://brainx-wishlist.myshopify.com',
    ];

    // Global middleware to handle CORS and ensure headers on all responses (including errors)
    $app->add(function (Request $request, RequestHandler $handler) use ($allowedOrigins) {
        $origin = $request->getHeaderLine('Origin');
        $allowedOrigin = in_array($origin, $allowedOrigins, true) ? $origin : null;

        // Short-circuit OPTIONS preflight
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            $res = new \Slim\Psr7\Response();
            if ($allowedOrigin) {
                $res = $res->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
                           ->withHeader('Vary', 'Origin')
                           ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Accept, Origin, Authorization')
                           ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                           ->withHeader('Access-Control-Allow-Credentials', 'true');
            }
            return $res;
        }

        try {
            $response = $handler->handle($request);
        } catch (\Throwable $e) {
            // Ensure CORS headers on error responses
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode(['error' => 'Internal Server Error']));
            $response = $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        if ($allowedOrigin) {
            $response = $response
                ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
                ->withHeader('Vary', 'Origin')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    });

    // Keep an OPTIONS catch-all route for clients/servers that hit it directly
    $app->options('/{routes:.+}', function (Request $req, Response $res) use ($allowedOrigins) {
        $origin = $req->getHeaderLine('Origin');
        $allowedOrigin = in_array($origin, $allowedOrigins, true) ? $origin : null;
        if ($allowedOrigin) {
            return $res
                ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
                ->withHeader('Vary', 'Origin')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->withHeader('Access-Control-Allow-Credentials', 'true');
        }
        return $res;
    });

    $shopify = new ShopifyClient();
    $app->get('/', function($req, $res) {
        $res->getBody()->write("Slim PHP Wishlist API is running 🚀");
        return $res;
    });

    // POST /api/wishlist/add
    $app->post('/api/wishlist/add', function(Request $req, Response $res) use ($shopify) {
        $data = $req->getParsedBody();
        $customerId = $data['customerId'] ?? null;
        $productId = $data['productId'] ?? null;
        if (!$customerId || !$productId) {
            $res->getBody()->write(json_encode(['error' => 'customerId and productId required']));
            return $res->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        // Fetch existing wishlist
        $mf = $shopify->getCustomerWishlist($customerId);
        $current = [];
        if (!empty($mf['metafields'])) {
            $current = json_decode($mf['metafields'][0]['value'], true);
        }
        if (!in_array($productId, $current)) $current[] = $productId;
        $shopify->saveCustomerWishlist($customerId, $current);
        $res->getBody()->write(json_encode(['success'=>true, 'wishlist'=>$current]));
        return $res->withHeader('Content-Type', 'application/json');
    });

    // GET /api/wishlist?customerId=...
    $app->get('/api/wishlist', function(Request $req, Response $res) use ($shopify) {
        $params = $req->getQueryParams();
        $customerId = $params['customerId'] ?? null;
        if (!$customerId) {
            $res->getBody()->write(json_encode(['error' => 'customerId required']));
            return $res->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $mf = $shopify->getCustomerWishlist($customerId);
        $list = [];
        if (!empty($mf['metafields'])) {
            $list = json_decode($mf['metafields'][0]['value'], true);
        }
        $res->getBody()->write(json_encode(['wishlist'=>$list]));
        return $res->withHeader('Content-Type', 'application/json');
    });

    // DELETE /api/wishlist/remove
    $app->delete('/api/wishlist/remove', function(Request $req, Response $res) use ($shopify) {
        $data = $req->getParsedBody();
        $customerId = $data['customerId'] ?? null;
        $productId = $data['productId'] ?? null;
        if (!$customerId || !$productId) {
            $res->getBody()->write(json_encode(['error' => 'customerId and productId required']));
            return $res->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        $mf = $shopify->getCustomerWishlist($customerId);
        $current = [];
        if (!empty($mf['metafields'])) {
            $current = json_decode($mf['metafields'][0]['value'], true);
        }
        $current = array_values(array_filter($current, function($p) use ($productId){ return $p != $productId; }));
        $shopify->saveCustomerWishlist($customerId, $current);
        $res->getBody()->write(json_encode(['success'=>true,'wishlist'=>$current]));
        return $res->withHeader('Content-Type', 'application/json');
    });
};

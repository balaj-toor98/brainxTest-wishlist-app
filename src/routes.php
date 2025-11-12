<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\ShopifyClient;

return function($app) {
    $shopify = new ShopifyClient();

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

        if (!in_array($productId, $current)) {
            $current[] = $productId;
        }

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

        $current = array_values(array_filter($current, function($p) use ($productId){
            return $p != $productId;
        }));

        $shopify->saveCustomerWishlist($customerId, $current);
        $res->getBody()->write(json_encode(['success'=>true,'wishlist'=>$current]));
        return $res->withHeader('Content-Type', 'application/json');
    });
};

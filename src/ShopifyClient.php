<?php
namespace App;

use GuzzleHttp\Client;

class ShopifyClient {
    private $client;
    private $shop;
    private $token;
    private $version;

    public function __construct() {
        $this->shop = getenv('SHOPIFY_SHOP');
        $this->token = getenv('SHOPIFY_ADMIN_TOKEN');
        $this->version = getenv('SHOPIFY_API_VERSION') ?: '2024-10';
        $this->client = new Client([
            'base_uri' => "https://{$this->shop}/admin/api/{$this->version}/",
            'headers' => [
                'X-Shopify-Access-Token' => $this->token,
                'Accept' => 'application/json',
            ],
            'http_errors' => false
        ]);
    }

    // Save wishlist (array of product IDs) as customer metafield (namespace: wishlist_app)
    public function saveCustomerWishlist($customerId, array $productIds) {
        $payload = [
            'metafield' => [
                'namespace' => 'wishlist_app',
                'key' => 'wishlist',
                'value' => json_encode(array_values($productIds)),
                'type' => 'json'
            ]
        ];
        // Try to find existing metafield first
        $res = $this->client->get("customers/{$customerId}/metafields.json?namespace=wishlist_app&key=wishlist");
        $body = json_decode($res->getBody()->getContents(), true);
        if (!empty($body['metafields'])) {
            $mf = $body['metafields'][0];
            $mfId = $mf['id'];
            return $this->client->put("metafields/{$mfId}.json", ['json' => $payload]);
        } else {
            // Create new metafield
            return $this->client->post("customers/{$customerId}/metafields.json", ['json' => $payload]);
        }
    }

    public function getCustomerWishlist($customerId) {
        $res = $this->client->get("customers/{$customerId}/metafields.json?namespace=wishlist_app&key=wishlist");
        return json_decode($res->getBody()->getContents(), true);
    }
}

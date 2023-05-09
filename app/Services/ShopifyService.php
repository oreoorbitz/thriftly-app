<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ShopifyService
{
    protected $api_key;
    protected $password;
    protected $store_url;
    protected $version;

    public function __construct()
    {
        $this->api_key = env('SHOPIFY_API_KEY');
        $this->password = env('SHOPIFY_PASSWORD');
        $this->store_url = env('SHOPIFY_STORE_URL');
        $this->version = env('SHOPIFY_VERSION');
    }

    public function countProducts()
    {
        $response = Http::withBasicAuth($this->api_key, $this->password)
            ->get("https://{$this->store_url}/admin/api/{$this->version}/products/count.json");
        return $response->json();
    }

    public function getProducts($since_id)
    {
        $response = Http::withBasicAuth($this->api_key, $this->password)
            ->get("https://{$this->store_url}/admin/api/{$this->version}/products.json?fields=id,handle,variants&since_id=$since_id");
        return $response->json();
    }

    public function updateProduct($productId, $productData)
    {
        $response = Http::withBasicAuth($this->api_key, $this->password)
            ->put("https://{$this->store_url}/admin/api/{$this->version}/products/{$productId}.json", [
                'product' => $productData,
            ]);
        return $response->json();
    }

    public function updateProductVariant($variant_id, $variantData)
    {
        $response = Http::withBasicAuth($this->api_key, $this->password)
            ->put("https://{$this->store_url}/admin/api/{$this->version}/variants/{$variant_id}.json", [
                'variant' => $variantData,
            ]);
        return $response->json();
    }

    public function getMetafield($variant_id)
    {
        $response = Http::withBasicAuth($this->api_key, $this->password)
            ->get("https://{$this->store_url}/admin/api/{$this->version}/variants/{$variant_id}/metafields.json");
        return $response->json();
    }
}

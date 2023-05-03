<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ShopifyService;

class ShopifyController extends Controller
{
    protected $shopifyService;

    public function __construct(ShopifyService $shopifyService)
    {
        $this->shopifyService = $shopifyService;
    }

    public function countProducts()
    {
        $products = $this->shopifyService->countProducts();
        return $products;
    }

    public function getProducts()
    {
        $count = $this->countProducts();
        $total_page = ceil($count['count']/50);
        $page = 1;
        $since_id = 0;
        while($page <= $total_page){
            $products = $this->shopifyService->getProducts($since_id);
            foreach ($products['products'] as $value) {
                foreach ($value['variants'] as $variant) {
                    if($variant['product_id'] == '8354369601855'){ //--Comment this line for production
                        $price = floatval($variant['price']);
                        $compare_at_price = floatval($variant['compare_at_price']);
                        $percentage = ($price / $compare_at_price) * 100;

                        $round_count = 1;
                        $metafield_id = null;
                        $metafields = $this->shopifyService->getMetafield($variant['id']);
                        foreach ($metafields['metafields'] as $metafield) {
                            if($metafield['key'] == 'round_count'){
                                if($metafield['value'] > 0){
                                    $round_count = $metafield['value'] + 1;
                                }
                                $metafield_id = $metafield['id'];
                            }
                        }
                        if($round_count > 16){
                            continue;
                        }
                        if($percentage == 10 || $percentage == 100){
                            $percentage = 90;
                        }
                        
                        $final_price = ($compare_at_price * ($percentage - 10)) / 100;
                        $variantData = array(
                            'id' => $variant['id'],
                            'price' => $final_price,
                            'metafields' => array(
                                array(
                                    "id" => $metafield_id,
                                    "key" => "round_count",
                                    "value" => $round_count,
                                    "type" => "number_integer",
                                    "namespace" => "custom"
                                )
                            )
                        );
                        $response = $this->shopifyService->updateProductVariant($variant['id'], $variantData);
                        if($round_count >= 16){
                            dd('watchlist clear.'); //--Manage watchlist api
                        }
                    }//--Comment this line for production
                }
            }
            $since_id = end($products['products'])['id'];
            $page++;
        }
        return true;
    }
    
}

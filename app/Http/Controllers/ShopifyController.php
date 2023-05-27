<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ShopifyService;
use Illuminate\Support\Facades\Http;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Events\PodcastProcessed;
use DB;

class ShopifyController extends Controller
{
    protected $shopifyService;

    public function __construct(ShopifyService $shopifyService){
        $this->shopifyService = $shopifyService;
    }

    public function countProducts(Request $request){
        $products = $this->shopifyService->countProducts();
        return $products;
    }
    
    //--Get watch list.
    public function getWatchlist(Request $request){
        return true;
    }

    //--Add watch list.
    public function addWatchlist(Request $request){
        $input = $request->input();
        
        $product = DB::table("watchlist")->where('product_id', (int)$input['product_id'])->first();
        if($product){
            $customer = json_decode($product->customer, true);
            if(isset($customer[$input['email']])){
                $response = ['status' => false, 'content' => 'customer already in watchlist.'];
            }else{
                $customer[$input['email']] = $input['name'];
                $data['customer'] = json_encode($customer);
                $data['watch_count'] = $product->watch_count + 1;
                DB::table("watchlist")->where('product_id', (int)$input['product_id'])->update($data);
                
                $metafields = $this->shopifyService->getMetafield($input['product_id']);
                if(!isset($metafields['metafields'])){
                    $response = ['status' => false, 'watch_count' => $data['watch_count'], 'content' => 'customer added in watchlist. Metafield not set.'];
                }else{
                    $metafield_id = null;
                    foreach ($metafields['metafields'] as $metafield) {
                        if($metafield['key'] == 'watch_count'){
                            $metafield_id = $metafield['id'];
                        }
                    }
                    $variantData = array(
                        'id' => $input['product_id'],
                        'metafields' => array(
                            array(
                                "id" => $metafield_id,
                                "key" => "watch_count",
                                "value" => $data['watch_count'],
                                "type" => "number_integer",
                                "namespace" => "custom"
                            )
                        )
                    );
                    $this->shopifyService->updateProductVariant($input['product_id'], $variantData);
                    $response = ['status' => true, 'watch_count' => $data['watch_count'], 'content' => 'customer added in watchlist.'];
                }
            }
        }else{
            $data['product_id'] = $input['product_id'];
            $data['customer'] = json_encode([$input['email'] => $input['name']]);
            $data['watch_count'] = 1;
            DB::table("watchlist")->insert($data);

            $metafields = $this->shopifyService->getMetafield($input['product_id']);
            if(!isset($metafields['metafields'])){
                $response = ['status' => false, 'watch_count' => $data['watch_count'], 'content' => 'customer added in watchlist with new product. Metafield not set.'];
            }else{
                $metafield_id = null;
                foreach ($metafields['metafields'] as $metafield) {
                    if($metafield['key'] == 'watch_count'){
                        $metafield_id = $metafield['id'];
                    }
                }
                $variantData = array(
                    'id' => $input['product_id'],
                    'metafields' => array(
                        array(
                            "id" => $metafield_id,
                            "key" => "watch_count",
                            "value" => $data['watch_count'],
                            "type" => "number_integer",
                            "namespace" => "custom"
                        )
                    )
                );
                $this->shopifyService->updateProductVariant($input['product_id'], $variantData);
                $response = ['status' => true, 'watch_count' => $data['watch_count'], 'content' => 'customer added in watchlist with new product.'];
            }
            
        }
        return json_encode($response);
    }

    //--Remove watch list.
    public function clearWatchlist($id){
        $metafields = $this->shopifyService->getMetafield($id);
        if(!isset($metafields['metafields'])){
            $response = ['status' => false, 'content' => 'Wishlist not clear.'];
        }else{
            DB::table("watchlist")->where('product_id', (int)$id)->delete();
            $metafield_id = null;
            foreach ($metafields['metafields'] as $metafield) {
                if($metafield['key'] == 'watch_count'){
                    $metafield_id = $metafield['id'];
                }
            }
            $variantData = array(
                'id' => $id,
                'metafields' => array(
                    array(
                        "id" => $metafield_id,
                        "key" => "watch_count",
                        "value" => 0,
                        "type" => "number_integer",
                        "namespace" => "custom"
                    )
                )
            );
            $this->shopifyService->updateProductVariant($id, $variantData);
            $response = ['status' => true, 'content' => 'Wishlist clear.'];
        }
        return json_encode($response);
    }

}

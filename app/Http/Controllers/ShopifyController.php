<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ShopifyService;
use Illuminate\Support\Facades\Http;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Events\PodcastProcessed;
use DB;
use Illuminate\Support\Facades\Log;

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
            $watch_count_id = null;
            $round_count_id = null;
            foreach ($metafields['metafields'] as $metafield) {
                if($metafield['key'] == 'watch_count'){
                    $watch_count_id = $metafield['id'];
                }
                if($metafield['key'] == 'round_count'){
                    $round_count_id = $metafield['id'];
                }
            }
            $variantData = array(
                'id' => $id,
                'metafields' => array(
                    array(
                        "id" => $watch_count_id,
                        "key" => "watch_count",
                        "value" => 0,
                        "type" => "number_integer",
                        "namespace" => "custom"
                    ),
                    array(
                        "id" => $round_count_id,
                        "key" => "round_count",
                        "value" => 0,
                        "type" => "number_integer",
                        "namespace" => "custom"
                    )
                )
            );
            $this->shopifyService->updateProductVariant($id, $variantData);
            $response = ['status' => true, 'content' => 'Wishlist clear.'];
        }
        return $response;
    }

    public function orderHook(Request $request){
        $data = file_get_contents('php://input');
        $order= json_decode($data, true);
        foreach ($order['line_items'] as $key => $value) {

            $product = $this->shopifyService->getOneProducts($value['product_id']);
            $collect_new_arrival = $this->shopifyService->getProducts($value['product_id']);

            $this->mailSend($value["variant_id"], $product['product'], $collect_new_arrival['products']);
        }
        return;
    }

    //--Send mail to customer.
    private function mailSend($id, $product, $collect_new_arrival)
    {
        $product_db = DB::table("watchlist")->where('product_id', $id)->first();
        if(!$product_db){
            return;
        }
        $customers = json_decode($product_db->customer, true);
        // Log::info($customers);
        if($customers){
            foreach ($customers as $email => $name) {
                //--Email server settings
                $mail = new PHPMailer(true);
                $mail->SMTPDebug = 0;
                $mail->isSMTP();
                $mail->Host = env('MAIL_HOST');
                $mail->SMTPAuth = true;
                $mail->Username = env('MAIL_USERNAME');
                $mail->Password = env('MAIL_PASSWORD');
                $mail->SMTPSecure = env('MAIL_ENCRYPTION');
                $mail->Port = env('MAIL_PORT');
                $mail->From = env('MAIL_FROM_ADDRESS');
                $mail->addAddress($email);
                $mail->Subject = "Thriflty update";
                $mail->isHTML(true);

                $mail->Body = view('emails.sold_out', ['data' => $product, 'customer_name' => $name, 'new_arraival' => $collect_new_arrival])->render();
                
                try {
                    $mail->send();
                    // Log::info("sent");
                } catch (Exception $e) {
                    Log::error($e);
                }
                sleep(1);
            }
            $this->clearWatchlist($id);
        }
        return true;
    }

}

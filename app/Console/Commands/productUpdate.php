<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ShopifyService;
use App\Events\PodcastProcessed;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use DB;

class productUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discount updated successfully.';

    protected $shopifyService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ShopifyService $shopifyService)
    {
        parent::__construct();
        $this->shopifyService = $shopifyService;
    }

    //--Count products.
    public function countProducts()
    {
        $products = $this->shopifyService->countProducts();
        return $products;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $total_update = 0;
        $total_un_update = 0;
        $count = $this->countProducts();
        $total_page = ceil($count['count']/50);
        $page = 1;
        $since_id = 0;
        $collect_product = [];
        $collect_soldout = [];
        $collect_new_arrival = [];
        while($page <= $total_page){
            $products = $this->shopifyService->getProducts($since_id);
            if(isset($products['products'])){
                foreach ($products['products'] as $value) {
                    // if($value['id'] != 8265575825727){
                    //     continue;
                    // }
                    foreach ($value['variants'] as $variant) {
                        if($variant['inventory_quantity'] <= 0){
                            $collect_soldout[] = $value;
                            continue;
                        }
                        
                        if(!isset($variant['compare_at_price']) || empty($variant['compare_at_price']) || $variant['compare_at_price'] <=0 || $variant['price'] <= 0 ){
                            continue;
                        }
                        
                        try{
                            $price = floatval($variant['price']);
                            $compare_at_price = floatval($variant['compare_at_price']);
                            $percentage = ($price / $compare_at_price) * 100;
                            $round_count = 1;
                            $metafield_id = null;
                            $metafields = $this->shopifyService->getMetafield($variant['id']);
                            if(!isset($metafields['metafields'])){
                                continue;
                            }
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
                            if($percentage <= 10 || $percentage == 100){
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
                            sleep(1);
                            $response = $this->shopifyService->updateProductVariant($variant['id'], $variantData);
                            $this->info($value['handle']." => Updated.");
                            $total_update++;
                            
                            $value['new_price'] = $response['variant']['price'];
                            $collect_product[] = $value;

                            if(count($collect_new_arrival) < 5){
                                $collect_new_arrival[] = $value;
                            } 

                            if($round_count == 9 || $round_count >= 16){
                                //--Remove this product watchlist.
                                $clearWatchlist = $this->clearWatchlist($variant['id']);
                                if($clearWatchlist['status']){
                                    $this->info("-----Watchlist clear.");
                                }else{
                                    $this->info("-----Watchlist not clear.");
                                }
                            }

                        }catch (\Exception $e) {
                            \Log::error($e);
                            $this->info($value['handle']." => ********( Not Updated )*******".$e->getMessage());
                            $total_un_update++;
                        }
                        // sleep(1);
                    }
                }
                $since_id = end($products['products'])['id'];
            }
            $page++;
        }
        $this->info("Total Discount updated ( $total_update ) successfully.");

        //--Send email to customer when discount updated.
        if($collect_product){
            $this->info("Sending mail for price update...");
            $this->mailSend($collect_product, "", $collect_new_arrival);
        }
        // if($collect_soldout){
        //     $this->info("Sending mail for sold out...");
        //     $this->mailSend($collect_soldout, "sold", $collect_new_arrival);
        // }
        $this->info("Done.");

        return true;
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
                    )
                    // array(
                    //     "id" => $round_count_id,
                    //     "key" => "round_count",
                    //     "value" => 0,
                    //     "type" => "number_integer",
                    //     "namespace" => "custom"
                    // )
                )
            );
            $this->shopifyService->updateProductVariant($id, $variantData);
            $response = ['status' => true, 'content' => 'Wishlist clear.'];
        }
        return $response;
    }

    //--Send mail to customer.
    private function mailSend($collect_product, $stock, $collect_new_arrival)
    {
        foreach ($collect_product as $product) {
            $product_db = DB::table("watchlist")->where('product_id', $product['variants'][0]['id'])->first();
            if(!$product_db){
                continue;
            }
            $customers = json_decode($product_db->customer, true);
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

                if($stock == "sold"){
                    // $mail->Body = view('emails.sold_out', ['data' => $product, 'customer_name' => $name, 'new_arraival' => $collect_new_arrival])->render();
                    // $this->clearWatchlist($product['variants'][0]['id']);
                }else{
                    $mail->Body = view('emails.price_drop', ['data' => $product, 'customer_name' => $name, 'new_arraival' => $collect_new_arrival])->render();
                }

                try {
                    $mail->send();
                } catch (Exception $e) {
                    \Log::error($e);
                }
                sleep(1);
            }
        }

        $response = [
            'status' => true
        ];
        return $response;
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ShopifyService;
use Illuminate\Support\Facades\Http;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Events\PodcastProcessed;

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
        set_time_limit(1000);
        $count = $this->countProducts();
        $total_page = ceil($count['count']/50);
        $page = 1;
        $since_id = 0;
        while($page <= $total_page){
            $products = $this->shopifyService->getProducts($since_id);
            foreach ($products['products'] as $value) {
                //--Comment this line for production
                // if($value['id'] != '8362488398143'){
                //     continue;
                // }
                foreach ($value['variants'] as $variant) {
                    if($variant['inventory_quantity'] <= 0){
                        continue;
                    }
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
                    // $this->mailSend();
                    if($round_count == 9 || $round_count >= 16){
                        // event(new PodcastProcessed()); //--Remove this product watchlist.
                    }
                }
                
            }
            $since_id = end($products['products'])['id'];
            $page++;
        }
        return true;
    }
    
    //--Get watch list.
    public function getWatchlist(){}

    //--Add watch list.
    public function addWatchlist(){}

    //--Remove watch list.
    public function clearWatchlist(){
		$data = [
			"product_id"=> 8354369601855,
			"customer_id"=> 6967658217791,
			"email"=> "jagdeep.singh109155@gmail.com",
		];
		// return $response->json();
    }

    //--Send mail to customer.
    private function mailSend(){
        $mail = new PHPMailer();

        // Email server settings
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = env('MAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = env('MAIL_USERNAME');
        $mail->Password = env('MAIL_PASSWORD');
        $mail->SMTPSecure = env('MAIL_ENCRYPTION');
        $mail->Port = env('MAIL_PORT');
        $mail->addAddress('govindersingh0595@gmail.com');
        $mail->Subject = "Product discount increase";
        $mail->isHTML(true);
        $mail->Body = '<h2>Check now!</h2>';  
        
        // Send email  
        if(!$mail->send()){  
            echo 'Message could not be sent. Mailer Error: '.$mail->ErrorInfo;  
        }else{  
            echo 'Message has been sent.';  
        }
    }
}

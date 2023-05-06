<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ShopifyService;
use App\Events\PodcastProcessed;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $count = $this->countProducts();
        $total_page = ceil($count['count']/50);
        $page = 1;
        $since_id = 0;
        while($page <= $total_page){
            $products = $this->shopifyService->getProducts($since_id);
            foreach ($products['products'] as $value) {
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
        $this->info($this->description);
    }

    //--Count products.
    public function countProducts()
    {
        $products = $this->shopifyService->countProducts();
        return $products;
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

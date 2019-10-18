<?php

namespace App\Http\Controllers;

use App\User;
use App\OrderBottomry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Symfony\Component\Console\Input\Input;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        

        // //$this->middleware('auth');
       
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }



    // uninstall APP
    public function orderpaid(Request $req){
      
            //return 1;$req->session()->token();

      //$shopify_hmac_k=env('SHOPIFY_SECRET')    
      define('SHOPIFY_APP_SECRET', 'd67d2b24999aafb7182b68a89675e2e9');
      function verify_webhook($data, $hmac_header)
      {
          $calculated_hmac = base64_encode(hash_hmac('sha256', $data, SHOPIFY_APP_SECRET, true));
          return hash_equals($hmac_header, $calculated_hmac);
      }

      $hmac_header = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'];
      $data = file_get_contents('php://input');
      // $data1=json_decode($data, true);

     $verified = verify_webhook($data, $hmac_header);
      error_log('Webhook verified asdasdasd: '.var_export($verified, true)); //check error.log to see the result*/
      
      if($verified){
          $code= $req->query->get('code'); 
          $hmac= $req->query->get('hmac'); 
          $shop= $req->query->get('shop');
          $storename=explode('.',$shop);
          $params = $_GET;
          $shopify_hmac = env('SHOPIFY_SECRET');
          $params = array_diff_key($params, array('hmac' => ''));   // Remove hmac from params
          ksort($params); // Sort params lexographically
          // Compute SHA256 digest
          $computed_hmac = hash_hmac('sha256', http_build_query($params), $shopify_hmac);
          $reqtime= $req->query->get('timestamp');  
          if (hash_equals($hmac, $computed_hmac)) {
              $flag =0; $shippingProtectionCost =0;  $bottomryOrderNumber = rand(111111,999999);
                $order_number   = $req->query->get('order_number');
                $orderDate      = $req->query->get('created_at');
                $total_price    = $req->query->get('total_price');
                $orderitems     = $req->query->get('line_items');
                
                for($i = 0; $i < count($orderitems); $i++){
                  if($orderitems[$i]->title == 'Bottomry Shipping Insurance'){
                     $shippingProtectionCost ='0.98'; 
                    $flag++;
                  }
                }

              if($flag>0){
                $norder = new OrderBottomry;
                $norder['shopName']               = $shop;
                $norder['orderDate']              = $orderDate;
                $norder['storeOrderNumber']       = $order_number;
                $norder['bottomryOrderNumber']    = $bottomryOrderNumber;
                $norder['customerName']           = "";
                $norder['subTotal']               = $total_price;
                $norder['shippingProtectionCost'] = $shippingProtectionCost;
                $norder->save();
              
              $lid = $norder->id;
                if($lid){
                  return "200 OK";
                }else{
                  return 0;
                }
              }
          }
            //return $req;
      } // if verified

      
    }
}

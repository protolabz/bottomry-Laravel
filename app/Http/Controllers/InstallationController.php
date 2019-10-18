<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Socialite;
use App\Store;
use App\User;
use App\UserProviders;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Console\Input\Input;
use Illuminate\Support\Facades\Session;
use App\Campaign;
use App\Pricerule;
class InstallationController extends Controller
{
    
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $req)
    {
      if($req->has('hmac')){  
          $shopify_hmac=env('SHOPIFY_SECRET');
          $code= $req->query->get('code'); 
          $hmac= $req->query->get('hmac'); 
          $shop= $req->query->get('shop');
          $storename=explode('.',$shop);
          $params = $_GET;
          $shop_data["shop"] = $shop;
          $params = array_diff_key($params, array('hmac' => ''));   // Remove hmac from params
          ksort($params); // Sort params lexographically

          // Compute SHA256 digest
          $computed_hmac = hash_hmac('sha256', http_build_query($params), $shopify_hmac);
          $reqtime= $req->query->get('timestamp');  
          if(hash_equals($hmac, $computed_hmac)) {
              // Set variables for our request
              $query = array(
                "client_id" => env('SHOPIFY_KEY'), // Your API key
                "scope" => 'read_orders,write_products',
              );
              $getuser=User::where('name', $shop)->first();
              //print_r($getuser);
              $userresult = json_decode($getuser, true);
              $is_user = $getuser->count();
              if($is_user>0){
                $shops_token = $getuser->access_token;
                // Generate access token URL
                $access_token_url = "http://". $params['shop'] . "/admin/oauth/authorize";
                // Configure curl client and execute request
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_URL, $access_token_url);
                curl_setopt($ch, CURLOPT_POST, count($query));
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
                // curl_setopt($ch, CURLOPT_HEADER, array("X-Shopify-Access-Token:$shops_token"));
                //print_r($ch);
                $result1 = curl_exec($ch);
                $result = json_encode($result1, true);

                curl_close($ch);
                $result = json_decode($result, true);
                
                // return redirect("https://phpstack-102119-1018622.cloudwaysapps.com/login");
                echo "<script>window.location('https://phpstack-102119-1018622.cloudwaysapps.com/login');</script>";
                //return view('rules',['data'=>$shop_data,'products'=>$proResult["products"]]);
              }else{
                echo "<script>window.location('https://phpstack-102119-1018622.cloudwaysapps.com/shopify?$shop');</script>";
              }
            }  
        }else{
          echo "<script>window.location('https://phpstack-102119-1018622.cloudwaysapps.com/shopify?$shop');</script>";
        }
    }

    
    /**
     * Load Registration Page
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function registerMe(){
      $arr = [];
      $shop= \Illuminate\Support\Facades\Input::get('shop');
      $arr["shop"] =  $shop;
      return view('welcome')->with('shopname', $shop);
    }




    /**
     * Redirect to Installation page.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function installapp()
    {
      //$shop= \Illuminate\Support\Facades\Input::get('shop');
      $shop= $_POST["shopname"];
      //echo $shop; die;
      $user= new User;
      $user['name'] = $shop;
      $user['domainname'] = $shop;
      $user['first_name'] = $_POST["fname"];
      $user['last_name']  = $_POST["lname"];
      $user['email']      = $_POST["email"];
      $user['password']   = Hash::make($_POST["pass"]);
      $user->save();

      $lid = $user->id;
      if($lid){
        $api_key = env('SHOPIFY_KEY');
        $scopes = env('SHOPIFY_SCOPE');
        $keysecret = env('SHOPIFY_SECRET');
        $redirect_uri = env('SHOPIFY_REDIRECT'); 
        // Build install/approval URL to redirect to
        $install_url = "https://" . $shop . "/admin/oauth/authorize?client_id=" . $api_key . "&scope=" . $scopes . "&redirect_uri=" . urlencode($redirect_uri);
        // Redirect
        return redirect($install_url);
          // return view('welcome'); 
      }else{
        echo "error";
      }
    }
}

<?php

namespace App\Http\Controllers;

use Socialite;
use App\Store;
use App\User;
use App\ShopProduct;
use App\UserProviders;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Console\Input\Input;
use Illuminate\Support\Facades\Session;
//use Laravel\Socialite\Facades\Socialite;


class LoginShopifyController extends Controller
{
    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider(Request $request){
       $this->validate($request,[
           'domain' =>'string|required'
       ]);

       $config = new \SocialiteProviders\Manager\Config(
           env('SHOPIFY_KEY'),
           env('SHOPIFY_SECRET'),
           env('SHOPIFY_REDIRECT'),
           ['subdomain' => $request->get('domain')]
       );

       return Socialite::with('shopify')
           ->setConfig($config)
           ->scopes(['read_products','write_products'])
           ->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback(Request $req){

      // return $req; die;
      $shopify_hmac=env('SHOPIFY_SECRET');
      $code= $req->query->get('code'); 
      $hmac= $req->query->get('hmac'); 
      $shop= $req->query->get('shop');
      $storename=explode('.',$shop);
      $params = $_GET;
      // print_r($params); die;
      $flagPro=0;

      $params = array_diff_key($params, array('hmac' => ''));   // Remove hmac from params
      ksort($params); // Sort params lexographically

      // Compute SHA256 digest
      $computed_hmac = hash_hmac('sha256', http_build_query($params), $shopify_hmac);
      $reqtime= $req->query->get('timestamp');  
      if (hash_equals($hmac, $computed_hmac)) {

          // Set variables for our request
          $query = array(
            "client_id" => env('SHOPIFY_KEY'), // Your API key
            "client_secret" => env('SHOPIFY_SECRET'), // Your app credentials (secret key)
            "code" => $params['code'] // Grab the access key from the URL
          );

          // Generate access token URL
          $access_token_url = "https://" . $params['shop'] . "/admin/oauth/access_token";
          // Configure curl client and execute request
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($ch, CURLOPT_URL, $access_token_url);
          curl_setopt($ch, CURLOPT_POST, count($query));
          curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
          $result = curl_exec($ch);
          curl_close($ch);

          // Store the access token
          $result = json_decode($result, true);
          //print_r($result);
          $access_token = $result['access_token'];
          //echo $access_token;
          if($access_token)
          {

            $user=User::updateOrCreate([
                'name'=>$shop, 
            ]);
            $user['name'] = $shop;
            $user['domainname']=$shop;
            $user['access_token'] = $access_token;
            $user['is_active'] = 0;
            $user->save();

            $shops_token = $access_token;

            $getThemes = "https://" . $shop . "/admin/themes.json";
            $gt = curl_init();
            curl_setopt($gt, CURLOPT_URL, $getThemes); //Url together with parameters
            curl_setopt($gt, CURLOPT_RETURNTRANSFER, 1); //Return data instead printing directly in Browser
            curl_setopt($gt, CURLOPT_CONNECTTIMEOUT , 7); //Timeout after 7 seconds
            curl_setopt($gt, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
            curl_setopt($gt, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: '.$shops_token));
            $cURLresult = curl_exec($gt);
            $gtResult = json_decode($cURLresult, true);
            $currentThemeId="";
            foreach ($gtResult as $themes) {
              for($i=0; $i<count($themes);$i++)
              {
                if($themes[$i]["role"]=="main")
                {
                  $currentThemeId = $themes[$i]["id"];
                } //if
              }  // for 
            } // foreach
              //WEFEWG$currentThemeId; // Current Theme ID
              $flag=$this->bqFuncton($shop,$shops_token,$currentThemeId);

              if($flag==1){
                $flagPro = $this->bottomryAddProduct($shop,$shops_token);
              }

              $this ->createWebhookOrder($shop,$shops_token);


            session(['access_token'=>$access_token]);
            return view('/redirectme',['shop'=>$params["shop"]]);            
            // return redirect('https://'.$params["shop"].'/admin/apps/quantity-breakdown');
            // header('location:');
          }
 
      } else {
        echo " NOT VALIDATED – Someone is trying to be shady!";
      }
    }

    
        // Curl Hit for Add Required Sections 1
        public function bqFuncton($shop,$shops_token,$currentThemeId){
          $sectionData = file_get_contents("https://phpstack-102119-1018622.cloudwaysapps.com/inject.txt");
            $sectionAdd = (object)[
              "asset" => (object)[
                "key" => "sections/qualtry.liquid",
                "value" => $sectionData
              ]
            ];
            $sectionAddUrl = "https://" . $shop . "/admin/themes/".$currentThemeId."/assets.json";
            $saCurl = curl_init($sectionAddUrl);
                    curl_setopt($saCurl, CURLOPT_HEADER, FALSE);
                    curl_setopt($saCurl, CURLOPT_ENCODING, 'gzip');
                    curl_setopt($saCurl, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($saCurl, CURLOPT_FOLLOWLOCATION, TRUE);
                    curl_setopt($saCurl, CURLOPT_MAXREDIRS, 3);
                    curl_setopt($saCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
                    curl_setopt($saCurl, CURLOPT_USERAGENT, 'My New Shopify App v.1');
                    curl_setopt($saCurl, CURLOPT_CONNECTTIMEOUT, 30);
                    curl_setopt($saCurl, CURLOPT_TIMEOUT, 30);
                    curl_setopt($saCurl, CURLOPT_CUSTOMREQUEST, 'PUT');
                    // Setup headers
                    $request_headersbxgy[] = "X-Shopify-Access-Token: " . $shops_token;
                    $request_headersbxgy[] = 'Content-Type: application/json';

                    curl_setopt($saCurl, CURLOPT_HTTPHEADER, $request_headersbxgy);
                    $querybxgy = json_encode($sectionAdd);
                    curl_setopt ($saCurl, CURLOPT_POSTFIELDS, $querybxgy);
                    $saCurlResult = curl_exec($saCurl);
                    $GetsaCurlResult = json_decode($saCurlResult);
              curl_close($saCurl);      
              
              if($GetsaCurlResult){
                return 1;
              }else{
                return 0;
              }
        }    
  
        // Curl Hit for Add New Product 
        public function bottomryAddProduct($shop,$shops_token){

          $urlShopify = 'https://'.$shop.'/admin/products.json'; // create customer

          $param = (object)[             
                    "product" => (object)[
                        "title" => "Bottomry Shipping Insurance",
                        "body_html" => "<strong>Shipping Insurance by Bottomry</strong>",
                        "vendor" => "Bottomry",
                        "product_type" => "Shipping Insurance",
                        "tags" => "Shipping",
                        "variants" => (object)[
                            "option1" => "0.98",
                            "price" => 0.98,
                            "position" => 1,
                            "sku" => "BOTTOMRY17",
                            "inventory_policy" => "continue",
                            "inventory_management" => null,
                            "grams" => 0,
                            "taxable" => false,
                            "weight" => 0,
                            "weight_unit" => "kg",
                            "inventory_quantity" => 1000000,
                            "old_inventory_quantity" => 0,
                            "requires_shipping" => false,
                            "metafields" => (object)[
                                "key" => "new",
                                "value" => "newvalue",
                                "value_type" => "string",
                                "namespace" => "global"
                            ],
                            "images" => (object)[
                                "src" => "https://phpstack-102119-1018622.cloudwaysapps.com/public/images/shippingins.jpg"
                            ],
                            "presentment_prices" => (object)[
                              "price" => (object)[
                                "currency_code" => "USD",
                                "amount" => 0.98
                              ],
                              "compare_at_price" => null
                            ],
                            "price" => (object)[
                              "currency_code" => "USD",
                              "amount" => "0.98"
                            ],
                            "compare_at_price" => null
                        ]
                    ]               
                ];
            $params1 = json_encode($param);    
            $ProBottomry = curl_init($urlShopify); 
                    curl_setopt($ProBottomry, CURLOPT_HEADER, FALSE);
                    curl_setopt($ProBottomry, CURLOPT_ENCODING, 'gzip'); 
                    curl_setopt($ProBottomry, CURLOPT_RETURNTRANSFER, TRUE); 
                    curl_setopt($ProBottomry, CURLOPT_FOLLOWLOCATION, TRUE); 
                    curl_setopt($ProBottomry, CURLOPT_MAXREDIRS, 3); 
                    curl_setopt($ProBottomry, CURLOPT_SSL_VERIFYPEER, FALSE); 
                    curl_setopt($ProBottomry, CURLOPT_USERAGENT, 'My New Shopify App v.1'); 
                    curl_setopt($ProBottomry, CURLOPT_CONNECTTIMEOUT, 30); 
                    curl_setopt($ProBottomry, CURLOPT_TIMEOUT, 30); 
                    curl_setopt($ProBottomry, CURLOPT_CUSTOMREQUEST, 'POST'); 
                    // Setup headers 
                    $request_headersnp[] = "X-Shopify-Access-Token: " . $shops_token; 
                    $request_headersnp[] = "Content-Type: application/json"; 
                    $request_headersnp[] = "cache-control: no-cache"; 
                    $request_headersnp[] = "X-Shopify-Api-Features: include-presentment-prices"; 

                    curl_setopt($ProBottomry, CURLOPT_HTTPHEADER, $request_headersnp); 
                    curl_setopt ($ProBottomry, CURLOPT_POSTFIELDS, $params1); 
                    $resultPro = curl_exec($ProBottomry);
                    $resultPro = json_decode($resultPro);
                    $proID=$resultPro->product->id;
                  //   print_r($resultPro); die;
                  //   return;
                  // echo $proID;  
            $shopPro = new ShopProduct;
            $shopPro['shopName']  =  $shop;
            $shopPro['shopToken'] =  $shops_token;
            $shopPro['productID'] =  $proID;
            $shopPro->save();
            
            $lid = $shopPro->id;
            if($lid){
              return 1;
            }else{
              return 0;
            }
        }

        // Function to create web-hook
        public function createWebhookOrder($shop,$shops_token){
          $url = 'https://'.$shop.'/admin/webhooks.json';
          $webParams = (object)[
                  "webhook"   => (object)[
                    "topic"   => "orders/paid",
                    "address" => "https://phpstack-102119-1018622.cloudwaysapps.com/orderpaid",
                    "format"  => "json"
                  ] 
          ];
          $webParams1 = json_encode($webParams);
            $webHookCreation = curl_init();
            curl_setopt($webHookCreation, CURLOPT_URL, $url);
            curl_setopt($webHookCreation, CURLOPT_POST, 1);
            // Tell curl that this is the body of the POST
            curl_setopt($webHookCreation, CURLOPT_POSTFIELDS, $webParams1);
            curl_setopt($webHookCreation, CURLOPT_HEADER, false);
            $request_headersnp[] = "X-Shopify-Access-Token: " . $shops_token; 
            $request_headersnp[] = "Content-Type: application/json"; 
            $request_headersnp[] = "Accept: application/json";
            curl_setopt($webHookCreation, CURLOPT_HTTPHEADER, $request_headersnp);

            curl_setopt($webHookCreation, CURLOPT_RETURNTRANSFER, true);
            // if(preg_match("/^(https)/",$url)) curl_setopt($webHookCreation,CURLOPT_SSL_VERIFYPEER,false);
            curl_setopt($webHookCreation,CURLOPT_SSL_VERIFYPEER,false);
            $response = curl_exec($webHookCreation);
            curl_close($webHookCreation);
            $resultPro = json_decode($response);
           // print_r($resultPro); die;
           // return;
        }
}

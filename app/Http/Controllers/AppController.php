<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Campaign;
use App\Useridentifire;
use App\Pricerule;
use App\BQSetting;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Akaunting\Money\Currency;
use Akaunting\Money\Money;
header('Access-Control-Allow-Origin: *');
// date_default_timezone_set('America/New_York');
class AppController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $req)
    {
      $shop_data=[];
      $shopify_hmac=env('SHOPIFY_SECRET');
      $code= $req->query->get('code');
      $hmac= $req->query->get('hmac');
      $shop= $req->query->get('shop');
      $storename=explode('.',$shop);
      $params = $_GET;
      $shop_data["shop"] = $shop;
        // print_r($params); die;
        $params = array_diff_key($params, array('hmac' => ''));   // Remove hmac from params
        ksort($params); // Sort params lexographically
        // Compute SHA256 digest
        $computed_hmac = hash_hmac('sha256', http_build_query($params), $shopify_hmac);
        $reqtime= $req->query->get('timestamp');
        if (hash_equals($hmac, $computed_hmac)) {
          $getuser=User::where('domainname', $shop)->first();
          $is_user = $getuser->count();
          if($is_user>0){
              $shops_token =$getuser->access_token;
              $shop_data['shop_token'] = md5($shops_token);

            // echo $shops_token; die;

            
            $access_token_url = "https://" . $shop . "/admin/price_rules.json";
            $ch = curl_init();
              curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
              curl_setopt($ch, CURLOPT_URL, $access_token_url);
              curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: '.$shops_token));
              $result = curl_exec($ch);
              curl_close($ch);

              // Store the access token
              $result = json_decode($result, true);
              // echo "<pre>";
              // print_r($result["price_rules"]); die;

              ///   *****************   cURL for Products Fetching    *****************   ///
              $url = "https://" . $shop . "/admin/products.json";
              $pch = curl_init();
              curl_setopt($pch, CURLOPT_URL, $url); //Url together with parameters
              curl_setopt($pch, CURLOPT_RETURNTRANSFER, 1); //Return data instead printing directly in Browser
              curl_setopt($pch, CURLOPT_CONNECTTIMEOUT , 7); //Timeout after 7 seconds
              curl_setopt($pch, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
              curl_setopt($pch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: '.$shops_token));
              $cURLresult = curl_exec($pch);
              $proResult = json_decode($cURLresult, true);


              $campaigns= Campaign::where('shop_address',$shop)->get();
              $campaigns = json_decode($campaigns, true);
             // return $campaigns; die;
              return view('rules',['data'=>$shop_data,'rules'=>$result["price_rules"],'products'=>$proResult["products"],'campaigns'=>$campaigns]);
          }
      }
    // }else{ return view('welcome');}
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
    */
    public function create(Request $req)
    {
      $this->validate(request(), [
          'campaignname'         => 'required',
          'discount_product_qty' => 'required',
          'discount_title'       => 'required',
          'discount_value'       => 'required',
          'discount_type'        => 'required',
      ]);
      $cur_date=date('Y-m-d',strtotime("-3 days")).'T'.date("H:i:s").'.'.date("c").'Z';
      $curlStatus=0; $clineno=0; 

      $campaign=Campaign::where('id', $req->campaignname)->first();
      $clineno = $campaign["cline_no"]+1; 
      $getuser=User::where('domainname', $req->shop)->first();
      $is_user = $getuser->count();
      if($is_user>0){
        if($req->discount_value==0){
          $req->discount_type="fixed_amount";
        }
        $prule = new Pricerule;
          $prule['shop_name']             = $req->shop;
          $prule['campaign_id']           = $req->campaignname;
          $prule['line_no']               = $clineno;
          $prule['rule_type']             = $req->discount_type;
          $prule['rule_title']            = addslashes($req->discount_title);
          $prule['rule_value']            = $req->discount_value;
          $prule['rule_qty']              = $req->discount_product_qty;
          $prule->save();
            if($prule->save()){
              $curlStatus=$curlStatus+1;
            }
        if($curlStatus>0){
            $campaign["campaign_count"] = $campaign["campaign_count"] +1;
            $campaign["cline_no"] = $campaign["cline_no"] +1;
            $campaign->save();
        }
        return back()->with('success','Price Rule is successfully added');      
      }else{
        return back()->with('error','User Not Found!');
      }
    }  

            
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($shop, $campaign,$id)
    {
      //return $shop." - ". $id;
      $priResult["price_rule"]      = "";
      $priResultbxgy['price_rule']  = "";
      $shop_data["shop"]            = $shop;
      $shop_data["campaignid"]      = $campaign;
      //$storename=explode('.',$shop);
      $getuser = User::where('domainname', $shop)->first();
      $is_user = $getuser->count();
      if($is_user>0){
        $shops_token = $getuser->access_token;
        $productid = Campaign::where('shop_address',$shop)->where('id',$campaign)->first();
        $getproductid = json_decode($productid, true);
        $ruleData= Pricerule::where('shop_name',$shop)
          ->where('campaign_id',$campaign)
          ->where('line_no',$id)
          ->get();
        $result = json_decode($ruleData, true);
          if($result[0]["rule_value"] == "") {
            $rulev = '';
          }else{
            $rulev = $result[0]["rule_value"];
          }
          $shop_data["productid"]     = $getproductid["product_id"];
          $shop_data["rule_title"]    = $result[0]["rule_title"];
          $shop_data["normallineno"]  = $result[0]["line_no"];
          $shop_data["ruleQty"]       = $result[0]["rule_qty"];
          $shop_data["ruleType"]      = $result[0]["rule_type"];
          $shop_data["ruleValue"]     = $rulev;
          
            ///   *****************   cURL for Products Fetching    *****************   ///
            $purl = "https://" . $shop . "/admin/products.json";
            $pch = curl_init();
            curl_setopt($pch, CURLOPT_URL, $purl); //Url together with parameters
            curl_setopt($pch, CURLOPT_RETURNTRANSFER, 1); //Return data instead printing directly in Browser
            curl_setopt($pch, CURLOPT_CONNECTTIMEOUT , 7); //Timeout after 7 seconds
            curl_setopt($pch, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
            curl_setopt($pch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: '.$shops_token));
            $cURLresult = curl_exec($pch);
            $proResult = json_decode($cURLresult, true);
            // Get All Campaign Data
            $campaigns= Campaign::where('shop_address',$shop)->get();
            $campaigns = json_decode($campaigns, true);
            // return $shop_data;
            return view('edit',['data'=>$shop_data,'products'=>$proResult["products"],'campaigns'=>$campaigns]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $req)
    {
      // return $req; die;
      $this->validate(request(), [
        'campaignname'         => 'required',
        'discount_product_qty' => 'required',
        'discount_title'       => 'required',
        'discount_value'       => 'required',
        'discount_type'        => 'required',
      ]);
      $cur_date=date('Y-m-d',strtotime("-3 days")).'T'.date("H:i:s").'.'.date("c").'Z';

      $curlStatus=0; $clineno=0; 
      $campaign=Campaign::where('id', $req->campaignname)->first();
      $clineno = $campaign["cline_no"]+1;

      $getuser=User::where('domainname', $req->shop)->first();
      $is_user = $getuser->count();
      if($is_user>0){
        if($req->discount_value==0){
          $req->discount_type="fixed_amount";
        }
        $ruleExits= Pricerule::where('campaign_id',$req->campaignname)
          ->where('shop_name',$req->shop)
          ->where('line_no',$req->normallineno)
          ->first();

          $ruleExits['shop_name']      = $req->shop;
          $ruleExits['campaign_id']    = $req->campaignname;
          $ruleExits['line_no']        = $req->normallineno;
          $ruleExits['rule_type']      = $req->discount_type;
          $ruleExits['rule_title']     = addslashes($req->discount_title);
          $ruleExits['rule_value']     = $req->discount_value;
          $ruleExits['rule_qty']       = $req->discount_product_qty;
          $ruleExits->save();
          return back()->with('success','Price Rule Is Successfully Updated');      
      }else{
        return back()->with('error','User Not Found!');
      }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($shop,$campaign,$id)
    {
        $isrules=0;
        //Generate access token URL
        $getuser=User::where('domainname', $shop)->first();
        $is_user = $getuser->count();
        if($is_user>0){
        //$shops_token =$getuser->access_token;
          $getpriceRulesDel=Pricerule:: where('shop_name',$shop)
            ->where('campaign_id',$campaign)
            ->where('line_no',$id)
            ->delete();
          $camp = Campaign::where("id",$campaign)->first();
          $newvalueCount = $camp["campaign_count"]-1;
          if($newvalueCount>0){
            $camp["campaign_count"] = $newvalueCount;
          }else{
            $camp["campaign_count"] = 0;
          }
          $camp->save();
          
          if($camp->save()){
            $isrules=1;
          }
          if($isrules==1){
              return back()->with('success','Price Rule is successfully Deleted');
          }
       }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function searchProduct($shop,$searchkeyword)
    {
      //echo "Shop: " .$shop. "<br> searchkeyword: ". $searchkeyword; die;
          // Generate access token URL
      $storename=explode('.',$shop);
      $getuser=User::where('domainname', $shop)->first();
      $is_user = $getuser->count();
      if($is_user>0){
          $shops_token =$getuser->access_token;
          $access_token_url = "https://" . $shop . "/admin/products.json";
          // echo $access_token_url; die;
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $access_token_url); //Url together with parameters
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Return data instead printing directly in Browser
          curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 7); //Timeout after 7 seconds
          curl_setopt($ch, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
          curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: '.$shops_token));
          $cURLresult = curl_exec($ch);
          $proResult = json_decode($cURLresult, true);
          if(curl_exec($ch) === false)
          {
              echo curl_error($ch);
          }else{
              return view('ProductSearchResult',['products'=>$proResult["products"]]);
          }
          curl_close($ch);
      }
    }

    /**
     * Product Selection .
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function Productsselection(Request $req)
    {
      $html="";
      $exp=explode("#7819",$req->data);
      $shop= explode(".",$exp[0]);
      $data1 = $exp[1];
      //var_dump($data1); die;
      $getuser=User::where('name', $exp[0])->first();
      $is_user = $getuser->count();
      if($is_user>0)
      {
          $shops_token =$getuser->access_token;

        $campaign = Campaign::where('id',$data1)->first();
        $campaign_data = json_decode($campaign,true);
        //var_dump($campaign_data);
        //echo $campaign_data["product_id"];

      ///   *****************   cURL for Products Fetching    *****************   ///
          $purl = "https://" . $exp[0] . "/admin/products/".$campaign_data["product_id"].".json";
          // echo $purl;
          $pch = curl_init();
          curl_setopt($pch, CURLOPT_URL, $purl); //Url together with parameters
          curl_setopt($pch, CURLOPT_RETURNTRANSFER, 1); //Return data instead printing directly in Browser
          curl_setopt($pch, CURLOPT_CONNECTTIMEOUT , 7); //Timeout after 7 seconds
          curl_setopt($pch, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
          curl_setopt($pch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: '.$shops_token));
          $cURLresult = curl_exec($pch);
          $proResult = json_decode($cURLresult, true);
          // echo "<pre>";
          // print_r($proResult);
          // echo "</pre>"; die; 
          //echo count($proResult["product"]["options"]); die;//?>
          <table class='table table-responsive producttable'>
            <tr>
              <td width='8%'><img style='width: 100%' src='<?php echo $proResult["product"]["image"]["src"]; ?>'></td>
              <td class="proTitles"   width='92%'><?php echo $proResult["product"]["title"];?></td>
              <input type="hidden" name="selectedp[]" value='<?php echo $campaign_data["product_id"];?>'>
              <input type="hidden" name="vopt" value='<?php echo count($proResult["product"]["options"]);?>'>
            </tr>
          </table>
    <?php  }
    }



    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function showrules(Request $req)
    {
      $shop_data=[];
      $shopify_hmac=env('SHOPIFY_SECRET');
      $code= $req->query->get('code');
      $hmac= $req->query->get('hmac');
      $shop= $req->query->get('shop');
      $storename=explode('.',$shop);
      $params = $_GET;
      $shop_data["shop"] = $shop;
        // print_r($params); die;
        $params = array_diff_key($params, array('hmac' => ''));   // Remove hmac from params
        ksort($params); // Sort params lexographically
        // Compute SHA256 digest
        $computed_hmac = hash_hmac('sha256', http_build_query($params), $shopify_hmac);
        $reqtime= $req->query->get('timestamp');
        if (hash_equals($hmac, $computed_hmac)) {
          $getuser=User::where('domainname', $shop)->first();
          $is_user = $getuser->count();
          if($is_user>0){
              $shops_token =$getuser->access_token;

              $getpriceRules = Pricerule::where('shop_name',$shop)
                                ->join('campaigns', 'price_rules.campaign_id', '=','campaigns.id')
                                ->get();
                                // ->groupBy('campaign_id');
              $result = json_decode($getpriceRules, true);
               // return view('viewrules',['data'=>$shop_data,'rules'=>$result["price_rules"],'products'=>$proResult["products"]]);
              return view('viewrules',['data'=>$shop_data,'rules'=>$result]);
          }
      }
    }

    public function frontend(Request $req)
    {
      $query = $req->all();
      

      if($query["type"]=="getProduct"){
        header('Content-type:application/json');
      //echo json_encode($query);die;
        $pid=$query["type"]; $query["shop"];
        $campaign=Campaign::where('product_id',$query["productId"])->where('status',1)->first();
        $prules=Pricerule::where('campaign_id',$campaign["id"])->join('campaigns', 'price_rules.campaign_id', '=','campaigns.id')->get();
        if(isset($query["productId")){
          
        }

        $campaign=Pricerule::where('shop_name',$query["shop"])->where('status',1)->get();
        $shopData=BQSetting::where('shop', $query["shop"])->first();

        $productArr = [];
          foreach ($campaign as $key => $value) {
            $product = explode("##", $value['selectedProducts']);             
            if(in_array($query["productId"],$product)){  
              if($value['rule_type'] == "fixed_amount"){
                $value['currency_type'] = $shopData['currency'];               
              }
              $value['save_text'] = $shopData['text']; 
              $value['DiscountMessage'] = $shopData['DiscountMessage'];
              array_push($productArr, $value);
            }
          }
          return $productArr;
        // header('Content-type:application/json');
 /*       if(isset($query["productId"])){
          $productArr = [];
          foreach ($campaign as $key => $value) {
            $product = explode("##", $value['selectedProducts']);             
            if(in_array($query["productId"],$product)){  
              if($value['rule_type'] == "fixed_amount"){
                $value['currency_type'] = $shopData['currency'];               
              }
              $value['save_text'] = $shopData['text']; 
              $value['DiscountMessage'] = $shopData['DiscountMessage'];
              array_push($productArr, $value);
            }
          }
          return $productArr;
        }else if(isset($query['collectionID'])){
          $collectionArr = [];
          foreach ($campaign as $key => $value) {
            $product = explode("##", $value['slectedoCollections']);             
            if(in_array($query["collectionID"],$product)){  
              if($value['rule_type'] == "fixed_amount"){
                $value['currency_type'] = $shopData['currency'];                
              }
              $value['save_text'] = $shopData['text']; 
              $value['DiscountMessage'] = $shopData['DiscountMessage']; 
              array_push($collectionArr, $value);
            }
          }
          return $collectionArr; 
        }else{
          return "Wrong Request";
        }      */
        
      }elseif($query["type"]=="getRuleId"){
          $query1= $query["shopCurrency"];
          // echo json_encode($query);die;
          $campaign=Pricerule::where('compaign_id',$query["compaignId"])->where('status',1)->where('line_no',$query['lineNo'])->where('shop_name',$query['shop'])->get();
         
          $uid=Useridentifire::updateOrCreate([
                'uuid'        => $query["uuu"],
                'campaign_id' => $query["compaignId"],
            ]);
        	$uid['shop_address']  = $query["shop"];
        	$uid['uuid']  		    = $query["uuu"];
          $uid['product_id']    = $query["productId"];
        	$uid['campaign_id']   = $query["compaignId"];
        	$uid['lineno']  	    = $query["lineNo"];
          $uid->save();
          
          $prules=Pricerule::where('compaign_id',$query["compaignId"])->where('line_no',$query["lineNo"])->where('shop_name',$query["shop"])->get();
          $getuser=User::where('name', $query["shop"])->first();
          $is_user = $getuser->count();
          if($is_user>0)
          {
            $shops_token =$getuser->access_token;
            ////   *****************   cURL for Products Fetching   *****************   ///
            $purl = "https://" . $query["shop"] . "/admin/products/".$query["productId"].".json";
            $pch = curl_init();
            curl_setopt($pch, CURLOPT_URL, $purl); //Url together with parameters
            curl_setopt($pch, CURLOPT_RETURNTRANSFER, 1); //Return data instead printing directly in Browser
            curl_setopt($pch, CURLOPT_CONNECTTIMEOUT , 7); //Timeout after 7 seconds
            curl_setopt($pch, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
            curl_setopt($pch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: '.$shops_token));
            $cURLresult = curl_exec($pch);
            $proResult = json_decode($cURLresult, true);
            header('Content-type:application/json');
      
            $newPrice = 0;$oldPrice = (float) $proResult['product']['variants'][0]['price']; //(int)$query["productPrice"];
            $displayoldPrice = (float) $proResult['product']['variants'][0]['price'] * (int)$prules[0]["rule_qty"];

            $comparePrice = $proResult['product']['variants'][0]['compare_at_price'] * (int)$prules[0]["rule_qty"];
            $oval = Money::$query1($displayoldPrice, true); //number_format((float)$newPrice, 2, '.', '');
            $oldvalue1 = (string) $oval;

            if($comparePrice>0){
              $cval = Money::$query1($comparePrice, true); //number_format((float)$newPrice, 2, '.', '');
              $cvalue1 = (string) $cval;
            }else{
              $cvalue1=0;
            }

            $data=[]; $nval=0; $newPrice=0.00;
              if($prules[0]["rule_type"] == "fixed_amount"){
                  $newPricesub          = ((float)$oldPrice * (int)$prules[0]["rule_qty"]) - (float)$prules[0]["rule_value"];
                  $newPrice             = $newPricesub;
                  $data['oldValue']     = $oldvalue1;
                  $data['compare_amt']  = $cvalue1;
                  $nval                 = Money::$query1($newPrice, true); //number_format((float)$newPrice, 2, '.', '');
                  $data1                = (string) $nval;
                  $data['newValue']     = $data1; //$prefix . $newPrice . "";
                  $data['ruleType']     = "fixed_amount";
                  $data['ruleQty']      = $prules[0]["rule_qty"];
                  $data['rType']        = 2;
                }else{
                  $newPricesub          = ((float)$oldPrice * (int)$prules[0]["rule_qty"]) - ((((float)$oldPrice * (int)$prules[0]["rule_qty"]) * (float)$prules[0]["rule_value"])/100);
                  $newPrice             = $newPricesub;// * (int)$prules[0]["variant_qty"];
                  $data['oldValue']     = $oldvalue1;
                  $data['compare_amt']  = $cvalue1;
                  $nval                 = Money::$query1($newPrice, true);
                  $data1                =(string) $nval;
                  $data['newValue']     = $data1;
                  $data['ruleType']     = "percentage";
                  $data['ruleQty']      = $prules[0]["rule_qty"];
                  $data['rType']        = 1;
                }
            header('Content-type:application/json');
            echo json_encode($data);die;
        	}
    	}elseif($query["type"]=="checkDiscount"){
        $ident = $query["dataIdentifier"];
        //echo $query["cartsubtotal"]/100; die; 
        $getuser=User::where('name', $query["shop"])->first();
        $is_user = $getuser->count();
          $data=[];
          $shopData=BQSetting::where('shop', $query["shop"])->first();
          $data['cart_text'] = $shopData['cartText'];
          $data['currency_name'] = $shopData['currency'];
          if($is_user>0 && isset($query["itemsArray"]))
          {
            $shops_token =$getuser->access_token;   
            header('Content-type:application/json');
            $discountamount=0;  
            $abc = []; $entitled_product_ids=[]; $orderQty=0;
              foreach($query["itemsArray"] as $value) {
                array_push($entitled_product_ids, $value['product_id']);
                $orderQty = $orderQty + $value['quantity'];
                $uid=db::select("SELECT p.*,uid.product_id,uid.lineno,uid.uuid, uid.shop_address FROM `Useridentifires` as uid INNER JOIN price_rules as p ON uid.campaign_id = p.compaign_id AND p.rule_qty <= $value[quantity] WHERE uid.uuid='$ident' AND uid.shop_address='$query[shop]' AND uid.product_id=$value[product_id] ORDER BY p.id DESC");
                array_push($abc, $uid);
              } 
              
              for($x=0;$x<count($abc); $x++){
                // print_r($abc); die;
                
                if(count($abc[$x])>0){
                  //   *****************   cURL for Products Fetching    *****************   ///
                  $urlpp = "https://" . $query["shop"] . "/admin/products/".$abc[$x][0]->product_id.".json";
                  $pchpp = curl_init();    
                  curl_setopt($pchpp, CURLOPT_URL, $urlpp); //Url together with parameters
                  curl_setopt($pchpp, CURLOPT_RETURNTRANSFER, 1); //Return data instead printing directly in Browser
                  curl_setopt($pchpp, CURLOPT_CONNECTTIMEOUT , 7); //Timeout after 7 seconds
                  curl_setopt($pchpp, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
                  curl_setopt($pchpp, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: '.$shops_token));
                  $cURLresult = curl_exec($pchpp);
                  $proRR = json_decode($cURLresult, true);
                    if(($abc[$x][0]->rule_type) == "fixed_amount"){
                      $discountamount = $discountamount + $abc[$x][0]->rule_value;
                    }else{
                      $discountamount = $discountamount + ($abc[$x][0]->rule_qty * (($proRR["product"]["variants"][0]["price"] * $abc[$x][0]->rule_value)/100));
                    }  
                }
              } // For Loop Ends here
              //echo "session is". $req->session()->get('oldpr');

              // if(isset($req->session()->get('oldpr'))){
              //   // echo "in if"; die;
              //   $DS_token_url = "https://" . $req->shop . "/admin/price_rules/".$req->session()->get('oldpr').".json";
              //   // echo $access_token_url; die;
              //   $chs = curl_init();
              //   curl_setopt($chs, CURLOPT_RETURNTRANSFER, 1);
              //   curl_setopt($chs, CURLOPT_URL, $DS_token_url);
              //   curl_setopt($chs, CURLOPT_CUSTOMREQUEST, "DELETE");
              //   curl_setopt($chs, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token: '.$shops_token));
              //   $result15 = curl_exec($chs);
              //   curl_close($chs); 
              // }


              // Loop to delete un used codes
              $url1 = "https://" . $query["shop"] . "/admin/price_rules.json";
              $prch1 = curl_init();
              curl_setopt($prch1, CURLOPT_URL, $url1); //Url together with parameters
              curl_setopt($prch1, CURLOPT_RETURNTRANSFER, 1); //Return data instead printing directly in Browser
              curl_setopt($prch1, CURLOPT_CONNECTTIMEOUT , 7); //Timeout after 7 seconds
              curl_setopt($prch1, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
              curl_setopt($prch1, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: '.$shops_token));
              $cURLresult1 = curl_exec($prch1);
              $priResultbxgy = json_decode($cURLresult1, true);
              $x=0;
              foreach ($priResultbxgy["price_rules"] as $pvalue) {
                $pcur_date=date('Y-m-d',strtotime("-1 days")).'T'.date("H:i:s");
                $pcur_date1=date('Y-m-d').'T'.date("H:i:s",strtotime('-1 hour'));

                $currentDate  = date("U",strtotime($pcur_date));
                $EndDate      = date("U",strtotime($pvalue["ends_at"]));
                //echo $pcur_date."<br>"; 
                //echo $pcur_date1."<br>";
                
                //echo "Current Date ". date("U",strtotime($pcur_date))."<br>"; 
                //echo "Pricerule Date ". date("U",strtotime($pvalue["ends_at"]))."<br>"; //die;
                //echo "-------------";

                if($EndDate <= $currentDate){
                  //echo $pvalue["title"]."<br>";
                  $Daccess_token_url = "https://" . $req->shop . "/admin/price_rules/".$pvalue["id"].".json";
                    // echo $access_token_url; die;
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_URL, $Daccess_token_url);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token: '.$shops_token));
                    $result = curl_exec($ch);
                    curl_close($ch);
                    //$result = json_decode($result, true);
                }
              }

              if($discountamount>0){
                $cur_date=date('Y-m-d',strtotime("-1 days")).'T'.date("H:i:s").'.'.date("c").'Z';
                $cur_date1=date('Y-m-d',strtotime("+14 days")).'T'.date("H:i:s",strtotime("+2 minutes")).'.'.date("c").'Z';

                $newrule = (object)[
                  "price_rule" => (object)[
                    "title"               => "DISCOUNT".rand(1000000,99999999),
                    "target_type"         => "line_item",
                    "target_selection"    => "entitled",
                    "allocation_method"   => "across",
                    "value_type"          => "fixed_amount",
                    "value"               => "-".$discountamount,
                    "customer_selection"  => "all",
                    "once_per_customer"   => true,
                    "usage_limit"         => 1,
                    "entitled_product_ids"=> $entitled_product_ids,
                    "prerequisite_quantity_range" => (object) [
                      "greater_than_or_equal_to"  => $orderQty
                                              ],
                    "starts_at"           => $cur_date,
                    "ends_at"             => $cur_date1
                  ]
                ];
                $dd3=json_encode($newrule);
                // return $dd3; die;
                $access_token_url = "https://" . $query["shop"] . "/admin/price_rules.json";
                $discode = curl_init($access_token_url);
                  curl_setopt($discode, CURLOPT_HEADER, FALSE);
                  curl_setopt($discode, CURLOPT_ENCODING, 'gzip');
                  curl_setopt($discode, CURLOPT_RETURNTRANSFER, TRUE);
                  curl_setopt($discode, CURLOPT_FOLLOWLOCATION, TRUE);
                  curl_setopt($discode, CURLOPT_MAXREDIRS, 3);
                  curl_setopt($discode, CURLOPT_SSL_VERIFYPEER, FALSE);
                  curl_setopt($discode, CURLOPT_USERAGENT, 'My New Shopify App v.1');
                  curl_setopt($discode, CURLOPT_CONNECTTIMEOUT, 30);
                  curl_setopt($discode, CURLOPT_TIMEOUT, 30);
                  curl_setopt($discode, CURLOPT_CUSTOMREQUEST, 'POST');
                  // Setup headers
                  $request_headers[] = "X-Shopify-Access-Token: " . $shops_token;
                  $request_headers[] = 'Content-Type: application/json';

                  curl_setopt($discode, CURLOPT_HTTPHEADER, $request_headers);
                  $queryRule = json_encode($newrule);
                  curl_setopt ($discode, CURLOPT_POSTFIELDS, $queryRule);
                  $resultrule = curl_exec($discode);
                  $resultrulea = json_decode($resultrule);
                  //print_r($resultrulea); die;
                  $bxgy=$resultrulea->price_rule->id;
                  //echo $resultrulea->price_rule->id; die;
                  // Curl For Discount Code
                  $requrl="https://".env('SHOPIFY_KEY').":".$shops_token."@".$query["shop"];
                  $discountURL= $requrl. "/admin/price_rules/".$resultrulea->price_rule->id."/discount_codes.json";
                  //echo $discountURL; die;
                  $discountcode=(object)[
                          "discount_code"=> (object)[
                            "code" => $resultrulea->price_rule->title
                          ]
                        ];
                  $discountcodequery = json_encode($discountcode);
                  $dcodef =  curl_init($discountURL);
                    curl_setopt($dcodef, CURLOPT_HEADER, FALSE);
                    curl_setopt($dcodef, CURLOPT_ENCODING, 'gzip');
                    curl_setopt($dcodef, CURLOPT_RETURNTRANSFER, TRUE);
                    curl_setopt($dcodef, CURLOPT_FOLLOWLOCATION, TRUE);
                    curl_setopt($dcodef, CURLOPT_MAXREDIRS, 3);
                    curl_setopt($dcodef, CURLOPT_SSL_VERIFYPEER, FALSE);
                    curl_setopt($dcodef, CURLOPT_USERAGENT, 'My New Shopify App v.1');
                    curl_setopt($dcodef, CURLOPT_CONNECTTIMEOUT, 30);
                    curl_setopt($dcodef, CURLOPT_TIMEOUT, 30);
                    curl_setopt($dcodef, CURLOPT_CUSTOMREQUEST, 'POST');
                    $request_headers1[]  = "Content-Type: application/json";
                    $request_headers1[]  = "Host:". $query["shop"];
                    curl_setopt($dcodef, CURLOPT_HTTPHEADER, $request_headers1);
                    curl_setopt($dcodef, CURLOPT_POSTFIELDS, $discountcodequery);
                    $result1code = curl_exec($dcodef);
                    $result1code = json_decode($result1code, true);
                    //$disbxgy=$result1code["discount_code"]["id"]; 
                    $data["discount_code"]  = $result1code["discount_code"]["code"];
                    $req->session()->put('oldpr', $resultrulea->price_rule->id);
              }else{
                  //$req->session()->put('oldpr', "");
                // Curl For Price rule... 
                  // $cur_date=date('Y-m-d',strtotime("-1 days")).'T'.date("H:i:s").'.'.date("c").'Z';
                  // $cur_date1=date('Y-m-d').'T'.date("H:i:s",strtotime("+15 minutes")).'.'.date("c").'Z';
                //  echo $cur_date."<br>";
                // $currentShopTime = $query["currentShopTime"];
                // $cst=explode(' ',$currentShopTime);
                // $endTime = strtotime("+15 minutes", strtotime($cst[1]));
                // //print_r($cst);
                // echo $endTime ."<br>"; //die;
                // // Curl For Price rule...    
                // $cur_date=$cst[0].'T'.$cst[1].'.'.date("c").'Z';  
                // $cur_date1=$cst[0].'T'.date('H:i:s', $endTime).'.'.date("c").'Z';  
                //  echo date($cur_date);// $cur_date ." ----------- " . $cur_date1;
                //  die;
                // $newrule = (object)[
                //   "price_rule" => (object)[
                //     "title"               => "DISCOUNT".rand(1000000,99999999),
                //     "target_type"         => "line_item",
                //     "target_selection"    => "all",
                //     "allocation_method"   => "across",
                //     "value_type"          => "fixed_amount",
                //     "value"               => "-0",
                //     "customer_selection"  => "all",
                //     "usage_limit"         => 1,
                //     "starts_at"           => $cur_date,
                //     "ends_at"             => $cur_date1
                //   ]
                // ];
                // $dd3=json_encode($newrule);
                // //return $dd3; die;
                // $access_token_url = "https://" . $query["shop"] . "/admin/price_rules.json";
                // $discode = curl_init($access_token_url);
                //   curl_setopt($discode, CURLOPT_HEADER, FALSE);
                //   curl_setopt($discode, CURLOPT_ENCODING, 'gzip');
                //   curl_setopt($discode, CURLOPT_RETURNTRANSFER, TRUE);
                //   curl_setopt($discode, CURLOPT_FOLLOWLOCATION, TRUE);
                //   curl_setopt($discode, CURLOPT_MAXREDIRS, 3);
                //   curl_setopt($discode, CURLOPT_SSL_VERIFYPEER, FALSE);
                //   curl_setopt($discode, CURLOPT_USERAGENT, 'My New Shopify App v.1');
                //   curl_setopt($discode, CURLOPT_CONNECTTIMEOUT, 30);
                //   curl_setopt($discode, CURLOPT_TIMEOUT, 30);
                //   curl_setopt($discode, CURLOPT_CUSTOMREQUEST, 'POST');
                //   // Setup headers
                //   $request_headers[] = "X-Shopify-Access-Token: " . $shops_token;
                //   $request_headers[] = 'Content-Type: application/json';

                //   curl_setopt($discode, CURLOPT_HTTPHEADER, $request_headers);
                //   $queryRule = json_encode($newrule);
                //   curl_setopt ($discode, CURLOPT_POSTFIELDS, $queryRule);
                //   $resultrule = curl_exec($discode);
                //   $resultrulea = json_decode($resultrule);
                //   //print_r($resultrulea); die;
                //   $bxgy=$resultrulea->price_rule->id;
                //   //echo $resultrulea->price_rule->id; die;
                //   // Curl For Discount Code
                //   $requrl="https://".env('SHOPIFY_KEY').":".$shops_token."@".$query["shop"];
                //   $discountURL= $requrl. "/admin/price_rules/".$resultrulea->price_rule->id."/discount_codes.json";
                //   //echo $discountURL; die;
                //   $discountcode=(object)[
                //           "discount_code"=> (object)[
                //             "code" => $resultrulea->price_rule->title
                //           ]
                //         ];
                  // $discountcodequery = json_encode($discountcode);
                  // $dcodef =  curl_init($discountURL);
                  //   curl_setopt($dcodef, CURLOPT_HEADER, FALSE);
                  //   curl_setopt($dcodef, CURLOPT_ENCODING, 'gzip');
                  //   curl_setopt($dcodef, CURLOPT_RETURNTRANSFER, TRUE);
                  //   curl_setopt($dcodef, CURLOPT_FOLLOWLOCATION, TRUE);
                  //   curl_setopt($dcodef, CURLOPT_MAXREDIRS, 3);
                  //   curl_setopt($dcodef, CURLOPT_SSL_VERIFYPEER, FALSE);
                  //   curl_setopt($dcodef, CURLOPT_USERAGENT, 'My New Shopify App v.1');
                  //   curl_setopt($dcodef, CURLOPT_CONNECTTIMEOUT, 30);
                  //   curl_setopt($dcodef, CURLOPT_TIMEOUT, 30);
                  //   curl_setopt($dcodef, CURLOPT_CUSTOMREQUEST, 'POST');
                  //   $request_headers1[]  = "Content-Type: application/json";
                  //   $request_headers1[]  = "Host:". $query["shop"];
                  //   curl_setopt($dcodef, CURLOPT_HTTPHEADER, $request_headers1);
                  //   curl_setopt($dcodef, CURLOPT_POSTFIELDS, $discountcodequery);
                  //   $result1code = curl_exec($dcodef);
                  //   $result1code = json_decode($result1code, true);
                    //$disbxgy=$result1code["discount_code"]["id"]; 
                    $data["discount_code"]  = ""; //$result1code["discount_code"]["code"];
              }  
               //echo $value; 
              //die;
              //echo "session is". $req->session()->get('oldpr');
            $new_subtotal = ($query["cartsubtotal"]/100) - $discountamount;                 
            $data["discount_amt"] = number_format($new_subtotal, 2);
            header('Content-type:application/json');
            echo json_encode($data);
            die;
          } // if user is exists
      }
    }

    /**
     * Product Selection .
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function checkQuantity(Request $req)
    {
      $html="";
      $exp=explode("#7819",$req->data);
      //echo $exp[0]."<br>".$exp[1];die;
      //$shop= explode(".",$exp[0]);
      //$data1 = $exp[1];
      //var_dump($data1); die;
      $getuser=User::where('name', $exp[0])->first();
      $is_user = $getuser->count();
      if($is_user>0)
      {
          $shops_token =$getuser->access_token;

        $quantity = Pricerule::where('campaign_id',$exp[1])->where('shop_name',$exp[0])->where('variant_qty',$exp[2])->first();
        $quantity_data = json_decode($quantity,true);
        //print_r($quantity_data);
        if(isset($quantity_data)){
          echo "1";
        }else{
          echo "0";
        }

      }
    }



    /**
     * Display a Help View.
     *
     * @return \Illuminate\Http\Response
     */
    public function helpview(Request $req)
    {
      $params = $_GET;
      //print_r($params); die;
      $shop_data=[];
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
        if (hash_equals($hmac, $computed_hmac)) {
          $getuser=User::where('domainname', $shop)->first();
          $userresult = json_decode($getuser, true);
          $is_user = $getuser->count();
          if($is_user>0){
              $shops_token =$getuser->access_token;
              $shop_data['shop_token'] = md5($shops_token);
             // return $campaigns; die;
             if($userresult["is_active"]!=0){
              $shop_data["isagree"] = 1;
             }else{
              $shop_data["isagree"] = 0;
             }
             return view('help',['data'=>$shop_data]); 
          }
        }
    }


    /**
     * Update the user table for activation
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function userpdate(Request $req)
    {
      //return $req; die;
      $getuser=User::where('domainname', $req->shop)->first();
      $is_user = $getuser->count();
      $shop_data["shop"] = $req->shop;
      if($is_user>0){
        $shops_token = $getuser->access_token;
        $getuser['is_active'] = 1;
        $getuser->save();

        $shop_data["isagree"] = 1;

        $campaign = Pricerule::where('shop_name',$req->shop)->groupBy('compaign_id')->get();
        $campaign_data = json_decode($campaign,true); 

            ///   *****************   cURL for Products Fetching    *****************   ///
            $url = "https://" . $req->shop . "/admin/products.json";
            $pch = curl_init();    
            curl_setopt($pch, CURLOPT_URL, $url); //Url together with parameters
            curl_setopt($pch, CURLOPT_RETURNTRANSFER, 1); //Return data instead printing directly in Browser
            curl_setopt($pch, CURLOPT_CONNECTTIMEOUT , 7); //Timeout after 7 seconds
            curl_setopt($pch, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
            curl_setopt($pch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: '.$shops_token));
            $cURLresult = curl_exec($pch);
            $proResult = json_decode($cURLresult, true);
            
            return view('viewcampaigns',['data'=>$shop_data,'campaigns'=>$campaign_data,'products'=>$proResult["products"]]);
        //return view('viewcampaigns',['data'=>$shop_data,'campaigns'=>$campaign_data,'products'=>$proResult["products"]]);
      }else{
        return back()->with('error','User Not Found!');
      }
    }

    public function addSettings(Request $req){
      $shop = $req->shop;
      $shopData=BQSetting::where('shop', $req->shop)->first();
      $shop_data["shop"] = $shop;
      $getuser=User::where('name', $shop)->first();
      $userresult = json_decode($getuser, true);
      if($userresult["is_active"]!=0){
        return view('settings',['data'=>$shopData]); 
      }else{
        if($userresult["is_active"]!=0){
          $shop_data["isagree"] = 1;
        }else{
          $shop_data["isagree"] = 0;
        }
        return view('help',['data'=>$shop_data]); 
      }

    }

    public function postSettings(Request $req){
      $this->validate(request(), [
        'shop'         => 'required',
        'text' => 'required',
        'currency'       => 'required',
        'carText'       => 'required',
        'DiscountMessage' => 'required'
      ]);
      
      $shopData=BQSetting::where('shop', $req->shop)->first();
      $shopData['text'] = $req->text;
      $shopData['currency'] = $req->currency;
      $shopData['cartText'] = $req->carText;
      $shopData['DiscountMessage'] = $req->DiscountMessage;
      // $shopData->save();
      if($shopData->save()){
        return back()->with('success','Text Setting are Updated');    
      }else{
        return back()->with('error','User Not Found!');
      }        
    }


    public function bxgyProductsselection(Request $req)
    {
      $html="";
      $exp=explode("7819",$req->data);
      $shop= explode(".",$exp[0]);
      $allselection=json_decode(stripslashes($exp[1]));
      $data1 = json_decode(stripslashes($exp[1]));
      //print_r($data1);die;
      $variants = json_decode(stripslashes($exp[2]));
      $var_arr=[]; $var_arrp=[];
      foreach ($variants as $asd) {
        $vary=explode("##",$asd);
        array_push($var_arr,$vary[1]);
        array_push($var_arrp,$vary[0]);
      }
      //var_dump($var_arr); die;
      $var_arrp = array_values(array_unique($var_arrp));
      //print_r($var_arrp);
      //print_r($data1);
      foreach ($var_arrp as $variants_arr) {
        array_push($allselection,$variants_arr);
      }
       $allselection = array_values(array_unique($allselection));
      foreach ($data1 as $key => $bxgyarray) {
        if(in_array($bxgyarray, $var_arrp)){
            //echo "yes = ". $key ."=". $bxgyarray;
            unset($data1[$key]);
        }
      }
      //echo "<br>after unset <br>";
      //print_r($allselection);

      $getuser=User::where('name', $exp[0])->first();
      $is_user = $getuser->count();
      if($is_user>0)
      {
          $shops_token =$getuser->access_token;
      //return $req;
      ///   *****************   cURL for Products Fetching   *****************   ///
          $purl = "https://" . $exp[0] . "/admin/products.json";
          $pch = curl_init();
          curl_setopt($pch, CURLOPT_URL, $purl); //Url together with parameters
          curl_setopt($pch, CURLOPT_RETURNTRANSFER, 1); //Return data instead printing directly in Browser
          curl_setopt($pch, CURLOPT_CONNECTTIMEOUT , 7); //Timeout after 7 seconds
          curl_setopt($pch, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
          curl_setopt($pch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: '.$shops_token));
          $cURLresult = curl_exec($pch);
          $proResult = json_decode($cURLresult, true);
          //print_r($proResult);//?>
          <table class='table table-responsive producttable'>
          <?php foreach ($proResult["products"] as $products) {
            if(in_array($products["id"], $allselection))
            { ?>
              <tr>
                <td width='8%'><img style='width: 100%' src='<?php echo $products["image"]["src"];?>'></td>
                <td width='92%' class="proTitles">
                  <?php
                      echo $products["title"]." ";
                      foreach ($products["variants"] as $variants) {
                        if(in_array($variants["id"], $var_arr)){
                          echo " [<span style='color:red;'>".$variants["title"] ."</span>]";
                        }
                      }
                  ?>
                </td>
                <td> <input type="button" class="btn btn-danger btn-sm removeProductSelect" data-value="<?php echo $products['id']; ?>" value="Remove" >  </td>
              </tr>
          <?php }
          }
            foreach ($data1 as $dt1) { ?>
              <input type="hidden" name="bxgyselectedp[]" value='<?php echo $dt1;?>'>
          <?php }

            foreach ($var_arr as $dt2) { ?>
              <input type="hidden" name="bxgyselectedvari[]" value='<?php echo $dt2;?>'>
          <?php }
            foreach ($allselection as $dt3) { ?>
              <input type="hidden" class="productIdsdata"  name="allselected[]" value='<?php echo $dt3; ?>'>
          <?php  }
          ?>
          </table>
      <?php  }
    }

    public function addnewRule(Request $request){

      //test
          // return $request->data;       
      //end test
    
      $name = $request->data['genralData'][0]['campaignName'];
      $text = $request->data['genralData'][1]['campaignText'];
      $shop_name = $request->data['shopname'];

      $compaign_id = DB::table('price_rules')->select('compaign_id')
      ->orderBy('compaign_id', 'desc')->first();
      $compaign_id = $compaign_id->compaign_id+1;  
      // echo $compaign_id; die;
      if(!isset($request->data['collections'])){
        // for products
          $counter =  count($request->data['lines']['qty']);    
          
          $count = count($request->data['products']);
          $allselectionids = implode("##", $request->data['products']);
          $runon = implode("##", $request->data['runOn']);
          $ruleData = implode('##', $request->data['lines']['ruleData']);
          // echo json_encode( $ruleData ); die; 
          $x=1;
          for($j= 0; $j < $counter; $j++){
            $qty = $request->data['lines']['qty'][$j];
            $discount = $request->data['lines']['discount'][$j];
            $type = $request->data['lines']['type'][$j];
            // $ruleData = $request->data['lines']['ruleData'][$j];
            

            
        

            DB::table('price_rules')->insert(
              [ 
                'shop_name'=> $shop_name , 'compaign_id' => $compaign_id, 'compaign_name'=> $name, 'line_no' => $x, 'rule_qty' => $qty, 
                'rule_title' => $text, "rule_value" => $discount,"rule_type"=> $type,
                "selectedProducts" =>  $allselectionids, "AppliesTo"=>"products", 'rule_data' => $ruleData, "run_on" => $runon
               ]
            );
            // echo "Line no. : ".$x."Name: ".$name."title: ".$text."qty: ".$qty."discount: ".$discount."type: ".$type."productids: ".$allselectionids;
            $x++;
          } 
          echo "true"; die;
      }else{
        // for collections
          $counter =  count($request->data['lines']['qty']);
          $collectionsids = implode('##', $request->data['collections']);
          $runon = implode("##", $request->data['runOn']);
          $ruleData = implode('##', $request->data['lines']['ruleData']);
          $x=1;
          for($j= 0; $j < $counter; $j++){
            $qty = $request->data['lines']['qty'][$j];
            $discount = $request->data['lines']['discount'][$j];
            $type = $request->data['lines']['type'][$j];
            // $ruleData = $request->data['lines']['ruleData'][$j];

            DB::table('price_rules')->insert(
              [ 
                'shop_name'=> $shop_name , 'compaign_id' => $compaign_id, 'compaign_name'=> $name, 'line_no' => $x, 'rule_qty' => $qty, 
                'rule_title' => $text, "rule_value" => $discount,"rule_type"=> $type,
                "slectedoCollections" =>  $collectionsids, "AppliesTo"=>"collections" ,'rule_data' => $ruleData, "run_on" => $runon
               ]
            );
            $x++;
          }
          echo "true"; die;

      }

    }
    public function updatenewRule(Request $request){

      $compaign_id = $request->data['campaignDataID'];
      $shop_name = $request->data['shopname'];
      $data  = DB::table('price_rules')->where('compaign_id', $compaign_id)->where('shop_name', $shop_name)->delete(); 
      if($data){
        $name = $request->data['genralData'][0]['campaignName'];
      $text = $request->data['genralData'][1]['campaignText'];
      $shop_name = $request->data['shopname'];

      $compaign_id = DB::table('price_rules')->select('compaign_id')
      ->orderBy('compaign_id', 'desc')->first();
      $compaign_id = $compaign_id->compaign_id+1;  
      // echo $compaign_id; die;
      if(!isset($request->data['collections'])){
        // for products
          $counter =  count($request->data['lines']['qty']);    
          
          $count = count($request->data['products']);
          $allselectionids = implode("##", $request->data['products']);
          $runon = implode("##", $request->data['runOn']);
          $ruleData = implode('##', $request->data['lines']['ruleData']);
          // echo json_encode( $ruleData ); die; 
          $x=1;
          for($j= 0; $j < $counter; $j++){
            $qty = $request->data['lines']['qty'][$j];
            $discount = $request->data['lines']['discount'][$j];
            $type = $request->data['lines']['type'][$j];
            // $ruleData = $request->data['lines']['ruleData'][$j];
            

            
        

            DB::table('price_rules')->insert(
              [ 
                'shop_name'=> $shop_name , 'compaign_id' => $compaign_id, 'compaign_name'=> $name, 'line_no' => $x, 'rule_qty' => $qty, 
                'rule_title' => $text, "rule_value" => $discount,"rule_type"=> $type,
                "selectedProducts" =>  $allselectionids, "AppliesTo"=>"products", 'rule_data' => $ruleData, "run_on" => $runon
               ]
            );
            // echo "Line no. : ".$x."Name: ".$name."title: ".$text."qty: ".$qty."discount: ".$discount."type: ".$type."productids: ".$allselectionids;
            $x++;
          } 
          echo "true"; die;
      }else{
        // for collections
          $counter =  count($request->data['lines']['qty']);
          $collectionsids = implode('##', $request->data['collections']);
          $runon = implode("##", $request->data['runOn']);
          $ruleData = implode('##', $request->data['lines']['ruleData']);
          $x=1;
          for($j= 0; $j < $counter; $j++){
            $qty = $request->data['lines']['qty'][$j];
            $discount = $request->data['lines']['discount'][$j];
            $type = $request->data['lines']['type'][$j];
            // $ruleData = $request->data['lines']['ruleData'][$j];

            DB::table('price_rules')->insert(
              [ 
                'shop_name'=> $shop_name , 'compaign_id' => $compaign_id, 'compaign_name'=> $name, 'line_no' => $x, 'rule_qty' => $qty, 
                'rule_title' => $text, "rule_value" => $discount,"rule_type"=> $type,
                "slectedoCollections" =>  $collectionsids, "AppliesTo"=>"collections" ,'rule_data' => $ruleData, "run_on" => $runon
               ]
            );
            $x++;
          }
          echo "true"; die;

      }
      }
    }
}

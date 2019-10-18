<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Campaign;
use App\Pricerule;
use Illuminate\Support\Facades\Validator;  
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $req, $shopT = null)
    {
        // $shopify_hmac=env('SHOPIFY_SECRET');
        // $code= $req->query->get('code'); 
        // $hmac= $req->query->get('hmac'); 
        $shop= $req->query->get('shop');
        $storename=explode('.',$shop);
        $params = $_GET;
        $shop_data["shop"] = $shop;
        if(!$shop){
            $shop = $shopT;
            $shop_data["shop"] = $shopT;
        }  
            $getuser=User::where('domainname', $shop)->first();
            $is_user = $getuser->count();
            if($is_user>0){
                $userresult = json_decode($getuser, true);
                if($userresult["is_active"]!=0){
                $shops_token =$getuser->access_token;

                $campaign = Pricerule::where('shop_name',$shop)->groupBy('compaign_id')->get();
                
                $campaign_data = json_decode($campaign,true); 
                // echo json_encode($campaign_data); die;

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
                    // echo json_encode($proResult); die;
                    // $getProducts = Pricerule::where('shop_name',$shop)->get();
                    // echo json_encode($getProducts); die;
                return view('viewcampaigns',['data'=>$shop_data,'campaigns'=>$campaign_data,'products'=>$proResult["products"]]);
                }else{
                    if($userresult["is_active"]!=0){
                        $shop_data["isagree"] = 1;
                      }else{
                        $shop_data["isagree"] = 0;
                      }
                    return view('help',['data'=>$shop_data]);
                }
            }
        // }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($shop)
    {
        // echo json_encode($shop); die;
        // $shop = "pardeep-quantitybreakdown.myshopify.com";
        // $shopify_hmac=env('SHOPIFY_SECRET');
        // $code= $req->query->get('code'); 
        // $hmac= $req->query->get('hmac'); 
        // $shop= $req->query->get('shop');
        // $storename=explode('.',$shop);
        // $params = $_GET;
        $shop_data["shop"] = $shop;
        // $params = array_diff_key($params, array('hmac' => ''));   // Remove hmac from params
        // ksort($params); // Sort params lexographically
        // // Compute SHA256 digest
        // $computed_hmac = hash_hmac('sha256', http_build_query($params), $shopify_hmac);
        // $reqtime= $req->query->get('timestamp');  
        // if (hash_equals($hmac, $computed_hmac)) {
            $getuser=User::where('domainname', $shop)->first();
            $is_user = $getuser->count();
            if($is_user>0){
                $shops_token =$getuser->access_token;
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

                $customCid = [];
                $smartCid = [];

                $customCtitle = [];
                $smartCTitle = [];


                //custom collections
                $url1 = "https://" . $shop . "/admin/custom_collections.json";
                $pch1 = curl_init();    
                curl_setopt($pch1, CURLOPT_URL, $url1); //Url together with parameters
                curl_setopt($pch1, CURLOPT_RETURNTRANSFER, 1); //Return data instead printing directly in Browser
                curl_setopt($pch1, CURLOPT_CONNECTTIMEOUT , 7); //Timeout after 7 seconds
                curl_setopt($pch1, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
                curl_setopt($pch1, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: '.$shops_token));
                $cURLresult1 = curl_exec($pch1);
                $collection = json_decode($cURLresult1, true);
                foreach ($collection['custom_collections'] as $value) {
                   array_push($customCid,$value['id']);
                   array_push($customCtitle ,$value['title']);
                }

                // smart collections
                $url1 = "https://" . $shop . "/admin/smart_collections.json";
                $pch1 = curl_init();    
                curl_setopt($pch1, CURLOPT_URL, $url1); //Url together with parameters
                curl_setopt($pch1, CURLOPT_RETURNTRANSFER, 1); //Return data instead printing directly in Browser
                curl_setopt($pch1, CURLOPT_CONNECTTIMEOUT , 7); //Timeout after 7 seconds
                curl_setopt($pch1, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
                curl_setopt($pch1, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: '.$shops_token));
                $cURLresult1 = curl_exec($pch1);
                $collections = json_decode($cURLresult1, true);  
                foreach ($collections['smart_collections'] as $value) {
                   array_push($smartCid,$value['id']);
                   array_push($smartCTitle ,$value['title']);
                }
                $data = null;                        
                $ids = array_merge($customCid,$smartCid);
                $titles = array_merge($customCtitle,$smartCTitle);
                $cdata['ids'] = $ids;
                $cdata['titles'] = $titles;

                $getProducts = Pricerule::where('shop_name',$shop)->where('status', 1)->get();
                $getProducts = json_decode($getProducts,true);
     
                return view('newcampaign',['data'=>$shop_data,'products'=>$proResult["products"], 'selectedProducts' => $getProducts, 'collectionsId'=>$cdata['ids'], 'collectionsTitle'=>$cdata['titles']]);
            }
        // }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
                'campaign_name' => 'required|unique:campaigns',
            ]);
        $storename=explode('.',$request->shop);
        $getuser=User::where('domainname', $request->shop)->first();
        $is_user = $getuser->count();

        //echo $request->selectedp[0]; die;
        if($is_user>0)
        {
          $shops_token =$getuser->access_token;
            
            if(!empty($request->selectedp)){
                $campaign = new Campaign;
                $campaign["shop_address"]   = $request->shop;
                $campaign["campaign_name"]  = $request->campaign_name;
                $campaign["product_id"]     = $request->selectedp[0];
                $campaign->save();
                return back()->with('success','Price Rule is successfully added');
            }else{
                return back()->with('error','Please select a product!');
            }
        }else{
          return back()->with('error','User Not Found!');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($shop, $id)
    {  //pardeepEdit
        $getdataWithID = Pricerule::where('shop_name',$shop)->where('compaign_id', $id)->get();
        $getdataWithID = json_decode($getdataWithID,true);
        // echo json_encode($getdataWithID); die;
        // $shop_data["shop"] = $shop;
        // $storename=explode('.',$shop);
        // $getuser=User::where('domainname', $shop)->first();
        // $is_user = $getuser->count();
        // if($is_user>0){
        //     $shops_token =$getuser->access_token;

        //     $campaign = Campaign::where('id',$id)->where('shop_address',$shop)->get();
        //     $campaign = json_decode($campaign, true);

        //     ///   *****************   cURL for Products Fetching    *****************   ///
        //     $url = "https://" . $shop . "/admin/products.json";
        //     $pch = curl_init();    
        //     curl_setopt($pch, CURLOPT_URL, $url); //Url together with parameters
        //     curl_setopt($pch, CURLOPT_RETURNTRANSFER, 1); //Return data instead printing directly in Browser
        //     curl_setopt($pch, CURLOPT_CONNECTTIMEOUT , 7); //Timeout after 7 seconds
        //     curl_setopt($pch, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
        //     curl_setopt($pch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: '.$shops_token));
        //     $cURLresult = curl_exec($pch);
        //     $proResult = json_decode($cURLresult, true);

        //     return view('editcampaign',['data'=>$shop_data,'campaigns'=>$campaign[0],'products'=>$proResult["products"]]);
        // }

        //pardeepedit
          // echo json_encode($shop); die;
        // $shop = "pardeep-quantitybreakdown.myshopify.com";
        // $shopify_hmac=env('SHOPIFY_SECRET');
        // $code= $req->query->get('code'); 
        // $hmac= $req->query->get('hmac'); 
        // $shop= $req->query->get('shop');
        // $storename=explode('.',$shop);
        // $params = $_GET;
        $shop_data["shop"] = $shop;
        // $params = array_diff_key($params, array('hmac' => ''));   // Remove hmac from params
        // ksort($params); // Sort params lexographically
        // // Compute SHA256 digest
        // $computed_hmac = hash_hmac('sha256', http_build_query($params), $shopify_hmac);
        // $reqtime= $req->query->get('timestamp');  
        // if (hash_equals($hmac, $computed_hmac)) {
            $getuser=User::where('domainname', $shop)->first();
            $is_user = $getuser->count();
            if($is_user>0){
                $shops_token =$getuser->access_token;
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

                $customCid = [];
                $smartCid = [];

                $customCtitle = [];
                $smartCTitle = [];


                //custom collections
                $url1 = "https://" . $shop . "/admin/custom_collections.json";
                $pch1 = curl_init();    
                curl_setopt($pch1, CURLOPT_URL, $url1); //Url together with parameters
                curl_setopt($pch1, CURLOPT_RETURNTRANSFER, 1); //Return data instead printing directly in Browser
                curl_setopt($pch1, CURLOPT_CONNECTTIMEOUT , 7); //Timeout after 7 seconds
                curl_setopt($pch1, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
                curl_setopt($pch1, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: '.$shops_token));
                $cURLresult1 = curl_exec($pch1);
                $collection = json_decode($cURLresult1, true);
                foreach ($collection['custom_collections'] as $value) {
                   array_push($customCid,$value['id']);
                   array_push($customCtitle ,$value['title']);
                }

                // smart collections
                $url1 = "https://" . $shop . "/admin/smart_collections.json";
                $pch1 = curl_init();    
                curl_setopt($pch1, CURLOPT_URL, $url1); //Url together with parameters
                curl_setopt($pch1, CURLOPT_RETURNTRANSFER, 1); //Return data instead printing directly in Browser
                curl_setopt($pch1, CURLOPT_CONNECTTIMEOUT , 7); //Timeout after 7 seconds
                curl_setopt($pch1, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
                curl_setopt($pch1, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: '.$shops_token));
                $cURLresult1 = curl_exec($pch1);
                $collections = json_decode($cURLresult1, true);  
                foreach ($collections['smart_collections'] as $value) {
                   array_push($smartCid,$value['id']);
                   array_push($smartCTitle ,$value['title']);
                }
                $data = null;                        
                $ids = array_merge($customCid,$smartCid);
                $titles = array_merge($customCtitle,$smartCTitle);
                $cdata['ids'] = $ids;
                $cdata['titles'] = $titles;

                $getProducts = Pricerule::where('shop_name',$shop)->where('status', 1)->get();
                $getProducts = json_decode($getProducts,true);
                
                return view('editcampaign',['data'=>$shop_data,'products'=>$proResult["products"], 'selectedProducts' => $getProducts, 'collectionsId'=>$cdata['ids'], 'collectionsTitle'=>$cdata['titles'] , 'campaign_data' => $getdataWithID ]);
            }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $storename=explode('.',$request->shop);
        $getuser=User::where('domainname', $request->shop)->first();
        $is_user = $getuser->count();
        //echo $request->selectedp[0]; die;
        $shop_data["shop"] = $request->shop;
        if($is_user>0)
        {
          $shops_token =$getuser->access_token;
            $this->validate(request(), [
                'campaignname' => 'required',
            ]);
            if(!empty($request->selectedp)){
                $campaign=Campaign::where('id', $id)->where('shop_address',$request->shop)->first();
                $campaign["campaign_name"]  = $request->campaignname;
                $campaign["product_id"]     = $request->selectedp[0];
                $campaign->save();

                $campaign = Campaign::where('shop_address',$request->shop)->get();
                $campaign_data = json_decode($campaign,true);

                ///   *****************   cURL for Products Fetching    *****************   ///
                $url = "https://" . $request->shop . "/admin/products.json";
                $pch = curl_init();    
                curl_setopt($pch, CURLOPT_URL, $url); //Url together with parameters
                curl_setopt($pch, CURLOPT_RETURNTRANSFER, 1); //Return data instead printing directly in Browser
                curl_setopt($pch, CURLOPT_CONNECTTIMEOUT , 7); //Timeout after 7 seconds
                curl_setopt($pch, CURLOPT_USERAGENT , "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
                curl_setopt($pch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json', 'X-Shopify-Access-Token: '.$shops_token));
                $cURLresult = curl_exec($pch);
                $proResult = json_decode($cURLresult, true);

                return view('viewcampaigns',['data'=>$shop_data,'campaigns'=>$campaign_data,'products'=>$proResult["products"]]);

                //return back()->with('success','Campaign is successfully Updated');
            }else{
                return back()->with('error','Please select a product!');
            }
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
    public function destroy($shop,$id)
    {
        $storename = explode('.',$shop);
        $getuser = User::where('domainname', $shop)->first();
        $is_user = $getuser->count();
        if($is_user>0){
            $shops_token =$getuser->access_token; $rulesids= array(); $rulesdiscount= array();
            $campaign = Campaign::where('id',$id)->first();

            $getpriceRules = Pricerule::where('campaign_id',$campaign["id"])->get();
            $getpriceRulescount = count($getpriceRules);
            foreach ($getpriceRules as $rules) {
                array_push($rulesids, $rules["fixed_rule"]);
                array_push($rulesids, $rules["percentage_rule"]);
                array_push($rulesids, $rules["shipping_rule"]);
                array_push($rulesids, $rules["bxgy_rule"]);

                array_push($rulesdiscount, $rules["fixed_discount"]);
                array_push($rulesdiscount, $rules["percentage_discount"]);
                array_push($rulesdiscount, $rules["shipping_discount"]);
                array_push($rulesdiscount, $rules["bxgy_discount"]);
                //$rulesids["percentage"]= $rules["percentage_rule"];
            };
            $rulesids=array_filter($rulesids);
            $rulesids=array_values($rulesids);
            
            $rulesdiscount=array_filter($rulesdiscount);
            $rulesdiscount=array_values($rulesdiscount);
            $xx=sizeof($rulesids); 
            for($i=0;$i<sizeof($rulesids);$i++){
            // echo "Price ID is: ".$rulesids[$i] ." - Discount ID is: ". $rulesdiscount[$i]."<br>";

                $disURL = "https://".$shop."/admin/price_rules/".$rulesids[$i]."/discount_codes/".$rulesdiscount[$i].".json";
                // // echo $access_token_url; die;
                $ch = curl_init();    
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_URL, $disURL);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token: '.$shops_token));
                    $result = curl_exec($ch);
                    curl_close($ch);

                //return back()->with('success','Price Rule is successfully Deleted');
                $ruleURL = "https://" . $shop . "/admin/price_rules/".$rulesids[$i].".json";
                // echo $access_token_url; die;
                $rch = curl_init();    
                    curl_setopt($rch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($rch, CURLOPT_URL, $ruleURL);
                    curl_setopt($rch, CURLOPT_CUSTOMREQUEST, "DELETE");
                    curl_setopt($rch, CURLOPT_HTTPHEADER, array('X-Shopify-Access-Token: '.$shops_token));
                    $rresult = curl_exec($rch);
                    curl_close($rch);
            }
            if($xx==$i){
                //echo "x".$xx."==".$i;
                $getpriceRules = Pricerule::where('campaign_id',$campaign["id"])->get()->each->delete();
                $campaign = Campaign::where('id',$id)->delete();
                //$getpriceRules->delete();
                //$campaign->delete();
                return back()->with('success','Campaign is successfully Deleted');
            }
        }
    }

    /**
     * Select Product For Campaign Entry.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function campaignproduct(Request $req)
    {
        $html="";
        $exp=explode("7819P",$req->data);
        $shop= explode(".",$exp[0]);
        $data1 = $exp[1];
        //var_dump($data1); die;
        $getuser=User::where('domainname', $exp[0])->first();
        $is_user = $getuser->count();
        if($is_user>0)
        {
          $shops_token =$getuser->access_token;


          $campaign = Campaign::select('product_id')->where('shop_address',$exp[0])->where('status',1)->get();
          $campaign = json_decode($campaign, true);
          $proexits=[];
          for($k=0;$k<count($campaign);$k++) {
              array_push($proexits,$campaign[$k]["product_id"]);
          }
          // print_r($proexits);die;
           
            ///   *****************   cURL for Products Fetching    *****************   ///
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
            <?php 
            $flag=0;
            foreach ($proResult["products"] as $products) {
                if(!in_array($data1, $proexits)){
                    if($products["id"]==$data1)
                    { ?> 
                      <tr>
                        <td width='8%'><img style='width: 100%' src='<?php echo $products["image"]["src"];?>'></td>
                        <td width='92%'><?php echo $products["title"]?></td>
                        <input type="hidden" name="selectedp[]" value='<?php echo $products["id"];?>'>
                      </tr>
                    <?php }
                }else{ 
                    $flag =1;        
             }    
            } if($flag==1) { ?>
                    <tr>
                        <td colspan="3">Please Choose different Product! Product already Exits</td>
                    </tr>
            <?php } ?>
            </table>
        <?php  }
    }
    public function postUpdateStatus(Request $req) {
     
    if($req->val == 1){
        $data =  DB::table('price_rules')->where('compaign_id', $req->id)->update(['status' => 1]);
    }else{
        $data =  DB::table('price_rules')->where('compaign_id', $req->id)->update(['status' => 0]);
    }
       
    }
    public function removeCampaign($shop, $id){
        $compaign_id = $id;
        $shop_name = $shop;
        $data  = DB::table('price_rules')->where('compaign_id', $compaign_id)->where('shop_name', $shop_name)->delete(); 
        if($data){
            return back()->with('price_rule', 'Price rule Deleted!');
        }
    }
}

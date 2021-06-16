<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Category;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ApiController extends Controller
{
    //
    public function __construct()
    {
        $this->serverUrl = "http://192.168.111.120:8000/";
    }

    public function send_sms($phone, $content) {
        $message = "SnapSell Help Center \n ".$content;
    }
    public function signup(Request $request) {
        $users = Customer::where('c_phone_number', $request->c_phone_number)
                            ->orWhere('c_email', $request->c_email)
                            ->orWhere('c_name', $request->c_name)
                            ->get();
        if(count($users) && isset($users[0]->c_phone_verified)) {
            return array(
                "flag" => 0,
                "msg" => 'Email Or Phone Number Or UserName is already used',
                "data" => ''
            );
        } else {
            $verify_code = rand(1999, 9999);
            if(count($users)){
                $user = Customer::where('c_phone_number', $request->c_phone_number)
                            ->update(['c_verify_code' => $verify_code, 'c_email' => $request->c_email]);
            } else {
                $user = Customer::create([
                    'c_name' => $request->c_name,
                    'c_email' => $request->c_email,
                    'c_phone_number' => $request->c_phone_number,
                    'c_password' => Hash::make($request->c_pwd),
                    'c_verify_code' => $verify_code
                ]);
            }

            $this->send_sms($request->c_phone_number, $verify_code);
            
            return array(
                "flag" => 1,
                "msg" => 'You signup successfully',
                "data" => $user
            );
        }
        
        return $users;
    }

    public function resendcode(Request $request){
        $user = Customer::where('c_phone_number', $request->c_phone_number)
                                ->get();
        if(count($user)){
            $verify_code = rand(1999, 9999);
            $update = Customer::where('c_phone_number', $request->c_phone_number)
                            ->update(['c_verify_code' => $verify_code]);

            $this->send_sms($request->c_phone_number, $verify_code);

            return array(
                "flag" => 1,
                "msg" => 'We sent verify code to '.$request->c_phone_number,
                "data" => $user
            );
        } else {
            return array(
                "flag" => 0,
                "msg" => 'Phone number is not registered',
                "data" => ''
            );
        }
    }

    public function verify(Request $request){
        $user = Customer::where('c_phone_number', $request->c_phone_number)
                        ->where('c_verify_code', $request->c_verify_code)
                                ->get();
        if(count($user)){
            Customer::where('c_phone_number', $request->c_phone_number)
                        ->update(['c_phone_verified'=> 1]);
            $user = Customer::where('c_phone_number', $request->c_phone_number)
                                ->get();
            $user[0]->avatar = $user[0]->c_avatar? $this->serverUrl."uploads/customers/".$user[0]->c_avatar : '';
            return array(
                "flag" => 1,
                "msg" => 'Phone Number is verified',
                "data" => $user
            );
        } else {
            return array(
                "flag" => 0,
                "msg" => 'Invalide Verify code',
                "data" => ''
            );
        }
    }

    public function login(Request $request){
        $user = Customer::where('c_email', $request->c_email)
                        ->where('c_phone_verified', 1)
                        ->get();
        if(count($user)){
            if(Hash::check($request->c_pwd, $user[0]->c_password)){
                $user[0]->avatar = $user[0]->c_avatar? $this->serverUrl."uploads/customers/".$user[0]->c_avatar : '';
                return array(
                    "flag" => 1,
                    "msg" => 'Success',
                    "data" => $user[0]
                );
            } else {
                return array(
                    "flag" => 0,
                    "msg" => 'Emaiil or Password is not correct',
                    "data" => ''
                );
            }
        } else {
            return array(
                "flag" => 0,
                "msg" => 'Email is not registered',
                "data" => $request->c_email
            );
        }
    }

    public function getcategories(Request $request){
        $category = Category::all();
        $temp = array();
        foreach($category as $cat){
            $cat->cat_image = $this->serverUrl."uploads/categories/".$cat->cat_image;
            $temp[] = $cat;
        }
        return $temp;
    }

    public function filterproducts(Request $request){

        $categories = json_decode($request->categories);
        $query_category = "";
        for($i = 0; $i < count($categories); $i++){
            $query_category .= "cat_id = ".$categories[$i]." ";
            if(($i + 1) < count($categories)){
                $query_category .= "OR ";
            }
        }
        $query = "SELECT * FROM (SELECT *, COUNT(pro_id) AS num FROM snap_product_cat WHERE ".$query_category." GROUP BY pro_id) t1 WHERE t1.num >=".$i."";
        $results = DB::select($query);
        $products = array();
        foreach ($results as $pro) {

            $pro_data = DB::select("SELECT * FROM snap_products WHERE pro_id = ".$pro->pro_id);
            $pro_category = DB::select("SELECT * FROM snap_product_cat WHERE pro_id = ".$pro->pro_id);
            
            $temp = array();

            foreach($pro_category as $p_cat){
                $category = Category::where('cat_id', $p_cat->cat_id)->get();
                if(count($category)){
                    $temp[] = array(
                        "cat_id" => $category[0]->cat_id,
                        "cat_name_en" => $category[0]->cat_name_en,
                        "cat_name_ar" => $category[0]->cat_name_ar
                    );
                }
            }

            $products[] = array(
                "pro_id"=> $pro_data[0]->pro_id,
                "pro_name"=> $pro_data[0]->pro_name,
                "pro_description"=> $pro_data[0]->pro_description,
                "pro_location"=> $pro_data[0]->pro_location,
                "pro_sub_location"=> $pro_data[0]->pro_sub_location,
                "pro_price"=> $pro_data[0]->pro_price,
                "pro_currency"=> $pro_data[0]->pro_currency,
                "pro_currency_symbo"=> $pro_data[0]->pro_currency_symbo,
                "pro_media_type"=> $pro_data[0]->pro_media_type,
                "pro_media_url"=> $this->serverUrl."uploads/products/".$pro_data[0]->pro_media_url,
                "pro_promoted"=> $pro_data[0]->pro_promoted,
                "pro_customer_id"=> $pro_data[0]->pro_customer_id,
                "created_at"=> $pro_data[0]->created_at,
                "updated_at"=> $pro_data[0]->updated_at,
                "pro_categories" => $temp
            );
        }
        $products_results = array();
        foreach($products as $pro){
            $user = DB::select("SELECT * FROM snap_customers WHERE c_id = ".$pro['pro_customer_id']);
            $pro['customer'] = array(
                "customer_avatar"=>$user[0]->c_avatar ? $this->serverUrl."uploads/customers/".$user[0]->c_avatar: '',
                "customer_name"=>$user[0]->c_name,
                "customer_full_name"=>$user[0]->c_full_name
            );
            $products_results[] = $pro;
        }

        return $products_results;
        
    }

    public function getprofile(Request $request){
        $user = Customer::where("c_id", $request->customer_id)->get();
        if(count($user)){
            $user[0]->avatar = $user[0]->c_avatar? $this->serverUrl."uploads/customers/".$user[0]->c_avatar : '';
            return array(
                "flag" => 1,
                "msg" => '',
                "data" => $user[0]
            );
        } else {
            return array(
                "flag" => 0,
                "msg" => 'This account is closed',
                "data" => ''
            );
        }
        
    }

    public function getreviews(Request $request){
        $reviews = DB::select('SELECT * FROM snap_reviews WHERE rev_to_uid = '.$request->customer_id);
        $result = array();

        foreach($reviews as $rev){
            $product = DB::select('SELECT * FROM snap_products WHERE pro_id = '.$rev->rev_to_proid);
            $reviewer = DB::select('SELECT * FROM snap_customers WHERE c_id = '.$rev->rev_from_uid);

            if(count($product)){
                $product[0]->pro_media_url = $this->serverUrl."uploads/products/".$product[0]->pro_media_url;
                if(count($reviewer)){
                    $reviewer[0]->avatar = $reviewer[0]->c_avatar ? $this->serverUrl."uploads/customers/".$reviewer[0]->c_avatar : "" ;
                    $rev->reviewer = $reviewer[0];
                }
                $rev->product = $product[0];
                
                $result[] = $rev;    
            }
        }

        return $result;
    }

    public function getproducts(Request $request){
        $products = DB::select('SELECT * FROM snap_products WHERE pro_customer_id = '.$request->customer_id);
        
        $result = array();
        foreach($products as $pro){
            $pro->pro_media_url = $this->serverUrl."uploads/products/".$pro->pro_media_url;
            $result[] = $pro;
        }

        return $result;
    }

    public function togglefollow(Request $request){
        $follow = DB::select('SELECT * FROM snap_follow WHERE f_from_uid = '.$request->from_uid.' AND f_to_uid = '.$request->to_uid);
        
        if(count($follow)){
            DB::table('snap_follow')
                ->where('f_to_uid', '=', $request->to_uid)
                ->where('f_from_uid', '=', $request->from_uid)
                ->delete();
            return array(
                'flag' => 1,
                'data' => 0
            );
        } else {
            DB::insert('INSERT into snap_follow (f_from_uid, f_to_uid) values (?, ?)', [$request->from_uid, $request->to_uid]);
            return array(
                'flag' => 1,
                'data' => 1
            );
        }
    }

    public function getfollow(Request $request){
        $follow = DB::select('SELECT * FROM snap_follow WHERE f_from_uid = '.$request->from_uid.' AND f_to_uid = '.$request->to_uid);
        
        if(count($follow)){
            return array(
                'flag' => 1,
                'data' => 1
            );
        } else {
            return array(
                'flag' => 1,
                'data' => 0
            );
        }
    }

    public function uploadFile(Request $request){
        $files = $request->file('file_attachment');
        if(!$files)
        {
            return array(
                "status"=> 0,
                "msg"=>"Post is Failed",
                "data"=>'');
        }
        // $testPro = DB::select("SELECT * FROM snap_products WHERE pro_name = ".$request->pro_name);
        // if(count($testPro)){
        //  return array(
        //         "status"=> 0,
        //         "msg"=>"The same product name is already exist",
        //         "data"=>'');   
        // }
        if($request->pro_media_type == 'image'){
            $filename = "product".round(microtime(true) * 1000).".jpg";
        } else {
            $filename = "product".round(microtime(true) * 1000).".mp4";
        }
        request()->file_attachment->move(public_path('uploads/products/'), $filename);

        // $result = DB::insert('INSERT into snap_products (pro_name, pro_description, pro_location, pro_price, pro_currency, pro_currency_symbo, pro_media_type, pro_media_url, pro_customer_id ) values (?, ?, ?, ?, ?, ?, ?, ?, ?)', [$request->pro_name, $request->pro_description, $request->pro_location, $request->pro_price, $request->pro_currency, $request->pro_currency_symbol, $request->pro_media_type, $filename, $request->pro_customer_id]);f
        $id = DB::table('snap_products')->insertGetId([
                'pro_name'=> $request->pro_name,
                'pro_description'=>$request->pro_description,
                'pro_location'=>$request->pro_location,
                'pro_price'=>$request->pro_price,
                'pro_currency'=>$request->pro_currency,
                'pro_currency_symbo'=>$request->pro_currency_symbol,
                'pro_media_type'=>$request->pro_media_type,
                'pro_media_url'=>$filename,
                'pro_customer_id'=>$request->pro_customer_id,
            ]);
        $categories = json_decode($request->pro_categories);
        foreach($categories as $category){
            DB::table('snap_product_cat')->insertGetId([
                "cat_id"=>$category,
                "pro_id"=>$id
            ]);
        }
        return array(
            "flag"=> 1,
            "msg"=> "Success",
            "data"=>$id
        );
    }

    public function getpacctterms () {
        $result = DB::select("SELECT * FROM snap_pacct_terms");

        return $result;
    }

    public function resetpassword (Request $request) {
        $user = Customer::where('c_phone_number', $request->c_phone_number)->get();
        if(count($user)){
            $newUser = Customer::where('c_phone_number', $request->c_phone_number)
                            ->update(['c_password' => Hash::make($request->new_pwd)]);
            $user[0]->avatar = $user[0]->c_avatar? $this->serverUrl."uploads/customers/".$user[0]->c_avatar : '';
            return array(
                "flag"=>1,
                "msg"=> "Success",
                "data"=>$user[0]
            );
        } else {
            return array(
                "flag"=>0,
                "msg"=> "The phone number is not registered",
                "data"=>''
            );
        }
        return $result;
    }

    public function updatefcmtoken (Request $request) {
        $user = Customer::where('c_id', $request->c_id)
                            ->update(['c_fcm_token' => $request->fcmToken]);
        $result = $this->sendPush($request->fcmToken);
        return array(
            'data' => $result
        );
    }

    public function sendPush(){
        return $response = Http::withToken('AAAAM6_y7nE:APA91bHEXatN0mxz7v6TqT-7focd7t_6MmX6eDxKV-eaIlpch_PEJpqxyw__m0N7N4CZmnRO8QSz01KGxDpQ1vWJ4YgIwIAbP_W2dVqCMHgnTdyJzt-F3IEIQgWrvmzf5KGHrvvZEc2q')->post('https://fcm.googleapis.com/fcm/send', [
                'to' => 'el0JNyhKQqOmYe9qGDb-e2:APA91bF3kTfffJK7NF_go-kkcxMJx5nNxXIao7E7_deeXBJKX9WC7DJ8Io6mc8p4SbnMwsYd5DH190H0lI4kjbVVsI9B25g3w3ewMuywnSSzP-YwOz88pK3PN7Ok1Di5LGFNCm_hHtrw',
                'notification' => ["body" => "The first message from the React Native and Firebase",
                                    "title" => "Server",
                                    "content_available" => true,
                                    "priority" => "high"
                                ],
                'data' => ["body" => "The first message from the React Native and Firebase",
                                    "title" => "Server",
                                    "content_available" => true,
                                    "priority" => "high"
                                ],
            ]);
    }
    
}

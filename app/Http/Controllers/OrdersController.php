<?php

namespace App\Http\Controllers;

use App\User;
use App\ContactNumber;
use App\Order;
use App\OrderLog;
use App\OrderDetails;
use App\Product;
use App\ProductCategory;
use App\FinancingDelivery;

use App\ProductParentCategory;
use App\ProductMainCategory;
use App\ProductID;

use Illuminate\Http\Request;

use Auth;

use Response;
use Validator;
use Hash, DB;
use Storage;

use Carbon\Carbon;

class OrdersController extends Controller
{

    public function list(Request $request, $perPage = 20) {

        $orders = Order::orderBy('date_of_delivery','DESC')->orderBy('order_id','DESC')->paginate($perPage);

        foreach($orders as $o){
            //$prio = 0;
            $total_price = OrderDetails::where('order_id',$o->order_id)->where('order_status',1)->sum('total_price');
            $o->total_price = $total_price;
            $prods = OrderDetails::where('order_id',$o->order_id)->where('order_status',1)->pluck('product_id');
            $prio = Product::whereIn('product_id',$prods)
                        ->where(function($q){
                                    $q->where('category_id', 3);
                                    $q->orwhere('category_id', 5);
                                })
                        ->count();
            $o->prio = 0;
            if($prio > 0 && $o->is_delivered == 0){
                $o->prio = 1;
            }
        }

        $response = [];

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $orders;
        return Response::json($response);

    }

    public function show($id){
        $order = Order::where('order_id',$id)->first();

        $total_price = OrderDetails::where('order_id',$order->order_id)->where('order_status',1)->sum('total_price');
        $order->total_price = $total_price;

        $details = OrderDetails::where('order_id',$order->order_id)->get();

        foreach($details as $d){
            $prod = Product::where('product_id', $d->product_id)->first();
            $cat = ProductCategory::where('category_id',$prod->category_id)->first();
            $d->product_name = $prod->product_name;
            $d->product_name_chinese = $prod->product_name_chinese;
            $d->category = $cat->name;
            $d->category_id = $cat->category_id;
            $d->combined_name = $cat->name.' - '.$prod->product_name.' ('.$prod->product_name_chinese.')';
        }

        $order->details = $details;

        $response = [];

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $order;
        return Response::json($response);
    }

    public function viewOrderLog($id){
        $order = OrderLog::where('order_id',$id)->orderBy('id','Desc')->get();

        $response = [];

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $order;
        return Response::json($response);
    }

    public function productCategories(){
        $cats = ProductCategory::get();

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $cats;
        return Response::json($response);
    }

    public function products($cat_id){
        $prods = Product::where('category_id',$cat_id)->get();

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $prods;
        return Response::json($response);
    }

    // new api for app
    public function getProductCategories($cat_id){
        $cats = DB::table('product_parent_category')
                ->leftJoin('product_category', 'product_category.category_id', '=', 'product_parent_category.category_id')
                ->where('product_parent_category.parent_id', $cat_id)
                ->get();

        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $cats;

        $catCount = DB::table('product_parent_category')->where('parent_id', $cat_id)->count();

        $hasProd = DB::table('product')->where('product.category_id', $cat_id)->count();

        if($catCount > 0 && $hasProd > 0) {
            $response['data'][] = array( 
                'id' => $cat_id,
                'parent_id' => $cat_id,
                'name' => 'Others'
            );
        }
       
        return Response::json($response);
    }

    public function getProducts($cat_id) {
        $cats = DB::table('product')
                ->where('category_id', $cat_id)
                ->get();
                
        $response['status'] = 'Success';
        $response['code'] = 200;
        $response['data'] = $cats;
        
        return Response::json($response);
    }
    // end of new api for app


    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'name' => 'required',
            'address' => 'required',
            'contact' => 'required|min:11',
            'date_of_delivery' => 'required',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $cp = $request->contact;
            preg_match_all('!\d+!', $cp, $matches);
            $cp = implode("", $matches[0]);
            $cp = ltrim($cp,"0");
            $cp = ltrim($cp,'+');
            $cp = ltrim($cp,'63');
                

            $client_id = $request->client_id;
            if(!$request->client_id || $request->client_id == ''){
                $exist = ContactNumber::where('number','like','%'.$cp)->where('user_id','!=',null)->count();
                if($exist){
                    $response['status'] = 'Failed';
                    $response['errors'] = 'Contact Number already taken';
                    $response['code'] = 422;
                    return Response::json($response);
                }
                $user = new User;
                $user->first_name = $request->name;
                $user->last_name = $request->last_name;
                $user->wechat_id = $request->wechat_id;
                $user->telegram = $request->telegram;
                $user->address = $request->address;
                // $order->contact = $request->contact;
                $user->save();

                $num = new ContactNumber;
                $num->user_id = $user->id;
                $num->number = $request->contact;
                $num->save();

                $user->update([
                                'password' => bcrypt($request->contact)
                            ]);

                $user->branches()->attach(1);
                $user->roles()->attach(2);

                $client_id = $user->id;
            }
            else{
                $user = User::findorFail($request->client_id);
                $user->wechat_id = $request->wechat_id;
                $user->telegram = $request->telegram;
                $user->address = $request->address;
                $user->save();

                // $user->contactNumbers()->delete();
                // $num = new ContactNumber;
                // $num->user_id = $user->id;
                // $num->number = $request->contact;
                // $num->save();
            }


            $order = new Order;
            $order->name = $request->name;
            $order->last_name = $request->last_name;
            $order->user_id = $client_id;
            $order->rmb_received = $request->rmb;
            $order->wechat_id = $request->wechat_id;
            $order->telegram = $request->telegram;
            $order->address = $request->address;
            $order->contact = $request->contact;
            $order->remarks = $request->remarks;
            $order->date_of_delivery = $request->date_of_delivery;
            $order->save();

            foreach($request->products as $p){
                $order_detail = new OrderDetails;
                $order_detail->order_id = $order->order_id;
                $order_detail->product_id = $p['product_id'];
                $order_detail->qty = $p['qty'];
                $order_detail->remarks = $p['remarks'];
                $order_detail->unit_price = $p['unit_price'];
                $order_detail->total_price = $p['total_price'];
                $order_detail->save();
            }

            $response['status'] = 'Success';
            $response['code'] = 200;
        }

        return Response::json($response);
    }

    public function update(Request $request, $id){


        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'name' => 'required',
            'last_name' => 'required',
            'address' => 'required',
            // 'wechat_id' => 'required',
            'contact' => 'required|min:11',
            'date_of_delivery' => 'required',
            'client_id' => 'required',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $user = User::findorFail($request->client_id);
            $user->wechat_id = $request->wechat_id;
            $user->telegram = $request->telegram;
            $user->address = $request->address;
            $user->save();

            $user->contactNumbers()->delete();
            $num = new ContactNumber;
            $num->user_id = $user->id;
            $num->number = $request->contact;
            $num->save();


            $order = Order::where('order_id',$id)->first();
            $order->name = $request->name;
            $order->last_name = $request->last_name;
            $order->rmb_received = $request->rmb;
            $order->wechat_id = $request->wechat_id;
            $order->telegram = $request->telegram;
            $order->address = $request->address;
            $order->contact = $request->contact;
            $order->date_of_delivery = $request->date_of_delivery;
            $order->remarks = $request->remarks;
            $order->save();

            $old = OrderDetails::where('order_id',$id)->get();
            $oldids = $old->pluck('id');
            $log = '';
            //OrderDetails::where('order_id',$id)->delete();
            foreach($request->products as $p){
                foreach($old as $o){
                    if($o->product_id == $p['product_id'] && $o->order_id == $id && $o->qty != $p['qty']){
                        $prod = Product::where('product_id',$p['product_id'])->first();
                        $log .= '&bull; Updated '.$prod->product_name.' qty from '.$o->qty.' to '.$p['qty']. '<br>';
                    }                   
                }

                $new = OrderDetails::whereIn('id',$oldids)->where('product_id',$p['product_id'])->count();
                if($new == 0){
                    $prod = Product::where('product_id',$p['product_id'])->first();
                    $log .= '&bull; Added '.$prod->product_name.' qty '.$p['qty']. '<br>';
                }


                $order_detail = new OrderDetails;
                $order_detail->order_id = $id;
                $order_detail->product_id = $p['product_id'];
                $order_detail->qty = $p['qty'];
                $order_detail->remarks = $p['remarks'];
                $order_detail->unit_price = $p['unit_price'];
                $order_detail->total_price = $p['total_price'];
                $order_detail->save();
            }

            OrderDetails::whereIn('id',$oldids)->delete();

            if($log !=''){
                if(Auth::check()) {
                    //Insert new order log
                    $name = Auth::user()->first_name;
                    $log = $name.' updated order. <br><br>'.$log;
                    $log_data['order_id'] = $id;
                    $log_data['log'] = $log;
                    OrderLog::insert($log_data);
                }
            }

            $response['status'] = 'Success';
            $response['code'] = 200;
        }

        return Response::json($response);
    }

    public function delete($id){
            Order::where('order_id',$id)->delete();
            OrderDetails::where('order_id',$id)->delete();
            FinancingDelivery::where('record_id',$id)->delete();
            $response['status'] = 'Success';
            $response['code'] = 200;

        return Response::json($response);
    }


    public function markComplete(Request $request){
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'is_delivered' => 'required',
            // 'money_received' => 'required',
            // 'delivered_by' => 'required',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            if($request->delivered_by == 'Grab' && ($request->grab_fee == '' || $request->grab_fee == null)){
                $response['status'] = 'Failed';
                $response['code'] = 422;
                return Response::json($response);
            }

            $order = Order::where('order_id',$request->order_id)->first();
            $is_delivered = ($request->is_delivered == 'no' ? 0 : 1);
            // $is_received = ($request->is_received == 'no' ? 0 : 1);
            $order->is_delivered = $is_delivered;
            // $order->is_received = $is_received;
            // $order->remarks = $request->remarks;
            if($request->currency == 'peso'){
                $order->money_received = $request->money_received;
                $order->rmb_received = '';
            }
            if($request->currency == 'rmb'){
                $order->rmb_received = $request->rmb_received;
                $order->money_received = '';
            }
            $order->delivered_by = $request->delivered_by;
            $order->save();

            $total = OrderDetails::where('order_id',$request->order_id)->sum('total_price');

            $checkID = FinancingDelivery::where('record_id',$request->order_id)->count();

            if($request->delivered_by != '' && $order->rmb_received > 0 && $checkID == 0){
                $trans_desc = $request->delivered_by.' for order #'.$request->order_id.', paid chinese money';
                if($request->delivered_by == 'Grab'){
                    $trans_desc = $trans_desc.'. grab fee : '.$request->grab_fee;
                }
              $log_data['user_sn'] = Auth::user()->id;
              $log_data['record_id'] = $request->order_id;
              $log_data['trans_desc'] = $trans_desc;
              $log_data['cat_type'] = 'delivery';
              $log_data['chmoney_paid'] = $order->rmb_received;
              $log_data['other_cost'] = $request->grab_fee;
              FinancingDelivery::insert($log_data); 
            }

            $checkID = FinancingDelivery::where('record_id',$request->order_id)->count();

            if(($request->delivered_by != ''&& $request->delivered_by != 'Picked up') && $checkID == 0){
                $delivery_budget = $this->roundFunction($total) - $total;
                $rem = $delivery_budget + $total;
              $trans_desc = $request->delivered_by.' budget for delivery ('.$delivery_budget.'p) for order #'.$request->order_id.', and '.$rem.' to be remitted.';
              $log_data['user_sn'] = Auth::user()->id;
              $log_data['record_id'] = $request->order_id;
              $log_data['trans_desc'] = $trans_desc;
              $log_data['cat_type'] = 'delivery';
              $log_data['delivery_budget'] = $delivery_budget;
              FinancingDelivery::insert($log_data);
            }


            $response['status'] = 'Success';
            $response['code'] = 200;
        }

        return Response::json($response);
    }

    public function roundFunction($n)  
    {  
        // Smaller multiple  
        $a = (int)($n / 1000) * 1000; 
        // \Log::info('A : '.$a); 
          
        // Larger multiple  
        $b = ($a + 1000);  
        // \Log::info('B : '.$b); 
      
        // Return whichever is higher
        return $b;  
    } 

    public function addProduct(Request $request){
        $validator = Validator::make($request->all(), [
            'category_id' => 'required',
            'price' => 'required',
            'name' => 'required',
            'name_chinese' => 'required',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {

            $prod = new Product;
            $prod->category_id = $request->category_id;
            $prod->product_price = $request->price;
            $prod->product_name = $request->name;
            $prod->product_name_chinese = $request->name_chinese;
            $prod->save();
            $response['status'] = 'Success';
            $response['code'] = 200;
        }

        return Response::json($response);
    }

    public function updateProduct(Request $request){
        $validator = Validator::make($request->all(), [
            'product_id' => 'required',
            'price' => 'required',
            'name' => 'required',
            'name_chinese' => 'required',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {

            $prod = Product::where('product_id',$request->product_id)->first();
            // $prod->product_price = $request->price;
            $prod->product_price = $request->orig_price * $request->multiplier;
            $prod->orig_price = $request->orig_price;
            $prod->unit = $request->unit;
            $prod->multiplier = $request->multiplier;
            $prod->product_name = $request->name;
            $prod->product_name_chinese = $request->name_chinese;
            $prod->save();
            $response['status'] = 'Success';
            $response['code'] = 200;
        }

        return Response::json($response);
    }

    public function uploadProduct(Request $request, $id){
        foreach($request->data as $item) {
            $imgData = [
                'imgBase64' => $item['imgBase64'],
                'file_path' => $id,
                'img_name' => $item['file_path']
            ];
            $success = $this->uploadDocuments($imgData);
            // return $success;
            if($success){            
                $product = Product::where('product_id',$id)->first();
                // return $product;
                if($product){
                    $product->product_img = $item['file_path'];
                    $product->save();
                }
                return json_encode([
                    'success' => true,
                    'message' => 'Successfully saved.'
                ]);
            }
            else{
                return json_encode([
                    'success' => false,
                    'message' => 'Uplaod failed.'
                ]);
            }
        }

    }

    public function uploadDocuments($data) {
        $img64 = $data['imgBase64'];

        list($type, $img64) = explode(';', $img64);
        list(, $img64) = explode(',', $img64); 

        $success = 0;
        
        if($img64!=""){ // storing image in storage/app/public Folder 
            \Storage::disk('public')->put('products/' . $data['img_name'], base64_decode($img64)); 
            $success = 1;
        } 

        return $success;
    }

    public function newOrderSummary(Request $request){
        $validator = Validator::make($request->all(), [
            'order_ids' => 'required|array',
            // 'order_id_to' => 'required',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {

            //$orders = Order::where('order_id','>=',$request->order_id_from)->where('order_id','<=',$request->order_id_to)
                        //->where('is_delivered',0)->pluck('order_id');
            
            $prod_ids = OrderDetails::whereIn('order_id',$request->order_ids)->groupBy('product_id')->pluck('product_id');
            

            $list = [];
            $ctr = 0;

            $cats = Product::whereIn('product_id',$prod_ids)->orderBy('category_id')->groupBy('category_id')->pluck('category_id');

            $final_total = 0;
            $total_kg = 0;
            $total_pc = 0;
            $total_tali = 0;
            $tprice = [];
            $tkg = [];
            $tpc = [];
            $tali = [];
            $price_total2 = 0; // total price of masks
            $kg_total2 = 0; // total kg price of masks
            $delivery_charge = 0; // total delivery charge


            foreach($cats as $c){
                $kg = 0;
                $pc = 0;
                $t = 0;
                $prc =0;
                $list[$ctr]['category'] = ProductCategory::where('category_id',$c)->first()->name;
                $list[$ctr]['category_id'] = $c;

                $products = Product::whereIn('product_id',$prod_ids)->where('category_id',$c)->get();

                $ps = $products->pluck('product_id');
                $list[$ctr]['orders'] = OrderDetails::whereIn('order_id',$request->order_ids)->whereIn('product_id',$ps)->orderBy('order_id')->groupBy('order_id')->pluck('order_id');

                $list[$ctr]['products'] = $products;

                $ctr2 = 0;
                $tprice[$c] = 0;
                $tkg[$c] = 0;
                $tpc[$c] = 0;
                $tali[$c] = 0;
                foreach($list[$ctr]['products'] as $p){
                    $list[$ctr]['products'][$ctr2]['order_details'] = OrderDetails::whereIn('order_id',$request->order_ids)->where('product_id',$p->product_id)->get();
                    $list[$ctr]['products'][$ctr2]['total'] = OrderDetails::whereIn('order_id',$request->order_ids)->where('product_id',$p->product_id)->sum('total_price');
                    $list[$ctr]['products'][$ctr2]['total_kg'] = OrderDetails::whereIn('order_id',$request->order_ids)->where('product_id',$p->product_id)->sum('qty');
                    $final_total += $list[$ctr]['products'][$ctr2]['total'];
                    // $total_kg += $list[$ctr]['products'][$ctr2]['total_kg'];

                    $prc += $list[$ctr]['products'][$ctr2]['total'];
                    $tprice[$c] += $list[$ctr]['products'][$ctr2]['total'];
                    if($p->unit == 'kg'){
                        $total_kg += $list[$ctr]['products'][$ctr2]['total_kg'];
                        $tkg[$c] += $list[$ctr]['products'][$ctr2]['total_kg'];
                    }
                    if($p->unit == 'pc'){
                        $total_pc += $list[$ctr]['products'][$ctr2]['total_kg'];
                        $tpc[$c] += $list[$ctr]['products'][$ctr2]['total_kg'];
                    }
                    if($p->unit == 'tali'){
                        $total_tali += $list[$ctr]['products'][$ctr2]['total_kg'];
                        $tali[$c] += $list[$ctr]['products'][$ctr2]['total_kg'];
                    }
                    
                    if($c == 6){
                        $delivery_charge += $list[$ctr]['products'][$ctr2]['total'];
                    }
                    if($c == 7){
                        $price_total2 += $list[$ctr]['products'][$ctr2]['total'];
                        $kg_total2 += $list[$ctr]['products'][$ctr2]['total_kg'];
                    }
                    $ctr2++;
                }
                $list[$ctr]['total_price'] = $prc;
                // $list[$ctr]['total_kg'] = $kg;
                // $list[$ctr]['total_pc'] = $pc;
                // $list[$ctr]['total_tali'] = $t;
                $ctr++;
            }

            $response['data'] = $list;
            $response['total'] = $final_total;
            $response['total_kg'] = $total_kg;
            $response['total_pc'] = $total_pc;
            $response['total_tali'] = $total_tali;
            $response['tprice'] = $tprice;
            $response['tkg'] = $tkg;
            $response['tpc'] = $tpc;
            $response['tali'] = $tali;
            $response['price_total2'] = $price_total2;
            $response['kg_total2'] = $kg_total2;
            $response['delivery_charge'] = $delivery_charge;
            $response['status'] = 'Success';
            $response['code'] = 200;
        }

        return Response::json($response);
    }
}

<?php

namespace App\Http\Controllers;

use App\User;
use App\ContactNumber;
use App\Order;
use App\OrderDetails;
use App\Product;
use App\ProductCategory;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use Response;
use Validator;
use Hash, DB;

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


    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'name' => 'required',
            'address' => 'required',
            'contact' => 'required',
            'date_of_delivery' => 'required',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $client_id = $request->client_id;
            if(!$request->client_id || $request->client_id == ''){
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

                $user->contactNumbers()->delete();
                $num = new ContactNumber;
                $num->user_id = $user->id;
                $num->number = $request->contact;
                $num->save();
            }


            $order = new Order;
            $order->name = $request->name;
            $order->last_name = $request->last_name;
            $order->user_id = $client_id;
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
            'contact' => 'required',
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
            $order->wechat_id = $request->wechat_id;
            $order->telegram = $request->telegram;
            $order->address = $request->address;
            $order->contact = $request->contact;
            $order->date_of_delivery = $request->date_of_delivery;
            $order->remarks = $request->remarks;
            $order->save();

            OrderDetails::where('order_id',$id)->delete();
            foreach($request->products as $p){
                $order_detail = new OrderDetails;
                $order_detail->order_id = $id;
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

    public function delete($id){
            Order::where('order_id',$id)->delete();
            OrderDetails::where('order_id',$id)->delete();
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

            $order = Order::where('order_id',$request->order_id)->first();
            $is_delivered = ($request->is_delivered == 'no' ? 0 : 1);
            $order->is_delivered = $is_delivered;
            // $order->remarks = $request->remarks;
            $order->money_received = $request->money_received;
            $order->delivered_by = $request->delivered_by;
            $order->save();
            $response['status'] = 'Success';
            $response['code'] = 200;
        }

        return Response::json($response);
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
            $prod->product_price = $request->price;
            $prod->product_name = $request->name;
            $prod->product_name_chinese = $request->name_chinese;
            $prod->save();
            $response['status'] = 'Success';
            $response['code'] = 200;
        }

        return Response::json($response);
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

            foreach($cats as $c){
                $list[$ctr]['category'] = ProductCategory::where('category_id',$c)->first()->name;

                $products = Product::whereIn('product_id',$prod_ids)->where('category_id',$c)->get();

                $ps = $products->pluck('product_id');
                $list[$ctr]['orders'] = OrderDetails::whereIn('order_id',$request->order_ids)->whereIn('product_id',$ps)->orderBy('order_id')->groupBy('order_id')->pluck('order_id');

                $list[$ctr]['products'] = $products;

                $ctr2 = 0;
                foreach($list[$ctr]['products'] as $p){
                    $list[$ctr]['products'][$ctr2]['order_details'] = OrderDetails::whereIn('order_id',$request->order_ids)->where('product_id',$p->product_id)->get();
                    $list[$ctr]['products'][$ctr2]['total'] = OrderDetails::whereIn('order_id',$request->order_ids)->where('product_id',$p->product_id)->sum('total_price');
                    $list[$ctr]['products'][$ctr2]['total_kg'] = OrderDetails::whereIn('order_id',$request->order_ids)->where('product_id',$p->product_id)->sum('qty');
                    $final_total += $list[$ctr]['products'][$ctr2]['total'];
                    $total_kg += $list[$ctr]['products'][$ctr2]['total_kg'];
                    $ctr2++;
                }
                $ctr++;
            }

            $response['data'] = $list;
            $response['total'] = $final_total;
            $response['total_kg'] = $total_kg;
            $response['status'] = 'Success';
            $response['code'] = 200;
        }

        return Response::json($response);
    }
}

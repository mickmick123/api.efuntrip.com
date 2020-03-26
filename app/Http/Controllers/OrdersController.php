<?php

namespace App\Http\Controllers;

use App\User;
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

    public function list() {
        
        $orders = Order::orderBy('order_id','Desc')->get();

        foreach($orders as $o){
            $total_price = OrderDetails::where('order_id',$o->order_id)->where('order_status',1)->sum('total_price');
            $o->total_price = $total_price;
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
            $d->product_name = $prod->product_name;
            $d->product_name_chinese = $prod->product_name_chinese;
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
            'delivered_by' => 'required',
            'contact' => 'required',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $order = new Order;
            $order->name = $request->name;
            $order->delivered_by = $request->delivered_by;
            $order->address = $request->address;
            $order->contact = $request->contact;
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
            'address' => 'required',
            'delivered_by' => 'required',
            'contact' => 'required',
        ]);

        if($validator->fails()) {
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;
        } else {
            $order = Order::where('order_id',$id)->first();
            $order->name = $request->name;
            $order->delivered_by = $request->delivered_by;
            $order->address = $request->address;
            $order->contact = $request->contact;
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
}

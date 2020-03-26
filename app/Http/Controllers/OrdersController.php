<?php

namespace App\Http\Controllers;

use App\User;
use App\Order;
use App\OrderDetails;
use App\Product;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use Response;
use Validator;
use Hash, DB;

use Carbon\Carbon;

class OrdersController extends Controller
{

    public function list() {
        
        $orders = Order::get();

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


}

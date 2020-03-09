<?php

namespace App\Http\Controllers;

use App\Action;

use Response;

use Illuminate\Http\Request;

class ActionController extends Controller
{
    
    public function index() {
		$response['status'] = 'Success';
		$response['data'] = [
		    'actions' =>  Action::with(['categories' => function($query) {
		    		$query->orderBy('name');
		    	}])->orderBy('order_of_precedence')->get()
		];
		$response['code'] = 200;

		return Response::json($response);
	}

}

<?php

namespace App\Http\Controllers;

use App\Form;

use Response;

use Illuminate\Http\Request;

class FormController extends Controller
{
    
	public function index() {
		$response['status'] = 'Success';
		$response['data'] = [
		    'forms' => Form::all()
		];
		$response['code'] = 200;

		return Response::json($response);
	}

}

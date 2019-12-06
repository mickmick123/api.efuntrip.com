<?php

namespace App\Http\Controllers;

use App\Country;

use Response;

use Illuminate\Http\Request;

class CountryController extends Controller
{
    
    public function index() {
		$response['status'] = 'Success';
		$response['data'] = [
		    'countries' => Country::all()
		];
		$response['code'] = 200;

		return Response::json($response);
	}

	public function show($id) {
		$response['status'] = 'Success';
		$response['data'] = [
		    'country' => Country::findOrFail($id)
		];
		$response['code'] = 200;

		return Response::json($response);
	}

}

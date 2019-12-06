<?php

namespace App\Http\Controllers;

use App\Nationality;

use Response;

use Illuminate\Http\Request;

class NationalityController extends Controller
{
    
    public function index() {
		$response['status'] = 'Success';
		$response['data'] = [
		    'nationalities' => Nationality::all()
		];
		$response['code'] = 200;

		return Response::json($response);
	}

	public function show($id) {
		$response['status'] = 'Success';
		$response['data'] = [
		    'nationality' => Nationality::findOrFail($id)
		];
		$response['code'] = 200;

		return Response::json($response);
	}

}

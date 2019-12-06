<?php

namespace App\Http\Controllers;

use App\Branch;

use Response;

use Illuminate\Http\Request;

class BranchController extends Controller
{
    
	public function index() {
		$response['status'] = 'Success';
		$response['data'] = [
		    'branches' => Branch::all()
		];
		$response['code'] = 200;

		return Response::json($response);
	}

	public function show($id) {
		$response['status'] = 'Success';
		$response['data'] = [
		    'branch' => Branch::findOrFail($id)
		];
		$response['code'] = 200;

		return Response::json($response);
	}

}

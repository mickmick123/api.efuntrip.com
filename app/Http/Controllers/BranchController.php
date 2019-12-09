<?php

namespace App\Http\Controllers;

use App\Branch;

use Response, Validator;

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

	public function update(Request $request, $id) {
		$validator = Validator::make($request->all(), [ 
            'name' => 'required|unique:branches,name,'.$id
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;   
        } else {
        	$branch = Branch::find($id);

        	if( $branch ) {
        		$branch->update(['name' => $request->name]);

        		$response['status'] = 'Success';
        		$response['code'] = 200;
        	} else {
        		$response['status'] = 'Failed';
        		$response['errors'] = 'No query results.';
				$response['code'] = 404;
        	}
        }

        return Response::json($response);
	}

	public function store(Request $request) {
		$validator = Validator::make($request->all(), [ 
            'name' => 'required|unique:branches,name'
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;   
        } else {
        	Branch::create(['name' => $request->name]);

        	$response['status'] = 'Success';
			$response['code'] = 200;
        }

        return Response::json($response);
	}

	public function destroy($id) {
		$branch = Branch::find($id);

		if( $branch ) {
			$branch->delete();

			$response['status'] = 'Success';
        	$response['code'] = 200;
		} else {
			$response['status'] = 'Failed';
        	$response['errors'] = 'No query results.';
			$response['code'] = 404;
		}

		return Response::json($response);
	}

}

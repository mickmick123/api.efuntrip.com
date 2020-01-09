<?php

namespace App\Http\Controllers;

use App\Branch;

use App\User;

use Response, Validator;

use Illuminate\Http\Request;

class BranchController extends Controller
{
    
	public function index() {
		$data = Branch::all();

		$branches = [];
		foreach($data as $index => $d) {
			$branchId = $d->id;

			$numberOfClients = User::whereHas('roles', function($query) {
					$query->where('roles.name', 'visa-client');
				})
				->whereHas('branches', function($query) use($branchId) {
					$query->where('branches.id', $branchId);
				})
				->count();

			$branches[$index] = $d;

			$branches[$index]['number_of_clients'] = $numberOfClients;
		}

		$response['status'] = 'Success';
		$response['data'] = [
		    'branches' => $branches
		];
		$response['code'] = 200;

		return Response::json($response);
	}

	public function show($id) {
		$branch = Branch::find($id);

		if( $branch ) {
			$response['status'] = 'Success';
			$response['data'] = [
			    'branch' => $branch
			];
			$response['code'] = 200;
		} else {
			$response['status'] = 'Failed';
        	$response['errors'] = 'No query results.';
			$response['code'] = 404;
		}

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

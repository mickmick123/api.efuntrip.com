<?php

namespace App\Http\Controllers;

use App\ServiceProfile;

use Response, Validator;

use Illuminate\Support\Str;

use Illuminate\Http\Request;

class ServiceProfileController extends Controller
{
    
    public function index() {
    	$response['status'] = 'Success';
		$response['data'] = [
		    'service_profiles' => ServiceProfile::where('is_active', 1)->orderBy('name')->get()
		];
		$response['code'] = 200;

		return Response::json($response);
    }

    public function store(Request $request) {
    	$validator = Validator::make($request->all(), [ 
            'name' => 'required|unique:service_profiles,name',
            'is_active' => 'required'
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;   
        } else {
        	ServiceProfile::create([
        		'name' => $request->name,
        		'slug' => Str::slug($request->name, '-'),
        		'is_active' => $request->is_active
        	]);

        	$response['status'] = 'Success';
			$response['code'] = 200;
        }

        return Response::json($response);
    }

    public function show($id) {
    	$serviceProfile = ServiceProfile::find($id);

		if( $serviceProfile ) {
			$response['status'] = 'Success';
			$response['data'] = [
			    'service_profile' => $serviceProfile
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
            'name' => 'required|unique:service_profiles,name,'.$id
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;   
        } else {
        	$serviceProfile = ServiceProfile::find($id);

        	if( $serviceProfile ) {
        		$serviceProfile->update(['name' => $request->name]);

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

    public function destroy($id) {
		$serviceProfile = ServiceProfile::find($id);

		if( $serviceProfile ) {
			$serviceProfile->update(['is_active' => 0]);

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

<?php

namespace App\Http\Controllers;

use App\Device;
use App\User;

use Auth, Hash, Response, Validator;

use Illuminate\Http\Request;

class UserController extends Controller
{
    
	public function login(Request $request) {
		$validator = Validator::make($request->all(), [ 
            'email' => 'required|email',
            'password' => 'required',
            'source' => 'required',
            'device_type' => 'required_if:source,mobile',
            'device_token' => 'required_if:source,mobile'
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;   
        } else {
        	$user = User::where('email', $request->email)->first();

        	if( $user ) {
        		if( Hash::check($request->password, $user->password) ) {
        			if( $request->source == 'mobile' ) {
        				Device::updateOrCreate(
        					['user_id' => $user->id, 'device_type' => $request->device_type, 'device_token' => $request->device_token],
        					[]
        				);
        			}

		            $token = $user->createToken('WYC Visa')->accessToken;

		            $response['status'] = 'Success';
		            $response['data'] = [
		            	'token' => $token
		            ];
		            $response['code'] = 200;
		        } else {
		            $response['status'] = 'Failed';
	            	$response['errors'] = 'Invalid email/password.';
	            	$response['code'] = 422;
		        }
        	} else {
        		$response['status'] = 'Failed';
            	$response['errors'] = 'Invalid email/password.';
            	$response['code'] = 422;   
        	}
        }

        return Response::json($response);
	}

	public function logout(Request $request) {
		$validator = Validator::make($request->all(), [ 
            'source' => 'required',
            'device_type' => 'required_if:source,mobile',
            'device_token' => 'required_if:source,mobile'
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;   
        } else {
        	if( $request->source == 'mobile' ) {
        		Device::where('user_id', Auth::user()->id)->where('device_type', $request->device_type)->where('device_token', $request->device_token)->delete();
        	}

        	$user = Auth::guard('api')->user()->token();
			$user->revoke();

		    $response['status'] = 'Success';
			$response['code'] = 200;
        }

		return Response::json($response);
	}

	public function userInformation() {
		$response['status'] = 'Success';
		$response['data'] = [
			'information' => User::with('branches')->findorfail(Auth::user()->id)
		];
		$response['code'] = 200;

		return Response::json($response);
	}

}

<?php

namespace App\Http\Controllers;

use App\User;

use Auth, Hash, Response, Validator;

use Illuminate\Http\Request;

class UserController extends Controller
{
    
	public function login(Request $request) {
		$validator = Validator::make($request->all(), [ 
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if($validator->fails()) {       
            $response['status'] = 'Failed';
            $response['errors'] = $validator->errors();
            $response['code'] = 422;   
        } else {
        	$user = User::where('email', $request->email)->first();

        	if( $user ) {
        		if( Hash::check($request->password, $user->password) ) {
		            $token = $user->createToken('WYC Visa')->accessToken;

		            $response['status'] = 'Success';
		            $response['data'] = [
		            	'token' => $token,
		            	'user' => $user
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

	public function logout() {
		$user = Auth::guard('api')->user()->token();
		$user->revoke();

	    $response['status'] = 'Success';
		$response['code'] = 200;

		return Response::json($response);
	}

	public function userInformation() {
		$response['status'] = 'Success';
		$response['data'] = Auth::user();
		$response['code'] = 200;

		return Response::json($response);
	}

}

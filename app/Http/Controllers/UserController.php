<?php

namespace App\Http\Controllers;

use App\Device;
use App\User;
use App\ContactNumber;
use App\Role;
use App\RoleUser;
use App\PermissionRole;
use App\Permission;

use Auth, Hash, Response, Validator;

use Illuminate\Http\Request;

class UserController extends Controller
{
    
	public function login(Request $request) {
		$validator = Validator::make($request->all(), [ 
            'email' => 'required',
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
            $login = $request->email;
            $result = filter_var( $login, FILTER_VALIDATE_EMAIL);

            if(!$result){
                preg_match_all('!\d+!', $login, $matches);
                $login = implode("", $matches[0]);
                $login = ltrim($login,"0");
                $login = ltrim($login,'+');
                $login = ltrim($login,'63');

                if(is_numeric($login)){
                    $ids = ContactNumber::where('number','like','%'.$login)->where('user_id','!=',null)->pluck('user_id');
                    $user = User::whereIn('id', $ids)->get();
                }else{
                    $user = NULL; 
                }
                
            }
            else{
        	   $user = User::where('email', $request->email)->get();
            }

            $count = 0;

        	if( $user ) {
                foreach($user as $u){
                    $client = User::findorFail($u->id);
            		if( Hash::check($request->password, $u->password) && ($client->hasRole('cpanel-admin') || $client->hasRole('master') || $client->hasRole('employee'))) {
            			if( $request->source == 'mobile' ) {
            				Device::updateOrCreate(
            					['user_id' => $u->id, 'device_type' => $request->device_type, 'device_token' => $request->device_token],
            					[]
            				);
            			}

    		            $token = $u->createToken('WYC Visa')->accessToken;

    		            $response['status'] = 'Success';
    		            $response['data'] = [
    		            	'token' => $token
    		            ];
    		            $response['code'] = 200;
                        $count++;
    		        } 
                }  
		        
            }
            else {
        		$response['status'] = 'Failed';
            	$response['errors'] = 'Invalid username/password.';
            	$response['code'] = 401;   
        	}
        }

        if($count == 0){
            $response['status'] = 'Failed';
            $response['errors'] = 'Invalid username/password.';
            $response['code'] = 401;
        }

        // if($client->hasRole('cpanel-admin') || $client->hasRole('master') || $client->hasRole('employee')){
        //     $response['status'] = 'Failed';
        //     $response['errors'] = 'No Access';
        //     $response['code'] = 200;
        // }

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
        $user = User::with('branches')->findorfail(Auth::user()->id);

        $roles = RoleUser::where('user_id', $user->id)->pluck('role_id');
        $perm = PermissionRole::whereIn('role_id',$roles)->groupBy('permission_id')->pluck('permission_id');
        $permissions = Permission::whereIn('id',$perm)->pluck('name');
        $user->permissions = $permissions;
		$response['status'] = 'Success';
		$response['data'] = [
			'information' => $user
		];
		$response['code'] = 200;

		return Response::json($response);
	}

}

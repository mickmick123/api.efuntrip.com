<?php

namespace App\Http\Controllers;

use App\User;

use App\ContactNumber;

use App\Device;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use Response;
use Validator;
use Hash, DB;

use Carbon\Carbon;

class AppController extends Controller
{
    
    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
            'device_token' => 'required',
            'device_id' => 'required',
            'device_type' => 'required',
        ]);
       
        $login = $request->username;
        $result = filter_var( $login, FILTER_VALIDATE_EMAIL);

        if(!$result){
            preg_match_all('!\d+!', $login, $matches);
            $login = implode("", $matches[0]);
            $login = ltrim($login,"0");
            $login = ltrim($login,'+');
            $login = ltrim($login,'63');

            if(is_numeric($login)){
                
                $ids = ContactNumber::where('number','like','%'.$login)->where('user_id','!=',null)->pluck('user_id');
                $user = User::whereIn('id', $id)->get();
            }else{
                $user = NULL; 
            }
            
        }
        else{
            $user = User::where('email', $login)->get();
        }

        $response = [];

        if( $validator->fails() ) {
            $response['status'] = 'Failed';
            $response['desc'] = $validator->errors();
            $httpStatusCode = 200; // Request Error
        }
        else{
            if($user) {
                foreach($user as $u){
                    if (Hash::check($request->password, $u->password)) {
                        $client = User::findorFail($u->id)->makeVisible('access_token');

                        Device::updateOrCreate(
                            ['user_id' => $client->id, 'device_type' => $request->device_type, 'device_token' => $request->device_token],
                            []
                        );
                        $token = $client->createToken('WYC Visa')->accessToken;

                        $is_new_user = 0;

                        $cnum = ContactNumber::where('user_id',$client->id)->where('is_primary',1)->first();
                        // $cnum = $user->contact_number;
                        if($cnum){
                            preg_match_all('!\d+!', $cnum, $matches);
                            $cnum = implode("", $matches[0]);
                            $cnum = ltrim($cnum,"0");
                            $cnum = ltrim($cnum,'+');
                            $cnum = ltrim($cnum,'63');
                            $cnum = "0".$cnum;
                            if (Hash::check($cnum,  $u->password)) {
                                $is_new_user = 1;
                            }
                        }

                        $numbers =  ContactNumber::where('user_id',$client->id)->get();

                        //response
                        $response['id'] = $client->id;
                        $response['email'] = $client->email;
                        $response['numbers'] = $numbers;
                        $response['token'] = $request->device_token;
                        $response['device_id'] = $request->device_id;
                        $response['device_type'] = $request->device_type;
                        $response['active'] = 1;
                        $response['access_token'] = $token;
                        $response['is_new_user'] = $is_new_user;

                        $admin = 0;
                        if($client->hasRole('cpanel-admin') || $client->hasRole('master') || $client->hasRole('employee')){
                            $admin = 1;
                        }
                        $vclient =0;
                        if($client->hasRole('visa-client')){
                            $vclient = 1;
                        }
                        $response['admin'] = $admin;
                        $response['client'] = $vclient;
                        $response['roles'] = $client->rolesname->pluck('name');
                        $response['status'] = 'Success';
                        $response['code'] = 200;

                        return Response::json($response);
                    } 
                }
                    $response['status'] = 'Failed';
                    $response['desc'] = 'Client authentication failed';
                    $response['code'] = 422;                

            } else {
                $response['status'] = 'Failed';
                $response['desc'] = 'Client authentication failed';
                $response['code'] = 422;
            }
        }
        return Response::json($response);

    }

}

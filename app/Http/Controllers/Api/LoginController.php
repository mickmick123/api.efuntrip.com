<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller{
    public function login(Request $request){
        if(Auth::attempt(['number'=>$request->email,'password'=>$request->pass])){
            $user=Auth::user();
            $http=new Client;
            $response = $http->post(config('api.domain').'/oauth/token', [
                'form_params' => [
                    'grant_type' =>config('api.grant_type'),
                    'client_id' =>config('api.client_id'),
                    'client_secret' =>config('api.client_secret'),
                    'username' =>$request->email,
                    'password' =>$request->pass,
                    'scope' => config('api.scope'),//授权应用程序支持的所有范围的令牌
                ],
            ]);
            $data['code']=200;
            $data['msg']='用户登录成功';
            $data['name']=$user->name;
            $data['number']=$user->number;
            $data['identity']=$user->identity;
            $data['img']=$user->img;
            $data['balance']=$user->balance;
            $data['token']=json_decode( (string) $response->getBody());
            return response()->json($data);
        }
        if(Auth::attempt(['email'=>$request->email,'password'=>$request->pass])){
            $user=Auth::user();
            $http=new Client;
            $response = $http->post(config('api.domain').'/oauth/token', [
                'form_params' => [
                    'grant_type' =>config('api.grant_type'),
                    'client_id' =>config('api.client_id'),
                    'client_secret' =>config('api.client_secret'),
                    'username' =>$request->email,
                    'password' =>$request->pass,
                    'scope' =>config('api.scope'),//授权应用程序支持的所有范围的令牌
                ],
            ]);
            $data['code']=200;
            $data['msg']='用户登录成功';
            $data['name']=$user->name;
            $data['number']=$user->number;
            $data['identity']=$user->identity;
            $data['img']=$user->img;
            $data['balance']=$user->balance;
            $data['token']=json_decode( (string) $response->getBody());
            return response()->json($data);
        }
    }
    public function register(Request $request){
        $validator =Validator::make($request->all(),[
            'nickName'       =>'required',
            'email'          =>'required|email|unique:users',
            'pass'           =>'required',
            'checkPass'      =>'required|same:pass',
            'number'         =>'required',
        ]);
        if($validator->fails()) {
            return response()->json(['error'=>$validator->errors()]);
        }
        $user=User::create([
            "name"=>$request->nickName,
            "password"=>bcrypt($request->pass),
            "number"=>$request->number,
            "email"=>$request->email,
            "identity"=>$request->identity,
            "rate"=>$request->rate,
        ]);
        $data['code']=200;
        $data['msg']='用户注册成功';
        $data['data']=$user;
        return response()->json($data);
    }
}

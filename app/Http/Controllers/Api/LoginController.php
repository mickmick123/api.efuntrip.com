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
            $data['name']=$user->name;
            $data['number']=$user->number;
            $data['identity']=$user->identity;
            $data['img']=$user->img;
            $data['balance']=$user->balance;
            $data['token']=json_decode( (string) $response->getBody());
            return $this->jsonReturn(200,'User login succeeded/用户登录成功',$data);
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
            $data['name']=$user->name;
            $data['number']=$user->number;
            $data['identity']=$user->identity;
            $data['img']=$user->img;
            $data['balance']=$user->balance;
            $data['token']=json_decode( (string) $response->getBody());
            return $this->jsonReturn(200,'User login succeeded/用户登录成功',$data);
        }else{
            return $this->jsonReturn(402,'wrong password/密码错误','wrong password/密码错误');
        }
    }
    public function register(Request $request){
        if(Auth::user()['identity']=='administrator'){
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
            if($request->identity=='administrator'){
                return $this->jsonReturn('401','Insufficient permissions/权限不足','Insufficient permissions/权限不足');
            }
            $user=User::create([
                "name"=>$request->nickName,
                "password"=>bcrypt($request->pass),
                "number"=>$request->number,
                "email"=>$request->email,
                "identity"=>$request->identity,
                "rate"=>$request->rate,
            ]);
            return $this->jsonReturn('200','User registration is successful/用户注册成功',$user);
        }else{
            return $this->jsonReturn('401','Insufficient permissions/权限不足','Insufficient permissions/权限不足');
        }
    }
}

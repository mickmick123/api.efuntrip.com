<?php

namespace App\Http\Controllers\Api;

use GuzzleHttp\Client;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller{
    public function getAllUserInfo(){
        if(Auth::user()['identity']=='administrator'){
            $user=User::where('id','!=',Auth::user()->id)->get();
            return $this->jsonReturn(200,'Get all user information successfully/成功获取所有用户信息',$user);
        }elseif(Auth::user()['identity']=='admin'){
            return $this->jsonReturn(401,'Insufficient permissions/权限不足','Insufficient permissions/权限不足');
        }elseif(Auth::user()['identity']=='user'){
            $user=User::where('id','!=',Auth::user()->id)->get();
            return $this->jsonReturn(200,'Get all user information successfully/成功获取所有用户信息',$user);
        }
    }
    public function getCurrentUser(){
        if($user=Auth::user()){
            return $this->jsonReturn(200,'Get the current user success/获取当前用户信息成功',$user);
        }else{
            return $this->jsonReturn(401,'Failed to get current user/无法获取当前用户信息','Failed to get current user/无法获取当前用户信息');
        }
    }
    public function modifyCurrentUserInformation(Request $request){
        if(Auth::user()['identity']=='administrator'){
            $validator=Validator::make($request->all(),[
                'id'        =>'required',
            ]);
            if($validator->fails()) {
                return response()->json(['error'=>$validator->errors()]);
            }
            if(User::where('id',$request->id)
                ->update([
                    'name'=>$request->name,
                    'number'=>$request->number,
                    'email'=>$request->email,
                    'rate'=>$request->rate,
                ])){
                return $this->jsonReturn('200','Successfully modified/修改成功','Successfully modified/修改成功');
            };
        }else{
            return $this->jsonReturn('401','Insufficient permissions/权限不足','Insufficient permissions/权限不足');
        }
    }

}

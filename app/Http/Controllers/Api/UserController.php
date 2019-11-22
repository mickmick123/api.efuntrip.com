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
        if(Auth::user()['identity']=='admin'){
            $user=User::all();
            return response()->json($user,200);
        }else{
            return response()->json(Auth::user(),201);
        }
    }
    public function getCurrentUser(){
        if($user=Auth::user()){
            return response()->json($user,200);
        }
    }
    public function modifyCurrentUserInformation(Request $request){
        if(Auth::user()['identity']=='admin'){
            $validator=Validator::make($request->all(),[
                'id'        =>'required',
            ]);
            if($validator->fails()) {
                return response()->json(['error'=>$validator->errors()]);
            }
            return User::where('id',$request->id)
                ->update([
                    'name'=>$request->name,
                    'number'=>$request->number,
                    'email'=>$request->email,
                    'rate'=>$request->rate,
                ]);
        }
    }
}

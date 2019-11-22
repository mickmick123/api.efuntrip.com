<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/
//login   登录
Route::post('login','Api\LoginController@login');
//register   注册
Route::post('register','Api\LoginController@register');
Route::group(['middleware'=>'auth:api'],function(){
    //得到当前的user信息
    Route::post('getCurrentUser','Api\UserController@getCurrentUser');
    //得到所有的user信息
    Route::post('getAllUserInfo','Api\UserController@getAllUserInfo');
    //得到交易日志
    Route::post('getTheTransactionLog','Api\CashflowController@getTheTransactionLog');
    //得到服务费流水日志
    Route::post('getServiceFeeFlowLog','Api\CashflowController@getServiceFeeFlowLog');
    //添加交易日志
    Route::post('addTheTransactionLog','Api\CashflowController@addTheTransactionLog');
    //得到所有的用户
    Route::post('getAllUserInfo','Api\CashflowController@getAllUserInfo');
    //修改当前用户的信息
    Route::post('modifyCurrentUserInformation','Api\UserController@modifyCurrentUserInformation');
});

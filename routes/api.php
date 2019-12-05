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

Route::prefix('v1')->group(function() {

	Route::post('login', 'UserController@login');

	Route::middleware('auth:api')->group(function() {

		Route::get('user-information', 'UserController@userInformation');

		Route::get('logout', 'UserController@logout');

	});

});

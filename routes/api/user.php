<?php

use Illuminate\Http\Request;

Route::post('login', 'UserController@login');
Route::post('add-user', 'UserController@addUser');

Route::middleware('auth:api')->group(function() {
	
	Route::get('internal-users', 'UserController@getInternalUsers');

	Route::get('user-information', 'UserController@userInformation');

	Route::post('logout', 'UserController@logout');

});
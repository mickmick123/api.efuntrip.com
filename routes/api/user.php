<?php

use Illuminate\Http\Request;

Route::post('login', 'UserController@login');

Route::middleware('auth:api')->group(function() {

	Route::get('user-information', 'UserController@userInformation');

	Route::post('logout', 'UserController@logout');

});
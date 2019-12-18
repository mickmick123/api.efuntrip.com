<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('manage-services', 'ServiceController@manageServices');

	Route::get('getParentServices', 'ServiceController@getParentServices');

	Route::post('/', 'ServiceController@store');

	Route::get('{id}', 'ServiceController@show');

	Route::patch('{id}', 'ServiceController@update');

	Route::delete('{id}', 'ServiceController@destroy');

});
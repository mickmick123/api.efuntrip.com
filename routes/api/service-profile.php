<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('/', 'ServiceProfileController@index');

	Route::post('/', 'ServiceProfileController@store');

	Route::get('{id}', 'ServiceProfileController@show');

	Route::patch('{id}', 'ServiceProfileController@update');

	Route::delete('{id}', 'ServiceProfileController@destroy');

  Route::get('get-users-groups/{id}', 'ServiceProfileController@getUsersGroups');

});
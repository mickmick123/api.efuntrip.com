<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('/', 'DocumentController@index');

	Route::post('/', 'DocumentController@store');

	Route::get('{id}', 'DocumentController@show');

	Route::patch('{id}', 'DocumentController@update');

	Route::delete('{id}', 'DocumentController@destroy');

});
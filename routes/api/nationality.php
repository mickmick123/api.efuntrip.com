<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('/', 'NationalityController@index');

	Route::get('{id}', 'NationalityController@show');

});
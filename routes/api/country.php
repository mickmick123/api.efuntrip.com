<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('/', 'CountryController@index');

	Route::get('{id}', 'CountryController@show');

});
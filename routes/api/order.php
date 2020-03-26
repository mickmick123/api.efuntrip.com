<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('/list', 'OrdersController@list');

	Route::get('{id}', 'OrdersController@show');

});
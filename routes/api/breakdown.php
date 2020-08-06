<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::post('/update-price', 'BreakdownController@updatePrice');
	Route::post('/', 'BreakdownController@store');

});
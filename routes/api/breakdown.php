<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::post('/update-price', 'BreakdownController@updatePrice');
	Route::patch('/profile-switch', 'BreakdownController@profileSwitch');
	Route::post('/', 'BreakdownController@store');

});
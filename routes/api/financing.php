<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::post('/', 'FinancingController@store');

	Route::patch('/{finance_id}', 'FinancingController@update');

	Route::get('/show/{date}/{branch_id}', 'FinancingController@show');

	Route::get('/get-borrowed/{trans_type}/{branch_id}', 'FinancingController@getBorrowed');

	Route::get('/get-req-users', 'FinancingController@getRequestingUsers');

});
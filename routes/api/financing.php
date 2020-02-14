<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('/show/{date}/{branch_id}', 'FinancingController@show');

});
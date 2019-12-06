<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('/', 'BranchController@index');

	Route::get('{id}', 'BranchController@show');

});
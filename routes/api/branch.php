<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('/', 'BranchController@index');

	Route::post('/', 'BranchController@store');

	Route::get('{id}', 'BranchController@show');

	Route::patch('{id}', 'BranchController@update');

	Route::delete('{id}', 'BranchController@destroy');

});
<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('/', 'RoleController@index');

    Route::get('get-role/{perPage?}', 'RoleController@getRole');
    Route::post('add-role', 'RoleController@addRole');
});

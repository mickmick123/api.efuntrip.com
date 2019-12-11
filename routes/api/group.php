<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('manage-groups', 'GroupController@manageGroups');

	Route::post('/', 'GroupController@store');

	Route::patch('{id}', 'GroupController@update');

});
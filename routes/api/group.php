<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('manage-groups', 'GroupController@manageGroups');

	Route::get('manage-groups-paginate/{perPage?}', 'GroupController@manageGroupsPaginate');

	Route::get('search', 'GroupController@groupSearch');

	Route::post('assign-role', 'GroupController@assignRole');

	Route::post('/', 'GroupController@store');

	Route::get('{id}', 'GroupController@show');

	Route::patch('{id}/update-risk', 'GroupController@updateRisk');
	
	Route::patch('{id}', 'GroupController@update');

});
<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('manage-groups', 'GroupController@manageGroups');

	Route::get('manage-groups-paginate/{perPage?}', 'GroupController@manageGroupsPaginate');

	Route::get('search', 'GroupController@groupSearch');

	Route::post('assign-role', 'GroupController@assignRole');

	Route::post('/', 'GroupController@store');

	Route::post('add-members', 'GroupController@addMembers'); //Adding members using member ids and group id

	Route::post('add-group-services', 'GroupController@addGroupServices'); //Adding members using member ids and group id


	Route::get('member-packages/{client_id}/{group_id?}', 'GroupController@getClientPackagesByGroup');

	Route::get('members/{id}/{page?}', 'GroupController@members');
	Route::get('packages-bybatch/{group_id}/{page?}', 'GroupController@getClientPackagesByBatch');
	Route::get('packages-byservice/{group_id}/{page?}', 'GroupController@getClientPackagesByService');

	Route::get('{id}', 'GroupController@show');

	Route::patch('{id}/update-risk', 'GroupController@updateRisk');

	Route::patch('{id}', 'GroupController@update');

});

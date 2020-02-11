<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('manage-groups', 'GroupController@manageGroups');

	Route::get('manage-groups-paginate/{perPage?}', 'GroupController@manageGroupsPaginate');

	Route::get('search', 'GroupController@groupSearch');

	Route::post('assign-role', 'GroupController@assignRole');

	Route::post('/', 'GroupController@store');

	Route::post('add-members', 'GroupController@addMembers'); //Adding members using member ids and group id

	Route::post('add-services', 'GroupController@addServices'); //adding services using group id

	Route::patch('edit-services', 'GroupController@editServices');

	//Route::get('members-packages/{group_id}/{page?}', 'GroupController@getMembersPackages');
	Route::get('members-packages/{group_id}/{perPage?}', 'GroupController@getMembersPackages');

	Route::get('member-packages/{client_id}/{group_id?}', 'GroupController@getClientPackagesByGroup');

	Route::get('get-funds/{group_id?}/{page?}', 'GroupController@getFunds');

	Route::post('add-funds', 'GroupController@addFunds');

	Route::patch('update-group-commission/{id}', 'GroupController@updateGroupCommission');

	Route::get('members/{id}/{page?}', 'GroupController@members');
	Route::get('packages-bybatch/{group_id}/{page?}', 'GroupController@getClientPackagesByBatch');
	Route::get('packages-byservice/{group_id}/{page?}', 'GroupController@getClientPackagesByService');
	Route::get('unpaid-services/{group_id}/{is_auto_generated}/{page?}', 'GroupController@getUnpaidServices');


	Route::get('{id}', 'GroupController@show');

	Route::patch('{id}/update-risk', 'GroupController@updateRisk');

	Route::patch('{id}', 'GroupController@update');

	Route::post('delete-member', 'GroupController@deleteMember');

	Route::get('client-services/{client_id}/{group_id?}', 'GroupController@getClientServices');

	Route::post('transfer', 'GroupController@transfer');

	Route::post('add-service-payment', 'GroupController@addServicePayment');

	Route::get('switch-branch/{group_id}/{branch_id?}', 'GroupController@switchBranch');

	Route::get('switch-cost-level/{group_id}/{service_profile_id?}', 'GroupController@switchCostLevel');


});

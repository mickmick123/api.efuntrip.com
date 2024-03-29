<?php

use Illuminate\Http\Request;

	Route::get('manage-groups', 'GroupController@manageGroups');

  Route::middleware('auth:api')->group(function() {

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

 //Group By
  Route::get('members/{id}/{page?}', 'GroupController@members');
	Route::get('packages-bybatch/{group_id}/{page?}', 'GroupController@getClientPackagesByBatch');
	Route::get('packages-byservice/{group_id}/{page?}', 'GroupController@getClientPackagesByService');

	Route::get('current-batch-page/{group_id?}', 'GroupController@getCurrentBatchPage');


 //
	Route::post('by-batch-members', 'GroupController@getMembersByBatch');
	Route::post('by-members-service', 'GroupController@getMemberByService');
	Route::post('by-service-members', 'GroupController@getServicesByMembers');


	Route::post('by-group-services', 'GroupController@getServicesByGroup');


 //
  Route::post('distribute-old-payment', 'GroupController@distributeOldPayment');
  Route::post('distribute-old-payment-by-batch', 'GroupController@distributeClientOldPayment2');


	//Export Excel
	Route::get('byservice/{group_id}/{page?}', 'GroupController@getByService');
	// Route::get('clientbyservice/{client_id}', 'GroupController@getServicesByClient');
	Route::get('bybatch/{group_id}/{page?}', 'GroupController@getByBatch');
	Route::get('group-members/{id}/{page?}', 'GroupController@getMembers');

	Route::post('preview-report', 'GroupController@previewReport');

	Route::get('unpaid-services/{group_id}/{is_auto_generated}/{page?}', 'GroupController@getUnpaidServices');

	Route::get('get-service-dates/{group_id}', 'GroupController@showServiceDates');
	Route::get('get-service-added/{group_id}/{date}', 'GroupController@showServiceAdded');

	Route::get('{id}', 'GroupController@show');

	Route::patch('{id}/update-risk', 'GroupController@updateRisk');

	Route::patch('{id}', 'GroupController@update');

	Route::post('delete-member', 'GroupController@deleteMember');

	Route::post('client-services', 'GroupController@getClientServices');

	Route::get('client-services-by-id/{client_id}', 'GroupController@getServicesByClient');

	Route::post('client-services-by-id-excel', 'GroupController@getClientServiceSummary');
	
	Route::post('transfer', 'GroupController@transfer');
	Route::post('transfer-member', 'GroupController@transferMember');

	Route::post('checkif-member-exist', 'GroupController@checkIfMemberExist');


	Route::post('add-service-payment', 'GroupController@addServicePayment');

	Route::get('switch-branch/{group_id}/{branch_id?}', 'GroupController@switchBranch');

	Route::get('switch-cost-level/{group_id}/{service_profile_id?}', 'GroupController@switchCostLevel');

	Route::post('group-summary', 'GroupController@getGroupSummary');

	Route::post('add-group-payment', 'GroupController@addGroupPayment');

	Route::post('edit-service-payment', 'GroupController@editGroupPayment');

	Route::post('add-group-remark', 'GroupController@addGroupRemark');

	Route::post('get-group-history', 'GroupController@getGroupHistory');

	Route::post('get-fund-list', 'GroupController@getFundList');

    Route::post('export-funds-summary', 'GroupController@exportFundsSummary');

});

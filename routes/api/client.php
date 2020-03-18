<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('manage-clients', 'ClientController@manageClients');

	Route::get('get-pending-services/{perPage?}', 'ClientController@getPendingServices');

	Route::get('get-on-process-services/{perPage?}', 'ClientController@getOnProcessServices');

	Route::get('get-today-services/{perPage?}', 'ClientController@getTodayServices');

	Route::get('manage-clients-paginate/{perPage?}', 'ClientController@manageClientsPaginate');

	Route::get('get-clients-services/{id}/{tracking?}', 'ClientController@getClientServices');

	Route::get('get-clients-packages/{id}', 'ClientController@getClientPackages');

	Route::get('get-clients-groups/{id}', 'ClientController@getClientGroups');

    Route::get('search', 'ClientController@clientSearch');

    Route::get('get-reminders', 'ClientController@getReminders');

	Route::post('add-temporary-client', 'ClientController@addTemporaryClient');

	Route::post('add-client-service', 'ClientController@addClientService');

	Route::post('edit-client-service', 'ClientController@editClientService');

	Route::post('add-client-fund', 'ClientController@addClientFunds');

	Route::post('add-client-package', 'ClientController@addClientPackage');

	Route::post('delete-client-package', 'ClientController@deleteClientPackage');

	Route::post('/', 'ClientController@store');

	Route::get('{id}', 'ClientController@show');

	Route::patch('{id}/update-risk', 'ClientController@updateRisk');

	Route::patch('{id}', 'ClientController@update');

    Route::post('get-today-tasks', 'ClientController@getTodayTasks');

    Route::post('get-employee', 'ClientController@getEmployees');

	Route::get('unpaid-services/{group_id}/{is_auto_generated}/{page?}', 'ClientController@getUnpaidServices');

	Route::post('add-service-payment', 'ClientController@addServicePayment');

	Route::get('switch-client-cost-level/{client_id}/{service_profile_id?}', 'ClientController@switchCostLevel');

});

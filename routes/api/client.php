<?php

use Illuminate\Http\Request;
Route::get('manage-clients', 'ClientController@manageClients');

Route::get('get-all-users', 'ClientController@getAllUsers');

Route::get('get-contact-type', 'ClientController@getContactType');

Route::post('rsa-sign', 'ClientController@rsaSign');

Route::post('get-qr-data', 'ClientController@getQRData');

Route::middleware('auth:api','throttle:300,1')->group(function() {

	// Route::get('manage-clients', 'ClientController@manageClients');

	Route::get('get-monthly-summary/{perPage?}', 'ClientController@getMonthlySummary');

	Route::get('get-pending-services/{perPage?}', 'ClientController@getPendingServices');

	Route::get('get-on-process-services/{perPage?}', 'ClientController@getOnProcessServices');

	Route::get('get-today-services/{perPage?}', 'ClientController@getTodayServices');

	Route::get('manage-clients-paginate/{perPage?}', 'ClientController@manageClientsPaginate');

	Route::get('get-clients-packages/{id}', 'ClientController@getClientPackages');

	Route::get('get-clients-groups/{id}', 'ClientController@getClientGroups');

  Route::get('search', 'ClientController@clientSearch');

	Route::get('search-com', 'ClientController@searchCom');


  Route::get('get-reminders/{perPage?}', 'ClientController@getReminders');

	Route::post('add-temporary-client', 'ClientController@addTemporaryClient');

	Route::post('add-client-service', 'ClientController@addClientService');

	Route::post('edit-client-service', 'ClientController@editClientService');

	Route::post('add-client-fund', 'ClientController@addClientFunds');

	Route::post('add-client-payment', 'ClientController@addClientPayment');

	Route::post('add-client-package', 'ClientController@addClientPackage');

	Route::post('delete-client-package', 'ClientController@deleteClientPackage');

	Route::post('/', 'ClientController@store');

	Route::patch('{id}/update-risk', 'ClientController@updateRisk');

	Route::patch('{id}', 'ClientController@update');

    Route::get('get-today-tasks/{perPage?}', 'ClientController@getTodayTasks');

    Route::get('get-past-tasks/{perPage?}', 'ClientController@getPastTasks');

    Route::get('get-tomorrow-tasks/{perPage?}', 'ClientController@getTomorrowTasks');

    Route::post('add-tomorrow-tasks', 'ClientController@addTomorrowTasks');

    Route::post('update-past-tasks', 'ClientController@updatePastTasks');

    Route::post('get-employee', 'ClientController@getEmployees');

	Route::get('unpaid-services/{group_id}/{is_auto_generated}/{page?}', 'ClientController@getUnpaidServices');

	Route::post('add-service-payment', 'ClientController@addServicePayment');

	Route::get('switch-client-cost-level/{client_id}/{service_profile_id?}', 'ClientController@switchCostLevel');

	Route::get('get-documents-on-hand/{id}', 'ClientController@getDocumentsOnHand');

	Route::post('get-clients-by-ids', 'ClientController@getClientsByIds');


	// Visa app
	Route::get('get-all-clients', 'ClientController@getAllClients');
	Route::get('{id}', 'ClientController@show');

	Route::get('get-clients-services/{id}/{tracking?}', 'ClientController@getClientServices');

    Route::post('add-clients-remark', 'ClientController@addClientsRemark');

    Route::get('get-clients-remarks/{client_id}', 'ClientController@getClientsRemarks');

    Route::get('get-pay-services/{id}', 'ClientController@getPayServices');

    Route::post('update-passport-monitor', 'ClientController@updatePassportMonitor');
});

<?php

use Illuminate\Http\Request;
Route::get('/get-group-document-logs/{group_id}', 'LogController@getGroupDocumentLogs');
Route::middleware('auth:api')->group(function() {

	Route::get('/get-transaction-logs/{client_id}/{group_id}', 'LogController@getTransactionLogs');
	Route::get('/get-group-transaction-logs/{client_id}/{group_id}', 'LogController@getGroupTransactionLogs');
	Route::get('/get-commission-logs/{client_id}/{group_id}', 'LogController@getCommissionLogs');
	Route::get('/get-action-logs/{client_id}/{group_id}', 'LogController@getActionLogs');
	Route::get('/get-document-logs/{client_id}', 'LogController@getDocumentLogs');
	// Route::get('/get-group-document-logs/{group_id}', 'LogController@getGroupDocumentLogs');
	Route::post('/delete-latest-document-log', 'LogController@deleteLatestDocLog');
	Route::get('/get-group-documents-onhand/{client_id}', 'LogController@getGroupDocsOnHand');
	Route::get('/get-all-logs/{client_service_id}', 'LogController@getAllLogs');
	Route::get('/get-transaction-history/{client_id}/{group_id}', 'LogController@getTransactionHistory');
	Route::get('/get-service-history/{group_id}', 'LogController@groupServiceHistory');

	Route::get('/get-notification/{client_id}', 'LogController@getAllNotification');

    Route::get('/get-employee-documents-onhand', 'LogController@getEmployeeDocsOnHand');



    // OLD LOGS //
	Route::get('/get-old-transaction-logs/{client_id}/{group_id}/{last_balance}', 'LogController@getOldTransactionLogs');


});

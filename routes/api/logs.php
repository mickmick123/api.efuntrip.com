<?php

use Illuminate\Http\Request;

Route::get('/get-document-logs/{client_id}', 'LogController@getDocumentLogs');

Route::middleware('auth:api')->group(function() {

	Route::get('/get-transaction-logs/{client_id}/{group_id}', 'LogController@getTransactionLogs');
	Route::get('/get-group-transaction-logs/{client_id}/{group_id}', 'LogController@getGroupTransactionLogs');
	Route::get('/get-commission-logs/{client_id}/{group_id}', 'LogController@getCommissionLogs');
	Route::get('/get-action-logs/{client_id}/{group_id}', 'LogController@getActionLogs');
	// Route::get('/get-document-logs/{client_id}', 'LogController@getDocumentLogs');
    Route::get('/get-all-logs/{client_service_id}', 'LogController@getAllLogs');
	Route::get('/get-transaction-history/{client_id}/{group_id}', 'LogController@getTransactionHistory');

    // OLD LOGS //
	Route::get('/get-old-transaction-logs/{client_id}/{group_id}', 'LogController@getOldTransactionLogs');


});

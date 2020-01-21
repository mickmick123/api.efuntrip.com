<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('/get-transaction-logs/{client_id}/{group_id}', 'LogController@getTransactionLogs');

});
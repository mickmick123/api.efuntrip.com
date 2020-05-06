<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::post('/', 'FinancingController@store');

	Route::patch('/{finance_id}', 'FinancingController@update');

	Route::get('/show/{date}/{branch_id}', 'FinancingController@show');

	Route::get('/get-borrowed/{trans_type}/{branch_id}', 'FinancingController@getBorrowed');

	Route::get('/get-req-users', 'FinancingController@getRequestingUsers');

	// financing delivery
	Route::get('/delivery/show/{date}', 'FinancingDeliveryController@show');
	Route::post('/add-purchasing-budget', 'FinancingDeliveryController@addPurchasingBudget');
	Route::post('/add-delivery-finance', 'FinancingDeliveryController@store');
	Route::patch('/update-delivery-finance/{finance_id}', 'FinancingDeliveryController@update');
	Route::patch('/update-delivery-row/{finance_id}', 'FinancingDeliveryController@updateRow');
	Route::delete('/delete-delivery-row/{finance_id}', 'FinancingDeliveryController@deleteRow');
	Route::get('/get-return-list/{trans_type}', 'FinancingDeliveryController@getReturnList');

});
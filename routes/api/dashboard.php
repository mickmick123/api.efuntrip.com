<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

 	Route::get('by-the-numbers', 'DashboardController@statistics');
 	Route::post('get-month-summary', 'DashboardController@getMonthSummary');
});
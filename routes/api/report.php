<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('clients/services', 'ReportController@clientsServices');

	Route::get('report-services', 'ReportController@reportServices');

});
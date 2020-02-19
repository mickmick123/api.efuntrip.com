<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('clients/services', 'ReportController@clientsServices');

	Route::get('report-services', 'ReportController@reportServices');

	Route::get('/{perPage?}', 'ReportController@index');

	Route::get('reports-by-service/{id?}', 'ReportController@reportsByService');

	Route::post('/', 'ReportController@store');

});

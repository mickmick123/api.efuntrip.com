<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('documents', 'ReportController@getDocuments');

	Route::get('on-hand-documents/{id}', 'ReportController@getOnHandDocuments');

	Route::get('clients/services', 'ReportController@clientsServices');

	Route::get('report-services', 'ReportController@reportServices');

	Route::get('filed-reports', 'ReportController@getFiledReports');
	
	Route::get('/{perPage?}', 'ReportController@index');

	Route::get('reports-by-service/{id?}', 'ReportController@reportsByService');

	Route::post('received-documents', 'ReportController@receivedDocuments');

	Route::post('released-documents', 'ReportController@releasedDocuments');

	Route::post('generate-photocopies', 'ReportController@generatePhotocopies');


	Route::post('/', 'ReportController@store');

});

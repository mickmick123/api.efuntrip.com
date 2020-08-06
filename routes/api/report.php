<?php

use Illuminate\Http\Request;

Route::get('check-updated-cost', 'ReportController@checkUpdatedCost');

Route::get('check-ond-handdocs/{id}', 'ReportController@checkOnHandDocs');

Route::middleware('auth:api')->group(function() {

	Route::get('documents', 'ReportController@getDocuments');

	Route::get('documentsById/{id}', 'ReportController@getDocumentsById');

	Route::get('on-hand-documents/{id}', 'ReportController@getOnHandDocuments');

	Route::get('document-logs/{id}', 'ReportController@documentLogs');

	Route::get('clients/services', 'ReportController@clientsServices');

	Route::get('report-services', 'ReportController@reportServices');

	Route::get('filed-reports', 'ReportController@getFiledReports');
	
	Route::get('/{perPage?}', 'ReportController@index');

	Route::get('reports-by-service/{id?}', 'ReportController@reportsByService');

	

	Route::post('update-client-report-score', 'ReportController@updateClientReportScore');

	Route::post('received-documents', 'ReportController@receivedDocuments');

	Route::post('released-documents', 'ReportController@releasedDocuments');

	Route::post('generate-photocopies', 'ReportController@generatePhotocopies');

	Route::post('/', 'ReportController@store');

});

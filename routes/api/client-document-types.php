<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('{perPage?}', 'ClientDocumentTypeController@index');

	Route::post('/', 'ClientDocumentTypeController@store');

	Route::get('details/{id}', 'ClientDocumentTypeController@show');

	Route::patch('details/{id}', 'ClientDocumentTypeController@update');

	Route::delete('{id}', 'ClientDocumentTypeController@destroy');

});
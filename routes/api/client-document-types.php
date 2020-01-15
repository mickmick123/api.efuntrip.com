<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('/', 'ClientDocumentTypeController@index');

	Route::post('/', 'ClientDocumentTypeController@store');

	Route::get('{id}', 'ClientDocumentTypeController@show');

	Route::patch('{id}', 'ClientDocumentTypeController@update');

	Route::delete('{id}', 'ClientDocumentTypeController@destroy');

});
<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('service/{serviceId}', 'ServiceProcedureController@index');

	Route::post('/', 'ServiceProcedureController@store');

	Route::get('{id}', 'ServiceProcedureController@show');

	Route::patch('{id}', 'ServiceProcedureController@update');

	Route::delete('{id}', 'ServiceProcedureController@destroy');

});
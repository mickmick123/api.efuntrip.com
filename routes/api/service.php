<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('manage-services', 'ServiceController@manageServices');

	Route::get('manage-parent-services', 'ServiceController@manageParentServices');

	Route::post('/', 'ServiceController@store');

	Route::get('{id}', 'ServiceController@show');

	Route::patch('{id}', 'ServiceController@update');

	Route::delete('{id}', 'ServiceController@destroy');

	Route::get('{id}/service-profiles-details', 'ServiceController@serviceProfilesDetails');

});

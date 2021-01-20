<?php
use Illuminate\Http\Request;


Route::get('our-services', 'ServiceController@ourServices');
Route::get('{id}', 'ServiceController@show');

Route::middleware('auth:api')->group(function() {

	Route::get('manage-services', 'ServiceController@manageServices');

	Route::get('manage-parent-services', 'ServiceController@manageParentServices');

	Route::post('/', 'ServiceController@store');

	Route::patch('{id}', 'ServiceController@update');

	Route::delete('{id}', 'ServiceController@destroy');

	Route::get('{id}/service-profiles-details', 'ServiceController@serviceProfilesDetails');

	Route::get('{id}/expanded-details', 'ServiceController@expandedDetails');

	Route::get('get-service-rate/{type}/{id}/{service_id?}', 'ServiceController@getServiceRate');

});

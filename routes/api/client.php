<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('manage-clients', 'ClientController@manageClients');

	Route::get('get-pending-services/{perPage?}', 'ClientController@getPendingServices');

	Route::get('get-on-process-services/{perPage?}', 'ClientController@getOnProcessServices');

	Route::get('manage-clients-paginate/{perPage?}', 'ClientController@manageClientsPaginate');

	Route::get('get-clients-services/{id}/{tracking?}', 'ClientController@getClientServices');

	Route::get('get-clients-packages/{id}', 'ClientController@getClientPackages');

	Route::get('get-clients-groups/{id}', 'ClientController@getClientGroups');

	Route::get('search', 'ClientController@clientSearch');

	Route::post('add-temporary-client', 'ClientController@addTemporaryClient');

	Route::post('/', 'ClientController@store');

	Route::get('{id}', 'ClientController@show');

	Route::patch('{id}/update-risk', 'ClientController@updateRisk');

	Route::patch('{id}', 'ClientController@update');



});
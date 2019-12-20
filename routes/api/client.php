<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('manage-clients', 'ClientController@manageClients');

	Route::get('manage-clients-paginate/{perPage?}', 'ClientController@manageClientsPaginate');

	Route::post('/', 'ClientController@store');

	Route::get('{id}', 'ClientController@show');

	Route::patch('{id}', 'ClientController@update');

});
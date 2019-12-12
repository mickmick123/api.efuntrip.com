<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('manage-clients', 'ClientController@manageClients');

	Route::get('manage-clients-paginate', 'ClientController@manageClientsPaginate');

	Route::post('/', 'ClientController@store');

	Route::patch('{id}', 'ClientController@update');

});
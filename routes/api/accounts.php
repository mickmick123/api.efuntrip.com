<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

 	Route::post('/', 'AccountsController@store');

 	Route::get('get-cpanel-users', 'AccountsController@getCpanelUsers');

 	Route::get('{id}', 'AccountsController@show');

 	Route::patch('{id}', 'AccountsController@update');

 	Route::delete('{id}', 'AccountsController@destroy');

});
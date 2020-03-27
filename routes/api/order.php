<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::get('/list', 'OrdersController@list');
	Route::get('/product-category', 'OrdersController@productCategories');
	Route::get('/products/{category_id}', 'OrdersController@products');
	Route::post('/mark-complete', 'OrdersController@markComplete');

	Route::post('/', 'OrdersController@store');
	Route::get('{id}', 'OrdersController@show');
	Route::patch('{id}', 'OrdersController@update');
	Route::delete('/{id}', 'OrdersController@delete');

});
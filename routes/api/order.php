<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

	Route::post('/product-upload/{product_id}', 'OrdersController@uploadProduct');
	Route::get('/list/{perPage?}', 'OrdersController@list');
	Route::get('/product-category', 'OrdersController@productCategories');
	Route::get('/products/{category_id}', 'OrdersController@products');
	Route::post('/mark-complete', 'OrdersController@markComplete');
	Route::post('/update-product', 'OrdersController@updateProduct');
	Route::post('/add-product', 'OrdersController@addProduct');
	Route::post('/summary', 'OrdersController@newOrderSummary');

	Route::post('/', 'OrdersController@store');
	Route::get('{id}', 'OrdersController@show');
	Route::patch('{id}', 'OrdersController@update');
	Route::delete('/{id}', 'OrdersController@delete');

});
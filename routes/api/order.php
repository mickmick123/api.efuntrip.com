<?php

use Illuminate\Http\Request;
	Route::get('/product-category', 'OrdersController@productCategories');
	Route::get('/products/{category_id}', 'OrdersController@products');

	Route::get('/get-product-category/{category_id}', 'OrdersController@getProductCategories');
	Route::get('/get-category-details/{category_id}', 'OrdersController@getCategoryDetails');
	Route::get('/get-products/{category_id}', 'OrdersController@getProducts');
	Route::get('/get-all-product-category', 'OrdersController@getAllCategories');
	
	Route::post('/add-product-category', 'OrdersController@storeCategory');
	Route::post('/update-product-category', 'OrdersController@updateCategory');

Route::middleware('auth:api')->group(function() {

	Route::post('/product-upload/{product_id}', 'OrdersController@uploadProduct');
	Route::get('/list/{perPage?}', 'OrdersController@list');
	Route::get('/view-log/{order_id?}', 'OrdersController@viewOrderLog');
	// Route::get('/product-category', 'OrdersController@productCategories');
	Route::post('/mark-complete', 'OrdersController@markComplete');
	Route::post('/update-product', 'OrdersController@updateProduct');
	Route::post('/add-product', 'OrdersController@addProduct');
	Route::post('/summary', 'OrdersController@newOrderSummary');

	Route::post('/', 'OrdersController@store');
	Route::get('{id}', 'OrdersController@show');
	Route::patch('{id}', 'OrdersController@update');
	Route::delete('/{id}', 'OrdersController@delete');

});
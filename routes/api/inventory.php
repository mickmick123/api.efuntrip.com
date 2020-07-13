<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

    Route::get('/get-all-inventory-categories','InventoryController@getAllInventoryCategories');
    Route::get('/get-all-inventory-lists','InventoryController@getAllInventoryLists');
    Route::get('/get-newly-added','InventoryController@getNewlyAdded');
    Route::get('/list','InventoryController@list');
    Route::get('/get-consumed','InventoryController@getConsumed');

    Route::post('/add-inventory','InventoryController@addInventory');
    Route::post('/add-inventory-category','InventoryController@addInventoryCategory');

    Route::post('/edit-inventory','InventoryController@editInventory');
    Route::post('/edit-inventory-category','InventoryController@editInventoryCategory');

    Route::post('/delete-inventory','InventoryController@deleteInventory');
    Route::post('/delete-inventory-category','InventoryController@deleteInventoryCategory');

    Route::get('/test','InventoryController@test');
});

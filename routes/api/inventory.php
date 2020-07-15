<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

    Route::post('/get-all-inventory-categories','InventoryController@getAllInventoryCategories');
    Route::get('/get-all-inventory-lists','InventoryController@getAllInventoryLists');
    Route::get('/get-newly-added','InventoryController@getNewlyAdded');
    Route::get('/list','InventoryController@list');
    Route::get('/get-consumed','InventoryController@getConsumed');

    Route::post('/move-inventory-category','InventoryController@moveInventoryCategory');

    Route::post('/add-company','InventoryController@addCompany');
    Route::post('/add-inventory','InventoryController@addInventory');
    Route::post('/add-inventory-category','InventoryController@addInventoryCategory');

    Route::post('/edit-inventory','InventoryController@editInventory');
    Route::post('/assign-inventory','InventoryController@assignInventory');
    Route::post('/update-image','InventoryController@updateImage');

    Route::post('/delete-inventory','InventoryController@deleteInventory');
    Route::post('/delete-inventory-category','InventoryController@deleteInventoryCategory');


    Route::match(array('get','post'),'/test','InventoryController@test');
});

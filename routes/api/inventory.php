<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

    Route::post('/get-all-inventory-categories','InventoryController@getAllInventoryCategories');
    Route::post('/get-tab-category','InventoryController@getTabCategory');
    Route::get('/get-all-inventory-lists','InventoryController@getAllInventoryLists');
    Route::get('/get-newly-added','InventoryController@getNewlyAdded');
    Route::get('/list','InventoryController@list');
    Route::get('/list-assigned','InventoryController@listAssigned');
    Route::post('/edit-assigned-item','InventoryController@editAssignedItem');
    Route::get('/get-consumed','InventoryController@getConsumed');
    Route::get('/get-modified','InventoryController@getNewlyModified');
    Route::post('/get-company-category','InventoryController@getCompanyCategory');
    Route::post('/get-company-category-inventory','InventoryController@getCompanyCategoryInventory');
    Route::post('/get-category-inventory','InventoryController@getCategoryInventory');

    Route::post('/move-inventory-category','InventoryController@moveInventoryCategory');

    Route::post('/add-company','InventoryController@addCompany');
    Route::post('/add-inventory','InventoryController@addInventory');
    Route::post('/add-inventory-category','InventoryController@addInventoryCategory');
    Route::post('/edit-inventory-category','InventoryController@editInventoryCategory');

    Route::post('/add-more-item','InventoryController@addMoreItem');
    Route::post('/edit-inventory','InventoryController@editInventory');
    Route::post('/assign-inventory','InventoryController@assignInventory');
    Route::post('/retrieve-inventory','InventoryController@retrieveInventory');
    Route::post('/disposed-inventory','InventoryController@disposedInventory');
    Route::post('/transfer-inventory','InventoryController@transferInventory');
    Route::post('/update-image','InventoryController@updateImage');

    Route::get('/list-location','InventoryController@locationList');
    Route::post('/delete-location','InventoryController@deleteLocation');

    Route::post('/delete-inventory','InventoryController@deleteInventory');
    Route::post('/delete-inventory-category','InventoryController@deleteInventoryCategory');


    Route::match(array('get','post'),'/test','InventoryController@test');
});

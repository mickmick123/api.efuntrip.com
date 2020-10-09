<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

    Route::post('/get-all-inventory-categories','InventoryController@getAllInventoryCategories');
    Route::post('/get-new-list','InventoryController@getNewList');
    Route::post('/get-tree-category','InventoryController@getTreeCategory');
    Route::post('/get-tab-category','InventoryController@getTabCategory');
    Route::get('/get-all-inventory-lists','InventoryController@getAllInventoryLists');
    Route::get('/get-newly-added/{perPage?}','InventoryController@getNewlyAdded');
    Route::get('/list','InventoryController@list');
    Route::get('/item/{id}','InventoryController@show');
    Route::get('/list-assigned','InventoryController@listAssigned');
    Route::post('/edit-assigned-item','InventoryController@editAssignedItem');
    Route::get('/get-consumed','InventoryController@getConsumed');
    Route::get('/get-modified','InventoryController@getNewlyModified');
    Route::post('/get-company-category','InventoryController@getCompanyCategory');
    Route::post('/get-company-category-inventory','InventoryController@getCompanyCategoryInventory');
    Route::post('/get-category-inventory','InventoryController@getCategoryInventory');
    Route::post('/get-action-log','InventoryController@getActionLog');
    Route::post('/get-purchase-unit','InventoryController@getPurchaseUnit');
    Route::post('/get-selling-unit','InventoryController@getSellingUnit');

    Route::post('/move-inventory-category','InventoryController@moveInventoryCategory');

    Route::post('/add-company','InventoryController@addCompany');
    Route::post('/add-inventory','InventoryController@addInventory');
    Route::post('/add-inventory-category','InventoryController@addInventoryCategory');
    Route::get('/list-inventory-consumable','InventoryController@getInventoryConsumable');
    Route::post('/add-inventory-consumable','InventoryController@addInventoryConsumable');
    Route::post('/add-inventory-consume','InventoryController@addInventoryConsume');
    Route::post('/edit-inventory-category','InventoryController@editInventoryCategory');

    Route::post('/add-more-item','InventoryController@addMoreItem');
    Route::post('/edit-inventory','InventoryController@editInventory');
    Route::post('/update-item-profile-consumable','InventoryController@updateItemProfileConsumable');
    Route::post('/get-inventory','InventoryController@getInventory');
    Route::post('/assign-inventory','InventoryController@assignInventory');
    Route::post('/retrieve-inventory','InventoryController@retrieveInventory');
    Route::post('/disposed-inventory','InventoryController@disposedInventory');
    Route::post('/transfer-inventory','InventoryController@transferInventory');
    Route::post('/update-image','InventoryController@updateImage');

    Route::get('/list-location','InventoryController@locationList');
    Route::get('/list-location-consumable','InventoryController@locationListConsumable');
    Route::post('/delete-location','InventoryController@deleteLocation');

    Route::post('/delete-inventory','InventoryController@deleteInventory');
    Route::post('/delete-inventory-category','InventoryController@deleteInventoryCategory');

    Route::post('/get-supplier','InventoryController@getSupplier');
    Route::get('/get-users-list','InventoryController@getUsersList');

    Route::match(array('get','post'),'/test','InventoryController@test');
});

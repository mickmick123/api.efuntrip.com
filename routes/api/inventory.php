<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

    Route::get('get-all-company','InventoryController@getAllCompanies');

});

<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

    Route::get('/get-location','LocationController@getLocation');
    Route::get('/get-location-detail','LocationController@getLocationDetail');

    Route::post('/get-location-consumable','LocationController@getLocationConsumable');

});

<?php

use Illuminate\Http\Request;
Route::get('get-packages', 'FuntripController@getPackages');
Route::post('add-package', 'FuntripController@addPackage');
Route::post('update-package', 'FuntripController@updatePackage');
Route::post('delete-package', 'FuntripController@deletePackage');
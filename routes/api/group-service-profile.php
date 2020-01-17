<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

  Route::get('services/{group_id}/{branch_id?}', 'GroupServiceProfileController@services');

});

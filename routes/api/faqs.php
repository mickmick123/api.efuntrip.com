<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

  Route::get('/', 'FaqsController@index');
 	
});
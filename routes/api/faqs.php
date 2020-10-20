<?php

use Illuminate\Http\Request;


Route::get('/', 'FaqsController@index');


Route::middleware('auth:api')->group(function() {

});

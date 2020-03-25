<?php

use Illuminate\Http\Request;

Route::post('/login', 'AppController@login');

Route::middleware('auth:api')->group(function() {


});
<?php

use Illuminate\Http\Request;

Route::post('/login', 'AppController@login');
Route::post('/verify-username', 'AppController@verifyUsername');
Route::post('/check-client', 'AppController@checkClient');
Route::post('/check-passport', 'AppController@checkPassport');

Route::middleware('auth:api')->group(function() {


});
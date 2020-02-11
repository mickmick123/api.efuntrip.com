<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

    Route::get('', 'AttendanceController@index');
    
    Route::post('test-kernel', 'AttendanceController@testKernel');
});
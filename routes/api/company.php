<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

    Route::get('/get-company','CompanyController@getCompany');

    Route::post('/add-company','CompanyController@addCompany');
    Route::post('/edit-company','CompanyController@editCompany');
    Route::post('/delete-company','CompanyController@deleteCompany');

});

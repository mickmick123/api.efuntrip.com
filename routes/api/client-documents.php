<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

    Route::get('{id}', 'ClientDocumentController@index');

    Route::post('add', 'ClientDocumentController@store');

    Route::post('upload-documents', 'ClientDocumentController@uploadDocuments');

});
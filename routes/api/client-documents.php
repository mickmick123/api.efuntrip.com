<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

    Route::get('{id}', 'ClientDocumentController@index');

    Route::post('add', 'ClientDocumentController@store');

    Route::post('upload-documents', 'ClientDocumentController@uploadDocuments');

    Route::get('client/{id}', 'ClientDocumentController@getDocumentsByClient');

    Route::post('document-type', 'ClientDocumentController@getDocumentTypes');

    Route::post('upload/{id}', 'ClientDocumentController@uploadDocumentsByClient');

    Route::post('delete-client-documents', 'ClientDocumentController@deleteClientDocument');

});
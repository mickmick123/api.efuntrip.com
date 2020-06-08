<?php

use Illuminate\Http\Request;

Route::middleware('auth:api')->group(function() {

    Route::get('{id}', 'ClientDocumentController@index');

    Route::post('add', 'ClientDocumentController@store');

    Route::post('upload-documents', 'ClientDocumentController@uploadDocuments');

    Route::post('document-type', 'ClientDocumentController@getDocumentTypes');

    Route::post('upload/{id}', 'ClientDocumentController@uploadDocumentsByClient');

    Route::post('delete-client-documents', 'ClientDocumentController@deleteClientDocument');

    Route::post('upload-docs', 'ClientDocumentController@uploadDocumentsByClientApp');

    Route::get('client/{id}', 'ClientDocumentController@getDocumentsByClient');

    Route::get('client-docs/{id}', 'ClientDocumentController@getDocumentsByClientApp');

});
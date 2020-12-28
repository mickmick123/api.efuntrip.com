<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('add-evaluation', 'RiderEvaluationController@addEvaluation');

Route::post('get-evaluation-day', 'RiderEvaluationController@getEvaluationDay');
Route::post('get-evaluation-month', 'RiderEvaluationController@getEvaluationMonth');


Route::middleware('auth:api')->group(function () {
});

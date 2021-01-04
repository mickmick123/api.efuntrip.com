<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('add-evaluation', 'RiderEvaluationController@addEvaluation');
Route::post('update-evaluation', 'RiderEvaluationController@updateEvaluation');
Route::post('delete-evaluation', 'RiderEvaluationController@deleteEvaluation');

Route::post('get-evaluation', 'RiderEvaluationController@getEvaluation');
Route::get('get-evaluation-day/{perPage?}', 'RiderEvaluationController@getEvaluationDay');
Route::post('get-evaluation-month', 'RiderEvaluationController@getEvaluationMonth');

Route::post('get-summary-evaluation-half-month', 'RiderEvaluationController@getSummaryEvaluationHalfMonth');

Route::middleware('auth:api')->group(function () {
});

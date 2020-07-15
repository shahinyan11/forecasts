<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('account/login', 'Auth\LoginController@login');
Route::post('account/register', 'Auth\RegisterController@register');

//protected routes
Route::group(['middleware' => 'auth:api'], function() {
    Route::get('account/logout', 'Auth\LoginController@logout');
    Route::put('account/update', 'UserController@update');
    Route::get('users', 'UserController@getUsersList');
    Route::get('users/{userId}', 'UserController@getUserById');
    Route::put('users/{userId}/update', 'UserController@updateUser');
    Route::post('users/{userId}/suspend', 'UserController@suspendUser');
    Route::delete('users/{userId}/delete ', 'UserController@deleteUser');
    Route::post('predictions', 'PredictionController@createPrediction');
    Route::get('predictions', 'PredictionController@getPredictions');
    Route::get('predictions/{id}', 'PredictionController@getPredictionById');
    Route::put('predictions/approve/{id}', 'PredictionController@approvePrediction');
    Route::put('predictions/approve', 'PredictionController@approveSomePredictions');

});
Route::post('account/reset', 'PasswordResetController@create');
Route::get('account/reset/{token}', 'PasswordResetController@find');
Route::post('account/resetProcess', 'PasswordResetController@reset');

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\UserController;

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

Route::controller(AuthController::class)->group(function(){
    Route::post('login', 'login');
    Route::post('login-using-firebase', 'loginFirebase');
    Route::post('get-user-by-phone', 'getUserByPhone');
});
Route::group(['middleware' => ['auth:api']], function () {
    Route::controller(ServiceController::class)->group(function(){
        Route::get('service/get-services', 'getServices');
        Route::post('service/add-service', 'addService');
        Route::post('service/update-service', 'updateService');
        Route::get('service/delete-service/{service_id}', 'deleteService');
    });
        Route::group(['prefix' => 'user','middleware' => ['checkRole:super-admin']], function () {
                Route::controller(UserController::class)->group(function () {
                Route::post('add-user', 'addUser');
                Route::post('update-user', 'updateUser');
                Route::get('get-user-by-id/{id}', 'getUserById');
                Route::get('get-users', 'getAllUsers');
                Route::post('delete-user', 'deleteUser');
            });
        });
});
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\OrderController;
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

Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('login-using-firebase', 'loginFirebase');
    Route::post('get-user-by-phone', 'getUserByPhone');
    Route::post('get-user-by-email', 'getUserByEmail');
    Route::post('register-user', 'registerUser');
});

Route::group(['middleware' => ['auth:api']], function () {
    Route::controller(ServiceController::class)->group(function () {
        Route::get('service/get-services', 'getServices');
        Route::post('service/add-service', 'addService');
        Route::post('service/update-service', 'updateService');
        Route::get('service/delete-service/{service_id}', 'deleteService');
        Route::get('service/get-service-by-id/{service_id}', 'getServiceById');
        Route::get('service/get-services-by-userid/{user_id}', 'getServiceByUserId');
    });

    //Orders Routes
    Route::controller(OrderController::class)->group(function () {
        Route::get('order/get-orders', 'getOrders');
        Route::post('order/add-order', 'addOrder');
        Route::post('order/update-order', 'updateOrder');
        Route::get('order/delete-order/{order_id}', 'deleteOrder');
        Route::get('order/get-order-by-id/{order_id}', 'getOrderById');
    });
    Route::group(['prefix' => 'user', 'middleware' => ['checkRole:super-admin']], function () {
        Route::controller(UserController::class)->group(function () {
            Route::post('add-user', 'addUser');
            Route::post('update-user', 'updateUser');
            Route::get('get-user-by-id/{id}', 'getUserById');
            Route::get('get-users', 'getAllUsers');
            Route::post('delete-user', 'deleteUser');
            Route::get('get-user-names-list', 'getUsersNamesList');

        });
    });
});

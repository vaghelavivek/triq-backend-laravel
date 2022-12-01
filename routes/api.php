<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\PaymentController;

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
    Route::group(['prefix' => 'order'], function () {
        Route::controller(OrderController::class)->group(function () {
            Route::get('get-orders', 'getOrders');
            Route::get('delete-order/{order_id}', 'deleteOrder');
            Route::get('get-order-by-id/{order_id}', 'getOrderById');
            Route::post('get-order-document-by-serviceid', 'getOrderDocumentByServiceId');
            Route::post('add-order-comment', 'addOrderComment');
            Route::post('add-user-order', 'addUserOrder');
            Route::post('update-order', 'updateOrder');
        });
    });

    Route::group(['prefix' => 'order','middleware' => ['checkRole:super-admin']], function () {
        Route::controller(OrderController::class)->group(function () {
            Route::post('add-order', 'addOrder');
        });
    });

    Route::group(['prefix' => 'user'], function () {
        Route::controller(UserController::class)->group(function () {
            Route::get('get-users', 'getAllUsers');            
            Route::get('get-user-names-list', 'getUsersNamesList');
            Route::get('get-user-by-id/{id}', 'getUserById');
        });
    });
    Route::group(['prefix' => 'user', 'middleware' => ['checkRole:super-admin']], function () {
        Route::controller(UserController::class)->group(function () {
            Route::post('add-user', 'addUser');
            Route::post('update-user', 'updateUser');
            Route::post('delete-user', 'deleteUser');

        });
    });
    Route::group(['prefix' => 'payment'], function () {
        Route::controller(PaymentController::class)->group(function () {
            Route::get('create-checksum', 'createCheckSum');
            Route::post('create-transaction-token', 'createTransectionToken');
        });
    });
});

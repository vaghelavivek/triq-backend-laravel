<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\ServiceController;

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
});

Route::group(['middleware' => ['auth:api']], function () {
    //Services Routes
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
});

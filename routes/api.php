<?php

use App\Http\Controllers\AirlineController;
use App\Http\Controllers\User;
use App\Http\Controllers\Partner;
use App\Http\Controllers\Admin;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::controller(UserController::class)->group(function() {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
});

Route::prefix('airline')->group(function() {
    Route::controller(AirlineController::class)->group(function() {
        Route::get('/get', 'get');
        Route::get('/get/{id}', 'show');
    });
});

Route::prefix('hotel')->group(function() {
    Route::controller(HotelController::class)->group(function() {
        Route::get('/get', 'get');
        Route::get('/get/{id}', 'show');
    });
});

Route::middleware('auth:sanctum')->group(function() {
    Route::prefix('user')->group(function() {
        Route::controller(User\UserController::class)->group(function() {
            Route::get('/profile', 'getProfile');
        });
    });

    Route::prefix('partner')->group(function() {
        Route::controller(Partner\PartnerController::class)->group(function() {
            Route::get('/get', 'get');
        });

        Route::prefix('hotel')->group(function() {
            Route::controller(Partner\HotelController::class)->group(function() {
                Route::get('/get', 'getHotel');
                Route::post('/create', 'createHotel');
                Route::put('/edit/{id}', 'editHotel');
                Route::delete('/delete/{id}', 'deleteHotel');

                Route::prefix('facility')->group(function() {
                    Route::get('/get', 'getFacilities');
                    Route::post('/create', 'createFacility');
                    Route::put('/edit/{id}', 'editFacility');
                    Route::delete('/delete/{id}', 'deleteFacility');
                });

                Route::prefix('type')->group(function() {
                    Route::get('/get', 'getRoomType');
                    Route::post('/create', 'createRoomType');
                    Route::put('/edit/{id}', 'editRoomType');
                    Route::delete('/delete/{id}', 'deleteRoomType');
                });

                Route::prefix('room')->group(function() {
                    Route::get('/get', 'getRoom');
                    Route::post('/create', 'createRoom');
                    Route::put('/edit/{id}', 'editRoom');
                    Route::delete('/delete/{id}', 'deleteRoom');
                });
            });
        });
    });

    Route::prefix('admin')->group(function() {
        Route::prefix('user')->group(function() {
            Route::controller(Admin\UserController::class)->group(function() {
                Route::get('/get', 'get');
                Route::post('/create', 'create');
                Route::put('/edit/{id}', 'edit');
            });
        });
    });
});

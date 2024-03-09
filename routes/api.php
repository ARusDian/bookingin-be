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

Route::controller(UserController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
});

Route::controller(AirlineController::class)->group(function () {
    Route::prefix('airline')->group(function () {
        Route::get('/get', 'getAirline');
        Route::get('/get/{id}', 'showAirline');
    });

    Route::prefix('flight')->group(function () {
        Route::get('/get', 'getFlights');
        Route::get('/get/{id}', 'showFlight');
    });
});

Route::prefix('hotel')->group(function () {
    Route::controller(HotelController::class)->group(function () {
        Route::get('/get', 'getHotel');
        Route::get('/get/{id}', 'showHotel');
        Route::get('/get/{id}/room/{roomId}', 'showHotelRoom');
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::controller(User\UserController::class)->group(function () {
        Route::get('/me', 'getProfile');
    });

    Route::prefix('user')->middleware(['role:USER'])->group(function () {
        Route::prefix('flight')->group(function () {
            Route::controller(User\AirlineController::class)->group(function () {
                Route::post('/buy', 'buyTicket');
            });
        });

        Route::prefix('hotel')->group(function () {
            Route::controller(User\HotelController::class)->group(function () {
                Route::post('/reservation', 'reservation');
            });
        });
    });

    Route::prefix('partner')->middleware(['role:PARTNER'])->group(function () {
        Route::controller(Partner\PartnerController::class)->group(function () {
            Route::get('/get', 'get');
        });

        Route::prefix('hotel')->group(function () {
            Route::controller(Partner\HotelController::class)->group(function () {
                Route::get('/get', 'getHotel');
                Route::post('/create', 'createHotel');
                Route::put('/edit/{id}', 'editHotel');
                Route::delete('/delete/{id}', 'deleteHotel');

                Route::prefix('facility')->group(function () {
                    Route::get('/get', 'getFacilities');
                    Route::post('/create', 'createFacility');
                    Route::put('/edit/{id}', 'editFacility');
                    Route::delete('/delete/{id}', 'deleteFacility');
                });

                Route::prefix('type')->group(function () {
                    Route::get('/get', 'getRoomType');
                    Route::post('/create', 'createRoomType');
                    Route::put('/edit/{id}', 'editRoomType');
                    Route::delete('/delete/{id}', 'deleteRoomType');
                });

                Route::prefix('room')->group(function () {
                    Route::get('/get', 'getRoom');
                    Route::post('/create', 'createRoom');
                    Route::put('/edit/{id}', 'editRoom');
                    Route::delete('/delete/{id}', 'deleteRoom');
                });
            });
        });

        Route::prefix('airline')->group(function () {
            Route::controller(Partner\AirlineController::class)->group(function () {
                Route::get('/get', 'getAirlines');
                Route::post('/create', 'createAirline');
                Route::put('/edit/{id}', 'editAirline');
                Route::delete('/delete/{id}', 'deleteAirline');

                Route::prefix('type')->group(function () {
                    Route::get('/get', 'getPlaneTypes');
                    Route::post('/create', 'createPlaneType');
                    Route::put('/edit/{id}', 'editPlaneType');
                    Route::delete('/delete/{id}', 'deletePlaneType');
                });

                Route::prefix('plane')->group(function () {
                    Route::get('/get', 'getPlanes');
                    Route::post('/create', 'createPlane');
                    Route::put('/edit/{id}', 'editPlane');
                    Route::delete('/delete/{id}', 'deletePlane');

                    Route::prefix('seat')->group(function () {
                        Route::get('/get', 'getPlaneSeats');
                        Route::post('/create', 'createPlaneSeat');
                        Route::put('/edit/{id}', 'editPlaneSeat');
                        Route::delete('/delete/{id}', 'deletePlaneSeat');
                    });

                    Route::prefix('flight')->group(function () {
                        Route::get('/get', 'getPlaneFlights');
                        Route::post('/create', 'createPlaneFlight');
                        Route::put('/edit/{id}', 'editPlaneFlight');
                        Route::delete('/delete/{id}', 'deletePlaneFlight');
                    });
                });
            });
        });
    });

    Route::prefix('admin')->middleware(['role:ADMIN'])->group(function () {
        Route::prefix('user')->group(function () {
            Route::controller(Admin\UserController::class)->group(function () {
                Route::get('/get', 'get');
                Route::post('/create', 'create');
                Route::put('/edit/{id}', 'edit');
                Route::post('/topup/{id}', 'topup');
                Route::post('/withdraw/{id}', 'withdraw');
                Route::get('/transaction/{id}', 'getTransaction');
            });
        });

        Route::prefix('transaction')->group(function () {
            Route::controller(Admin\TransactionController::class)->group(function () {
                Route::get('/get', 'get');
            });
        });
    });
});

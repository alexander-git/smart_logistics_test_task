<?php

use App\Http\Controllers\Api\V1\Notification\StartNotificationController;
use App\Http\Controllers\Api\V1\Receiver\ShowHistoryController;
use App\Http\Controllers\Api\V1\Test\InitDataController;
use App\Http\Controllers\Api\V1\Test\InitWithRandomDataController;
use App\Http\Middleware\StartNotificationDeduplicateMiddleware;
use Illuminate\Support\Facades\Route;

Route::post('/v1/notification/start', StartNotificationController::class)
    ->middleware(['throttle:startNotification', StartNotificationDeduplicateMiddleware::class]);

Route::get('/v1/receiver/show-history/{receiver}', ShowHistoryController::class)
    ->middleware('throttle:showReceiverHistory');

Route::post('/v1/test/init-data', InitDataController::class);
Route::post('/v1/test/init-with-random-data', InitWithRandomDataController::class);

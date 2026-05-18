<?php

use App\Http\Controllers\Api\V1\Notification\Start\StartNotificationController;
use App\Http\Controllers\Api\V1\Receiver\ShowHistory\ShowHistoryController;
use App\Http\Controllers\Api\V1\Test\InitData\InitDataController;
use App\Http\Middleware\StartNotificationDeduplicateMiddleware;
use Illuminate\Support\Facades\Route;

Route::post('/v1/notification/start', StartNotificationController::class)
    ->middleware(['throttle:startNotification', StartNotificationDeduplicateMiddleware::class]);

Route::get('/v1/receiver/show-history/{receiver}', ShowHistoryController::class)
    ->middleware('throttle:showReceiverHistory');

Route::post('/v1/test/data/init', InitDataController::class);

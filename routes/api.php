<?php

use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:api')->group(function () {
    Route::get('/me', [UserController::class, 'me']);
});

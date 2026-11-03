<?php

use App\Http\Controllers\Api\ImageGenerationController;
use App\Http\Middleware\AuthenticateImageApiKey;
use Illuminate\Support\Facades\Route;

Route::middleware([AuthenticateImageApiKey::class, 'throttle:10,1'])
    ->post('/v1/images/generations', ImageGenerationController::class)
    ->name('api.v1.images.generations');

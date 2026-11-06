<?php
use App\Http\Controllers\Api\ImageGenerationController;
use App\Http\Middleware\AuthenticateImageApiKey;
use Illuminate\Support\Facades\Route;
Route::prefix('v1')->middleware([AuthenticateImageApiKey::class,'throttle:120,1'])->group(function(){
 Route::get('/catalog/stores',[ImageGenerationController::class,'catalogStores'])->name('api.v1.catalog.stores');
 Route::get('/catalog/stores/{store}/templates',[ImageGenerationController::class,'catalogTemplates'])->whereNumber('store')->name('api.v1.catalog.templates');
 Route::get('/catalog/models',[ImageGenerationController::class,'catalogModels'])->name('api.v1.catalog.models');
 Route::post('/images/generations',[ImageGenerationController::class,'store'])->name('api.v1.images.generations.store');
 Route::get('/images/generations/{generation}',[ImageGenerationController::class,'show'])->whereNumber('generation')->name('api.v1.images.generations.show');
 Route::post('/images/generations/{generation}/cancel',[ImageGenerationController::class,'cancel'])->whereNumber('generation')->name('api.v1.images.generations.cancel');
 Route::get('/images/generations/{generation}/images/{image}',[ImageGenerationController::class,'showImage'])->whereNumber('generation')->whereNumber('image')->name('api.v1.images.generations.images.show');
});
<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::view('/kiosk', 'kiosk.home')->name('kiosk.home');


Route::get('/kiosk/availability', [\App\Http\Controllers\KioskController::class, 'availabilityForm'])
     ->name('kiosk.availability');

Route::post('/kiosk/availability', [\App\Http\Controllers\KioskController::class, 'availabilitySearch'])
     ->name('kiosk.availability.search');


Route::get('/booking/thank-you', [\App\Http\Controllers\KioskController::class, 'thankYou'])
    ->name('kiosk.thankyou');

Route::get('/kiosk/checkin', [\App\Http\Controllers\KioskController::class, 'checkinForm'])->name('kiosk.checkin');
Route::post('/kiosk/checkin', [\App\Http\Controllers\KioskController::class, 'checkinLookup']);

Route::get('/kiosk/checkout', [\App\Http\Controllers\KioskController::class, 'checkoutForm'])->name('kiosk.checkout');
Route::post('/kiosk/checkout', [\App\Http\Controllers\KioskController::class, 'checkoutLookup']);

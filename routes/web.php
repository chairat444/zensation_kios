<?php

use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\KioskController;
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
Route::view('/', 'kiosk.home')->name('kiosk.home');

Route::view('/kiosk', 'kiosk.home')->name('kiosk.home');

Route::prefix('kiosk')->group(function () {



Route::get('/availability', [KioskController::class, 'availabilityForm'])
     ->name('kiosk.availability');

Route::post('/availability', [KioskController::class, 'availabilitySearch'])
     ->name('kiosk.availability.search');


Route::get('/checkin', [KioskController::class, 'showCheckin'])->name('kiosk.checkin');
Route::post('/search', [KioskController::class, 'searchReservation'])->name('api.kiosk.search');

Route::post('/checkin/perform', [KioskController::class, 'performCheckin'])->name('api.kiosk.checkin.perform');
});

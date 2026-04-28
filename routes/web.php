<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KioskController;
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

Route::view('/kiosk', 'kiosk.home')->name('kiosk.landing');

Route::prefix('kiosk')->group(function () {
    Route::get('/availability', [KioskController::class, 'availabilityForm'])
        ->name('kiosk.availability');

    Route::post('/availability', [KioskController::class, 'availabilitySearch'])
        ->name('kiosk.availability.search');

    Route::get('/checkin', [KioskController::class, 'showCheckin'])->name('kiosk.checkin');
    Route::post('/checkin', [KioskController::class, 'guestCheckIn'])->name('kiosk.checkin');

    Route::get('/card-dispenser-test', [KioskController::class, 'showCardDispenserTest'])
        ->name('kiosk.card-dispenser-test');

    Route::get('/passport', function () {
        return view('kiosk.passport');
    });

    // Route สำหรับประมวลผลการ Sync ข้อมูลและค้นหา
    Route::any('/search', [KioskController::class, 'searchWithLiveSync'])->name('kiosk.search');

});

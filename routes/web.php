<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


use App\Http\Controllers\UserController;

Route::get('/register', [UserController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [UserController::class, 'register']);

Route::get('/login', [UserController::class, 'showLoginForm'])->name('login');
Route::post('/login', [UserController::class, 'login']);

Route::get('/verify-otp', [UserController::class, 'showOtpForm'])->name('verify.otp');
Route::post('/verify-otp', [UserController::class, 'verifyOtp'])->name('verify.otp');

// Add resend OTP route
Route::post('/resend-otp', [UserController::class, 'resendOtp'])->name('resend.otp');

// Test email route - for debugging Brevo configuration
Route::get('/test-email', [UserController::class, 'testEmail'])->name('test.email');

Route::get('/home', [UserController::class, 'home'])->name('home')->middleware('auth');

Route::get('/logout', [UserController::class, 'logout'])->name('logout');

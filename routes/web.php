<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\MembershipApplicationController;
use App\Http\Controllers\MembershipReviewController;

// Public routes
Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/daftar', [MembershipApplicationController::class, 'create'])->name('membership.create');
Route::post('/daftar', [MembershipApplicationController::class, 'store'])->name('membership.store');
Route::get('/daftar/sukses', [MembershipApplicationController::class, 'success'])->name('membership.success');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Membership review routes
    Route::get('/admin/pendaftaran', [MembershipReviewController::class, 'index'])->name('admin.membership.index');
    Route::get('/admin/pendaftaran/{application}', [MembershipReviewController::class, 'show'])->name('admin.membership.show');
    Route::post('/admin/pendaftaran/{application}/setujui', [MembershipReviewController::class, 'approve'])->name('admin.membership.approve');
    Route::post('/admin/pendaftaran/{application}/tolak', [MembershipReviewController::class, 'reject'])->name('admin.membership.reject');
});

// Test route for authorization middleware
Route::get('/test-ketua-umum', function () {
    return 'Access granted';
})->middleware(['auth', 'active', 'role:Ketua Umum']);

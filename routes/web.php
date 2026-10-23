<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\MembershipApplicationController;
use App\Http\Controllers\MembershipReviewController;
use App\Http\Controllers\AccountActivationController;
use App\Http\Controllers\MemberController;

// Public routes
Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/daftar', [MembershipApplicationController::class, 'create'])->name('membership.create');
Route::post('/daftar', [MembershipApplicationController::class, 'store'])->name('membership.store');
Route::get('/daftar/sukses', [MembershipApplicationController::class, 'success'])->name('membership.success');

// Activation routes
Route::get('/aktivasi/{token}', [AccountActivationController::class, 'show'])->name('activation.show');
Route::post('/aktivasi/{token}', [AccountActivationController::class, 'activate'])->name('activation.activate');
Route::get('/aktivasi-sukses', [AccountActivationController::class, 'success'])->name('activation.success');

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
    
    // Member management routes
    Route::get('/admin/anggota', [MemberController::class, 'index'])->name('admin.members.index');
    Route::get('/admin/anggota/{member}', [MemberController::class, 'show'])->name('admin.members.show');
    Route::get('/admin/anggota/{member}/edit', [MemberController::class, 'edit'])->name('admin.members.edit');
    Route::put('/admin/anggota/{member}', [MemberController::class, 'update'])->name('admin.members.update');
    Route::post('/admin/anggota/{member}/nonaktifkan', [MemberController::class, 'deactivate'])->name('admin.members.deactivate');
});

// Test route for authorization middleware
Route::get('/test-ketua-umum', function () {
    return 'Access granted';
})->middleware(['auth', 'active', 'role:Ketua Umum']);

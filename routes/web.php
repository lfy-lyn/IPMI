<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\MembershipApplicationController;
use App\Http\Controllers\MembershipReviewController;
use App\Http\Controllers\AccountActivationController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\ClassRoomController;
use App\Http\Controllers\MemberClassController;
use App\Http\Controllers\ClassPromotionController;
use App\Http\Controllers\IncidentalActivityController;
use App\Http\Controllers\ActivityAttendanceController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\AnnouncementController;

// Public routes
Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/daftar', [MembershipApplicationController::class, 'create'])->name('membership.create');
Route::post('/daftar', [MembershipApplicationController::class, 'store'])->middleware('throttle:6,1')->name('membership.store');
Route::get('/daftar/sukses', [MembershipApplicationController::class, 'success'])->name('membership.success');

// Activation routes
Route::get('/aktivasi/{token}', [AccountActivationController::class, 'show'])->name('activation.show');
Route::post('/aktivasi/{token}', [AccountActivationController::class, 'activate'])->middleware('throttle:10,1')->name('activation.activate');
Route::get('/aktivasi-sukses', [AccountActivationController::class, 'success'])->name('activation.success');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', \App\Http\Controllers\DashboardController::class)->name('dashboard');
    
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
    Route::post('/admin/anggota/{member}/jabatan', [\App\Http\Controllers\MemberPositionController::class, 'assign'])->name('admin.members.positions.assign');
    Route::delete('/admin/anggota/{member}/jabatan/{position}', [\App\Http\Controllers\MemberPositionController::class, 'remove'])->name('admin.members.positions.remove');
    Route::post('/admin/anggota/{member}/nonaktifkan', [MemberController::class, 'deactivate'])->name('admin.members.deactivate');
    
    // Academic year routes
    Route::get('/admin/tahun-ajaran', [AcademicYearController::class, 'index'])->name('academic-years.index');
    Route::get('/admin/tahun-ajaran/tambah', [AcademicYearController::class, 'create'])->name('academic-years.create');
    Route::post('/admin/tahun-ajaran', [AcademicYearController::class, 'store'])->name('academic-years.store');
    Route::get('/admin/tahun-ajaran/{academicYear}/edit', [AcademicYearController::class, 'edit'])->name('academic-years.edit');
    Route::put('/admin/tahun-ajaran/{academicYear}', [AcademicYearController::class, 'update'])->name('academic-years.update');
    Route::post('/admin/tahun-ajaran/{academicYear}/aktifkan', [AcademicYearController::class, 'activate'])->name('academic-years.activate');
    Route::post('/admin/tahun-ajaran/{academicYear}/arsipkan', [AcademicYearController::class, 'archive'])->name('academic-years.archive');
    
    // Class routes
    Route::get('/admin/kelas', [ClassRoomController::class, 'index'])->name('classes.index');
    Route::get('/admin/kelas/tambah', [ClassRoomController::class, 'create'])->name('classes.create');
    Route::post('/admin/kelas', [ClassRoomController::class, 'store'])->name('classes.store');
    Route::get('/admin/kelas/{class}/edit', [ClassRoomController::class, 'edit'])->name('classes.edit');
    Route::put('/admin/kelas/{class}', [ClassRoomController::class, 'update'])->name('classes.update');
    
    // Member class assignment routes
    Route::get('/admin/penetapan-kelas', [MemberClassController::class, 'index'])->name('member-classes.index');
    Route::post('/admin/penetapan-kelas/{member}/tetapkan', [MemberClassController::class, 'assign'])->name('member-classes.assign');
    Route::delete('/admin/penetapan-kelas/{member}/hapus', [MemberClassController::class, 'remove'])->name('member-classes.remove');
    
    // Class Promotion Routes
    Route::get('/admin/kenaikan-kelas', [ClassPromotionController::class, 'index'])->name('class-promotion.index');
    Route::post('/admin/kenaikan-kelas/preview', [ClassPromotionController::class, 'preview'])->name('class-promotion.preview');
    Route::post('/admin/kenaikan-kelas/proses', [ClassPromotionController::class, 'process'])->name('class-promotion.process');

    // Activities Routes
    Route::get('/admin/kegiatan', [IncidentalActivityController::class, 'index'])->name('activities.index');
    Route::get('/admin/kegiatan/create', [IncidentalActivityController::class, 'create'])->name('activities.create');
    Route::post('/admin/kegiatan', [IncidentalActivityController::class, 'store'])->name('activities.store');
    Route::get('/admin/kegiatan/{activity}', [IncidentalActivityController::class, 'show'])->name('activities.show');
    Route::get('/admin/kegiatan/{activity}/edit', [IncidentalActivityController::class, 'edit'])->name('activities.edit');
    Route::put('/admin/kegiatan/{activity}', [IncidentalActivityController::class, 'update'])->name('activities.update');
    Route::post('/admin/kegiatan/{activity}/arsipkan', [IncidentalActivityController::class, 'archive'])->name('activities.archive');

    // Attendance Routes
    Route::get('/admin/kegiatan/{activity}/absensi', [ActivityAttendanceController::class, 'index'])->name('activities.attendance.index');
    Route::post('/admin/kegiatan/{activity}/absensi', [ActivityAttendanceController::class, 'record'])->name('activities.attendance.record');
    Route::put('/admin/absensi/{attendance}', [ActivityAttendanceController::class, 'update'])->name('activities.attendance.update');
    Route::get('/admin/kegiatan/{activity}/rekap', [ActivityAttendanceController::class, 'summary'])->name('activities.attendance.summary');

    // Materials Routes
    // Program rutin (kegiatan berulang) + sesi + absensi sesi
    Route::get('/admin/program', [\App\Http\Controllers\ProgramController::class, 'index'])->name('programs.index');
    Route::get('/admin/program/tambah', [\App\Http\Controllers\ProgramController::class, 'create'])->name('programs.create');
    Route::post('/admin/program', [\App\Http\Controllers\ProgramController::class, 'store'])->name('programs.store');
    Route::get('/admin/program/{program}', [\App\Http\Controllers\ProgramController::class, 'show'])->name('programs.show');
    Route::get('/admin/program/{program}/edit', [\App\Http\Controllers\ProgramController::class, 'edit'])->name('programs.edit');
    Route::put('/admin/program/{program}', [\App\Http\Controllers\ProgramController::class, 'update'])->name('programs.update');
    Route::post('/admin/program/{program}/sesi', [\App\Http\Controllers\ProgramSessionController::class, 'store'])->name('programs.sessions.store');
    Route::get('/admin/program/{program}/sesi/{session}/absensi', [\App\Http\Controllers\ProgramSessionController::class, 'attendance'])->name('programs.sessions.attendance');
    Route::post('/admin/program/{program}/sesi/{session}/absensi', [\App\Http\Controllers\ProgramSessionController::class, 'record'])->name('programs.sessions.record');

    Route::get('/admin/materi', [MaterialController::class, 'index'])->name('materials.index');
    Route::get('/admin/materi/create', [MaterialController::class, 'create'])->name('materials.create');
    Route::post('/admin/materi', [MaterialController::class, 'store'])->name('materials.store');
    Route::get('/admin/materi/{material}', [MaterialController::class, 'show'])->name('materials.show');
    Route::get('/admin/materi/{material}/edit', [MaterialController::class, 'edit'])->name('materials.edit');
    Route::put('/admin/materi/{material}', [MaterialController::class, 'update'])->name('materials.update');
    Route::get('/admin/materi/{material}/download', [MaterialController::class, 'download'])->name('materials.download');
    Route::post('/admin/materi/{material}/arsipkan', [MaterialController::class, 'archive'])->name('materials.archive');

    // Announcements Routes
    Route::get('/admin/pengumuman', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/admin/pengumuman/create', [AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('/admin/pengumuman', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('/admin/pengumuman/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');
    Route::get('/admin/pengumuman/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit');
    Route::put('/admin/pengumuman/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::post('/admin/pengumuman/{announcement}/arsipkan', [AnnouncementController::class, 'archive'])->name('announcements.archive');
});


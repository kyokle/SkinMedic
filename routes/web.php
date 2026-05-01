<?php

use App\Http\Controllers\IndexController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AddProductController;
use App\Http\Controllers\AddServiceController;
use App\Http\Controllers\SkinAnalysisController;
use App\Http\Controllers\BookAppointmentController;
use App\Http\Controllers\ReviewController;

use App\Http\Controllers\PatientPageController;
use App\Http\Controllers\PatientServicesController;
use App\Http\Controllers\PatientBookingsController;
use App\Http\Controllers\PatientProfileController;
use App\Http\Controllers\PatientReviewsController;
use App\Http\Controllers\PatientAR_Skin_AnalysisController;

use App\Http\Controllers\DoctorPageController;
use App\Http\Controllers\DoctorBookingsController;
use App\Http\Controllers\DoctorProfileController;

use App\Http\Controllers\AdminPageController;
use App\Http\Controllers\AdminAddAccountController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\AdminBookingsController;
use App\Http\Controllers\AdminManageUsersController;
use App\Http\Controllers\AdminServicesController;
use App\Http\Controllers\AdminInventoryController;
use App\Http\Controllers\AdminProductsController;
use App\Http\Controllers\AdminReviewsController;

use App\Http\Controllers\StaffPageController;
use App\Http\Controllers\StaffProfileController;
use App\Http\Controllers\StaffBookingsController;
use App\Http\Controllers\StaffServicesController;
use App\Http\Controllers\StaffInventoryController;
use App\Http\Controllers\StaffProductsController;
use App\Http\Controllers\StaffReviewsController;


// ── Landing, Auth, & Booking ──────────────────────────────
Route::get('/',                 [IndexController::class, 'index'])->name('index');
Route::get('/login',            [IndexController::class, 'index'])->name('login');
Route::post('/login',           [IndexController::class, 'login']);
Route::post('/admin-login',     [IndexController::class, 'adminLogin']);
Route::post('/signup',          [IndexController::class, 'signup']);
Route::post('/forgot-password', [IndexController::class, 'forgotPassword']);
Route::post('/verify-otp',      [IndexController::class, 'verifyResetOtp']);
Route::post('/reset-password',  [IndexController::class, 'resetPassword']);
Route::get('/logout',           [AuthController::class,  'logout'])->name('logout');
Route::get('/book-appointment', [BookAppointmentController::class, 'show'])->name('book.appointment.show');
Route::post('/book-appointment', [BookAppointmentController::class, 'store'])->name('book.appointment.store');
Route::get('/get-available-times', [BookAppointmentController::class, 'getAvailableTimes']);


// Review
Route::post('/reviews',          [ReviewController::class, 'store'])->name('reviews.store');
Route::delete('/reviews/{id}',   [ReviewController::class, 'destroy'])->name('reviews.destroy');


// ── Patient ─────────────────────────────────────
Route::get('/patient',                          [PatientPageController::class,    'index'])->name('patient.home');
Route::post('/patient/cancel',                  [PatientPageController::class,     'cancel'])->name('patient.cancel');

// ── Patient Service ──────────────────────────────────
Route::get('/patient/services',                 [PatientServicesController::class, 'index'])->name('patient.services');

// ── Patient Bookings ──────────────────────────────────
Route::get('/patient/bookings',                 [PatientBookingsController::class, 'index'])->name('patient.bookings');

// ── Patient Profile ──────────────────────────────────
Route::get('/patient/profile',                  [PatientProfileController::class,  'index'])->name('patient.profile');
Route::post('/patient/profile/upload-pic',      [PatientProfileController::class,  'uploadPic'])->name('patient.profile.upload-pic');
Route::post('/patient/profile/update-personal', [PatientProfileController::class,  'updatePersonal'])->name('patient.profile.update-personal');
Route::post('/patient/profile/update-medical',  [PatientProfileController::class,  'updateMedical'])->name('patient.profile.update-medical');

// ── Patient Reviews ──────────────────────────────────
Route::get('/patient/reviews', [PatientReviewsController::class, 'index'])->name('patient.reviews');
Route::post('/patient/reviews', [PatientReviewsController::class, 'store'])->name('patient.reviews.store');

// ── Patient AR Skin Analysis ──
Route::get('/patient/skin-analysis',         [PatientAR_Skin_AnalysisController::class, 'index'])->name('patient.skin-analysis');
Route::post('/patient/skin-analysis/analyze',[PatientAR_Skin_AnalysisController::class, 'analyze'])->name('patient.skin-analysis.analyze');
Route::get('/patient/skin-analysis/result',  [PatientAR_Skin_AnalysisController::class, 'result'])->name('patient.skin-analysis.result');

// ── Doctor ──────────────────────────────────────
Route::get('/doctor',          [DoctorPageController::class,    'index'])->name('doctor.home');

// ── Doctor Bookings ──────────────────────────────────────
Route::get('/doctor/bookings', [DoctorBookingsController::class, 'index'])->name('doctor.bookings');

// ── Doctor Profile ──────────────────────────────────────
Route::get('/doctor/profile',  [DoctorProfileController::class,  'show'])->name('doctor.profile');
Route::post('/doctor/profile/upload-pic',      [DoctorProfileController::class, 'uploadPic'])->name('doctor.profile.upload-pic');
Route::post('/doctor/profile/upload', [DoctorProfileController::class, 'upload'])->name('doctor.profile.upload');
Route::post('/doctor/profile/update-personal', [DoctorProfileController::class, 'updatePersonal'])->name('doctor.profile.update-personal');
Route::post('/doctor/profile/update-doctor', [DoctorProfileController::class, 'updateDoctor'])->name('doctor.profile.update-doctor');


// ── Staff ───────────────────────────────────────
Route::get('/staff',           [StaffPageController::class,     'index'])->name('staff.home');

// ── Staff Bookings ───────────────────────────────────────
Route::get('/staff/bookings',  [StaffBookingsController::class,  'index'])->name('staff.bookings');
Route::post('/staff/bookings/update-status', [StaffBookingsController::class, 'updateStatus'])->name('staff.bookings.update-status');
Route::get('/staff/bookings', [StaffBookingsController::class, 'index'])
    ->name('staff.bookings')
    ->middleware('check.staff.role');

// ── Staff Services ───────────────────────────────────────
Route::get('/staff/services',  [StaffServicesController::class,  'index'])->name('staff.services');
Route::post('/staff/services/store',   [StaffServicesController::class, 'store'])->name('staff.services.store');
Route::post('/staff/services/update',  [StaffServicesController::class, 'update'])->name('staff.services.update');
Route::post('/staff/services/delete',  [StaffServicesController::class, 'delete'])->name('staff.services.delete');

// ── Staff Inventory ───────────────────────────────────────
Route::get('/staff/inventory', [StaffInventoryController::class, 'index'])
    ->name('staff.inventory')
    ->middleware('check.staff.role');

Route::post('/staff/inventory/deduct-stock', [StaffInventoryController::class, 'deductStock'])
     ->name('staff.inventory.deduct-stock');

Route::post('/staff/inventory/add-stock', [StaffInventoryController::class, 'addStock'])
    ->name('staff.inventory.add-stock')
    ->middleware('check.staff.role');

// ── Staff Products ───────────────────────────────────────
Route::get('/staff/products',  [StaffProductsController::class,  'index'])->name('staff.products');
Route::post('/staff/products/store', [StaffProductsController::class, 'store'])->name('staff.products.store');
Route::post('/staff/products/update',[StaffProductsController::class, 'update'])->name('staff.products.update');
Route::post('/staff/products/delete',[StaffProductsController::class, 'delete'])->name('staff.products.delete');

// ── Staff Profile ───────────────────────────────────────
Route::get('staff/profile', [StaffProfileController::class, 'index'])
    ->name('staff.profile')
    ->middleware('check.staff.role');

Route::post('staff/profile/upload-pic', [StaffProfileController::class, 'uploadPic'])
    ->name('staff.profile.upload-pic')
    ->middleware('check.staff.role');

Route::post('/staff/profile/update-personal', [StaffProfileController::class, 'updatePersonal'])
    ->name('staff.profile.update-personal')
    ->middleware('check.staff.role');

Route::post('/staff/profile/update-employment', [StaffProfileController::class, 'updateEmployment'])
    ->name('staff.profile.update-employment')
    ->middleware('check.staff.role');

// ── Staff Reviews ───────────────────────────────────────
Route::get('/staff/reviews',          [StaffReviewsController::class, 'index'])->name('staff.reviews');
Route::delete('/staff/reviews/{id}',  [StaffReviewsController::class, 'destroy'])->name('staff.reviews.destroy');


// ── Admin ────────────────────────────────────────
Route::get('/admin',                             [AdminPageController::class,         'index'])->name('admin.home');

// ── Admin Bookings ──────────────────────────────────
Route::get('/admin/bookings',                    [AdminBookingsController::class,     'index'])->name('admin.bookings');
Route::post('/admin/bookings/update-status',     [AdminBookingsController::class,     'updateStatus'])->name('admin.bookings.update-status');

// ── Admin Reviews ──────────────────────────────────
Route::get('/admin/reviews',          [AdminReviewsController::class, 'index'])->name('admin.reviews');
Route::delete('/admin/reviews/{id}',  [AdminReviewsController::class, 'destroy'])->name('admin.reviews.destroy');

// ── Admin Service ──────────────────────────────────
Route::get('/admin/services',                    [AdminServicesController::class,     'index'])->name('admin.services');
Route::post('/admin/services/add',                [AdminServicesController::class, 'add'])->name('admin.services.add');
Route::post('/admin/services/update',                [AdminServicesController::class, 'update'])->name('admin.services.update');
Route::post('/admin/services/delete',                [AdminServicesController::class, 'delete'])->name('admin.services.delete');

// ── Admin Product ──────────────────────────────────
Route::get('/admin/products',                    [AdminProductsController::class,     'index'])->name('admin.products');
Route::post('/admin/products/add',               [AdminProductsController::class,     'add'])->name('admin.products.add');
Route::post('/admin/products/update',            [AdminProductsController::class,     'update'])->name('admin.products.update');
Route::post('/admin/products/delete',            [AdminProductsController::class,     'delete'])->name('admin.products.delete');

// ── Admin Inventory ──────────────────────────────────
Route::get('/admin/inventory',                   [AdminInventoryController::class,    'index'])->name('admin.inventory');
Route::post('/admin/inventory/add-stock',        [AdminInventoryController::class,    'addStock'])->name('admin.inventory.add-stock');
Route::post('/admin/inventory/deduct-stock', [AdminInventoryController::class, 'deductStock'])
     ->name('admin.inventory.deduct-stock');

Route::post('/admin/inventory/add-stock', [AdminInventoryController::class, 'addStock'])
    ->name('admin.inventory.add-stock')
    ->middleware('check.staff.role');

// ── Admin Manage Users ──────────────────────────────────
Route::get('/admin/users',                       [AdminManageUsersController::class, 'index'])->name('admin.users');
Route::get('/admin/manage-users',                [AdminManageUsersController::class,  'index'])->name('admin.manage-users');
Route::post('/admin/manage-users/update',        [AdminManageUsersController::class,  'update'])->name('admin.manage-users.update');
Route::post('/admin/manage-users/delete',        [AdminManageUsersController::class,  'delete'])->name('admin.manage-users.delete');
Route::post('/admin/manage-users/set-preferred-time', [AdminManageUsersController::class, 'setPreferredTime'])->name('admin.manage-users.set-preferred-time');
Route::post('/admin/manage-users/remove-regular',     [AdminManageUsersController::class, 'removeRegular'])->name('admin.manage-users.remove-regular');

// ── Admin Add Account ──────────────────────────────────
Route::get('/admin/add-account',                 [AdminAddAccountController::class,   'index'])->name('admin.add-account');
Route::post('/admin/add-account',                [AdminAddAccountController::class,   'store'])->name('admin.add-account.store');

// ── Admin Profile ──────────────────────────────────
Route::get('/admin/profile',                     [AdminProfileController::class,      'index'])->name('admin.profile');
Route::post('/admin/profile/upload-pic',         [AdminProfileController::class,      'uploadPic'])->name('admin.profile.upload-pic');

// ── Admin Add Product ──────────────────────────────────
Route::get('/admin/add-product',  [AddProductController::class, 'index'])->name('admin.add-product');
Route::post('/admin/add-product', [AddProductController::class, 'store'])->name('products.store');

// ── Admin Add Service ──────────────────────────────────
Route::get('/admin/add-service',  [AddServiceController::class, 'index'])->name('admin.add-service');
Route::post('/admin/add-service', [AddServiceController::class, 'store'])->name('services.store');


// ── Skin Analysis ────────────────────────────────────────
Route::get('/skin-analysis',         [SkinAnalysisController::class, 'index'])->name('skin-analysis.index');
Route::post('/skin-analysis/analyze',[SkinAnalysisController::class, 'analyze'])->name('skin-analysis.analyze');
Route::get('/skin-analysis/result', [SkinAnalysisController::class, 'result'])->name('skin-analysis.result');
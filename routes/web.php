<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\RateController;
use App\Http\Controllers\Admin\VehicleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ShipmentController;
use App\Http\Controllers\Admin\ShipmentItemController;
use App\Http\Controllers\Admin\ShipmentTrackingController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ManagerReportController;
use App\Http\Controllers\Admin\CourierTaskController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Customer\PortalController;
use App\Http\Controllers\Customer\AddressController as CustomerAddressController;
use App\Http\Controllers\Customer\ShipmentController as CustomerShipmentController;
use App\Http\Controllers\Customer\PaymentController as CustomerPaymentController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/track', [TrackingController::class, 'index'])->name('track.index');
Route::post('/track', [TrackingController::class, 'search'])->name('track.search');

// ======================
// HALAMAN UNTUK GUEST
// ======================
Route::middleware('guest')->group(function () {
    // halaman login tunggal untuk semua role
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->name('auth.authenticate');

    // alias route lama agar link lama tetap jalan
    Route::get('/login-customer', fn () => redirect()->route('login'))->name('auth.login_customer');
    Route::get('/login-admin', fn () => redirect()->route('login'))->name('auth.login_admin');
    Route::post('/login-customer', [LoginController::class, 'authenticate'])->name('customer.login');
    Route::post('/login-admin', [LoginController::class, 'authenticate'])->name('admin.login');

    // register
    Route::get('/register', [RegisterController::class, 'create'])->name('auth.register');
    Route::post('/register', [RegisterController::class, 'store'])->name('auth.register.store');
});

// ======================
// HALAMAN SETELAH LOGIN
// ======================
Route::middleware(['auth', 'role:admin,cashier,courier,manager'])->group(function () {

    Route::resource('/dashboard', DashboardController::class)->only(['index']);

    // modul operasional inti
    Route::resource('/shipments', ShipmentController::class);
    Route::resource('/shipment-items', ShipmentItemController::class)->except(['show']);
    Route::resource('/shipment-trackings', ShipmentTrackingController::class)->except(['show']);
    Route::resource('/payments', PaymentController::class)->except(['show']);
    Route::get('/payments-verification', [PaymentController::class, 'verificationQueue'])->name('payments.verification');
    Route::patch('/payments/{payment}/verify', [PaymentController::class, 'verify'])->name('payments.verify');
    Route::patch('/payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
    Route::get('/manager-reports', [ManagerReportController::class, 'index'])->name('manager.reports');
    Route::get('/manager-reports/export', [ManagerReportController::class, 'export'])->name('manager.reports.export');
    Route::get('/courier-tasks', [CourierTaskController::class, 'index'])->name('courier.tasks');
    Route::patch('/courier-tasks/{shipment}/status', [CourierTaskController::class, 'updateStatus'])->name('courier.tasks.update-status');

    // modul master (admin)
    Route::middleware('role:admin')->group(function () {
        Route::resource('/branches', BranchController::class)->except(['show']);
        Route::resource('/rates', RateController::class)->except(['show']);
        Route::resource('/vehicles', VehicleController::class)->except(['show']);
    Route::resource('/users', UserController::class)->except(['show']);
    Route::resource('/customers', CustomerController::class)->only(['index', 'edit', 'update']);
    });

    // logout
    Route::post('/logout-admin', [LoginController::class, 'logoutAdmin'])->name('admin.logout');
});

Route::middleware('auth:customer')->group(function () {
    Route::get('/home', [PortalController::class, 'index'])->name('home.index');
    Route::get('/about', [PortalController::class, 'about'])->name('home.about');
    Route::get('/service', [PortalController::class, 'service'])->name('home.service');
    Route::get('/blog', [PortalController::class, 'blog'])->name('home.blog');
    Route::get('/contact', [PortalController::class, 'contact'])->name('home.contact');

    Route::resource('/customer-shipments', CustomerShipmentController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->names('customer.shipments');
    Route::resource('/customer-addresses', CustomerAddressController::class)
        ->except(['show'])
        ->names('customer.addresses');

    Route::resource('/customer-payments', CustomerPaymentController::class)
        ->only(['index', 'create', 'store'])
        ->names('customer.payments');
    Route::get('/customer-payments/{customer_payment}/invoice', [CustomerPaymentController::class, 'invoice'])->name('customer.payments.invoice');

    Route::get('/profile', [CustomerProfileController::class, 'edit'])->name('customer.profile.edit');
    Route::put('/profile', [CustomerProfileController::class, 'update'])->name('customer.profile.update');
    Route::post('/tracking-notifications/read', [PortalController::class, 'markTrackingNotificationsRead'])->name('customer.notifications.read');
    Route::get('/tracking-notifications/poll', [PortalController::class, 'pollTrackingNotifications'])->name('customer.notifications.poll');

    Route::post('/logout-customer', [LoginController::class, 'logoutCustomer'])->name('customer.logout');
});

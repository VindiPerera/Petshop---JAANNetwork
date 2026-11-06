<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Shared screens: branch users see only their shop, admin sees every branch (with ?branch_id= filter).
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::resource('customers', CustomerController::class)->except('destroy');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/profit', [ReportController::class, 'profit'])->name('reports.profit');
    Route::get('/reports/stock-value', [ReportController::class, 'stockValue'])->name('reports.stock');

    // Shop POS
    Route::middleware('role:branch')->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos');
        Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });

    // Main admin: warehouse, branches, catalogue, GRN transfers
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('branches', Admin\BranchController::class)->except(['show', 'destroy']);
        Route::resource('users', Admin\UserController::class)->except(['show', 'destroy']);
        Route::resource('products', Admin\ProductController::class)->except(['show', 'destroy']);
        Route::resource('categories', Admin\CategoryController::class)->only(['index', 'store', 'destroy']);
        Route::resource('suppliers', Admin\SupplierController::class)->only(['index', 'store', 'destroy']);
        Route::resource('purchases', Admin\PurchaseController::class)->only(['index', 'create', 'store', 'show']);
        Route::resource('transfers', Admin\TransferController::class)->only(['index', 'create', 'store', 'show']);
    });
});

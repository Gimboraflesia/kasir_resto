<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SalesDashboardController;
use App\Http\Controllers\Admin\SalesReportController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\CashierAccountController;
use App\Http\Controllers\Admin\RestaurantSettingsController;
use App\Http\Controllers\Cashier\PointOfSaleController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\Cashier\SalesReportController as CashierSalesReportController;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return auth()->user()->isAdmin()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('cashier.index');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/webhooks/midtrans', MidtransWebhookController::class)->name('webhooks.midtrans');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', SalesDashboardController::class)->name('dashboard');
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('products', ProductController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::post('/transactions/cancel-today', [TransactionController::class, 'cancelToday'])->name('transactions.cancel-today');
        Route::post('/transactions/{transaction}/cancel', [TransactionController::class, 'cancel'])->name('transactions.cancel');
        Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
        Route::get('/reports', [SalesReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [SalesReportController::class, 'export'])->name('reports.export');
        Route::get('/reports/export/pdf', [SalesReportController::class, 'exportPdf'])->name('reports.export.pdf');
        Route::get('/users', [CashierAccountController::class, 'index'])->name('users.index');
        Route::post('/users', [CashierAccountController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [CashierAccountController::class, 'update'])->name('users.update');
        Route::put('/users/{user}/password', [CashierAccountController::class, 'resetPassword'])->name('users.password');
        Route::get('/settings', [RestaurantSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [RestaurantSettingsController::class, 'update'])->name('settings.update');
    });
    Route::middleware('role:cashier,admin')->prefix('cashier')->name('cashier.')->group(function () {
        Route::get('/', [PointOfSaleController::class, 'index'])->name('index');
        Route::get('/reports', [CashierSalesReportController::class, 'index'])->name('reports.index');
        Route::post('/checkout', [PointOfSaleController::class, 'checkout'])->name('checkout');
        Route::get('/transactions', [PointOfSaleController::class, 'transactions'])->name('transactions.index');
        Route::get('/transactions/{transaction}/receipt', [PointOfSaleController::class, 'receipt'])->name('transactions.receipt');
    });
});





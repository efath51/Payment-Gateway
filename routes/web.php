<?php

use App\Http\Controllers\Admin\GatewayController;
use App\Http\Controllers\Admin\GatewayCurrencyController;
use App\Http\Controllers\BulkImportController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\Gateway\PaymentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::middleware(['auth', 'verified'])->group(function () {


    Route::prefix('gateway')->name('gateway.')->group(function () {
        Route::get('/', [PaymentController::class, 'CreateGateway'])->name('index');
        Route::get('/dataTesting', [DepositController::class, 'index'])->name('dataTesting');
    });

    Route::prefix('user')->name('user.')->group(function () {
        Route::get('cart', [PaymentController::class, 'cart'])->name('cart');
        Route::get('/deposit',  [DepositController::class, 'index'])->name('deposit.index');
        // Route::post('/deposit', [DepositController::class, 'store'])->name('deposit.store');
        Route::post('/deposit', [PaymentController::class, 'depositInsert'])->name('deposit.store');
        Route::get('/deposit/confirm', [PaymentController::class, 'depositConfirm'])->name('deposit.confirm');
    });

    Route::prefix('admin')->name('admin.')->group(function () {

        // Gateways
        Route::get('gateways',              [GatewayController::class, 'index'])->name('gateways.index');
        Route::get('gateways/create',       [GatewayController::class, 'create'])->name('gateways.create');
        Route::post('gateways',              [GatewayController::class, 'store'])->name('gateways.store');
        Route::get('gateways/{gateway}/edit', [GatewayController::class, 'edit'])->name('gateways.edit');
        Route::put('gateways/{gateway}',    [GatewayController::class, 'update'])->name('gateways.update');

        // Gateway currencies
        Route::get('gateway-currencies/create',          [GatewayCurrencyController::class, 'create'])->name('gateway-currencies.create');
        Route::post('gateway-currencies',                 [GatewayCurrencyController::class, 'store'])->name('gateway-currencies.store');
        Route::get('gateway-currencies/{currency}/edit', [GatewayCurrencyController::class, 'edit'])->name('gateway-currencies.edit');
        Route::put('gateway-currencies/{currency}',      [GatewayCurrencyController::class, 'update'])->name('gateway-currencies.update');
    });
});


Route::match(['GET', 'POST'], 'payment/success/{trx?}', [PaymentController::class, 'paymentSuccess'])
    ->name('payment.success');

Route::match(['GET', 'POST'], 'payment/fail/{trx?}', [PaymentController::class, 'paymentFail'])
    ->name('payment.fail');

Route::match(['GET', 'POST'], 'payment/cancel/{trx?}', [PaymentController::class, 'paymentCancel'])
    ->name('payment.cancel');

Route::post('/ipn/sslcommerz', [App\Http\Controllers\Gateway\Sslcommerz\ProcessController::class, 'ipn'])
    ->name('ipn.sslcommerz');

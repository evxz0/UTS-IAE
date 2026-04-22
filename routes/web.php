<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MidtransNotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    
    $role = auth()->user()->role;
    
    if ($role === 'kasir') {
        return redirect()->route('pos.index');
    }
    
    return redirect()->route('dashboard');
});

use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;

use App\Http\Controllers\DashboardController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Admin & Owner Dashboard
    Route::middleware(['role:admin,owner'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        Route::resource('products', ProductController::class);
        Route::resource('categories', CategoryController::class);
    });

    // Kasir POS
    Route::middleware(['role:kasir'])->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
        Route::post('/checkout', [\App\Http\Controllers\CheckoutController::class, 'store'])->name('checkout');
        Route::get('/receipt/{id}', [\App\Http\Controllers\ReceiptController::class, 'show'])->name('receipt.show');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ── Midtrans Webhook ─────────────────────────────────────────────────────────
// Route ini dipanggil oleh server Midtrans (bukan browser user),
// sehingga tidak butuh auth session maupun CSRF token.
// Pastikan URL ini sudah di-set di Midtrans Dashboard:
//   Sandbox → Settings → Configuration → Payment Notification URL
//   Isi dengan: https://<ngrok-url>/midtrans/notification
Route::post('/midtrans/notification', [MidtransNotificationController::class, 'handle'])
    ->name('midtrans.notification');
// ─────────────────────────────────────────────────────────────────────────────

require __DIR__.'/auth.php';

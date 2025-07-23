<?php
use App\Http\Controllers\GoalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuditController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/goals', [GoalController::class, 'index'])->name('goals.index');
    Route::get('/goals/create', [GoalController::class, 'create'])->name('goals.create');
    Route::post('/goals', [GoalController::class, 'store'])->middleware('throttle:10,1')->name('goals.store');
    Route::get('/goals/{goal}', [GoalController::class, 'show'])->name('goals.show');
    Route::get('/goals/{goal}/edit', [GoalController::class, 'edit'])->name('goals.edit');
    Route::put('/goals/{goal}', [GoalController::class, 'update'])->middleware('throttle:10,1')->name('goals.update');
    Route::delete('/goals/{goal}', [GoalController::class, 'destroy'])->middleware('throttle:10,1')->name('goals.destroy');
    Route::post('/goals/{goal}/deposit', [GoalController::class, 'deposit'])->middleware('throttle:5,1')->name('goals.addAmount');

    // Transaction routes
    Route::resource('transactions', TransactionController::class)->middleware([
        'store' => 'throttle:10,1',
        'update' => 'throttle:10,1',
        'destroy' => 'throttle:10,1'
    ]);

    // Budget routes
    Route::resource('budgets', BudgetController::class)->middleware([
        'store' => 'throttle:10,1',
        'update' => 'throttle:10,1',
        'destroy' => 'throttle:10,1'
    ]);

    // Debt routes
    Route::get('/debts', [DebtController::class, 'index'])->name('debts.index');
    Route::get('/debts/create', [DebtController::class, 'create'])->name('debts.create');
    Route::post('/debts', [DebtController::class, 'store'])->middleware('throttle:10,1')->name('debts.store');
    Route::get('/debts/plan', [DebtController::class, 'plan'])->name('debts.plan');
    Route::get('/debts/{debt}', [DebtController::class, 'show'])->name('debts.show');
    Route::get('/debts/{debt}/edit', [DebtController::class, 'edit'])->name('debts.edit');
    Route::put('/debts/{debt}', [DebtController::class, 'update'])->middleware('throttle:10,1')->name('debts.update');
    Route::delete('/debts/{debt}', [DebtController::class, 'destroy'])->middleware('throttle:10,1')->name('debts.destroy');
    Route::post('/debts/{debt}/payment', [DebtController::class, 'recordPayment'])->middleware('throttle:5,1')->name('debts.recordPayment');
    Route::post('/debts/{debt}/mark-paid', [DebtController::class, 'markAsPaid'])->middleware('throttle:5,1')->name('debts.markAsPaid');
    Route::post('/debts/{debt}/mark-active', [DebtController::class, 'markAsActive'])->middleware('throttle:5,1')->name('debts.markAsActive');

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/recent', [NotificationController::class, 'getRecent'])->name('notifications.recent');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::get('/notifications/unread-count', [NotificationController::class, 'getUnreadCount'])->name('notifications.unreadCount');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications/clear', [NotificationController::class, 'clear'])->name('notifications.clear');

    // Audit routes
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
    Route::get('/audit/{modelType}/{modelId}', [AuditController::class, 'show'])->name('audit.show');
});

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

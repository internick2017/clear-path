<?php
use App\Http\Controllers\GoalController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/goals', [GoalController::class, 'index'])->name('goals.index');
    Route::get('/goals/create', [GoalController::class, 'create'])->name('goals.create');
    Route::post('/goals', [GoalController::class, 'store'])->name('goals.store');
    Route::post('/goals/{id}/deposit', [GoalController::class, 'deposit'])->name('goals.addAmount');
    // Goals page route (already present as /goals, index)
});

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

use App\Models\Budget;
Route::get('/dashboard', function () {
    $budgets = auth()->user()->budgets()->get()->map(function($budget) {
        return [
            'id' => $budget->id,
            'category' => $budget->category,
            'limit' => $budget->limit,
            'spent' => $budget->spent,
        ];
    });
    return Inertia::render('Dashboard', [
        'budgets' => $budgets,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

use App\Http\Controllers\DebtController;
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/debts/plan', [DebtController::class, 'plan'])->name('debts.plan');
});

require __DIR__.'/auth.php';

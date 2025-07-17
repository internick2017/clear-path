<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

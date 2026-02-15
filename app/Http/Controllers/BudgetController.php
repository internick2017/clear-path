<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Transaction;
use App\Http\Requests\BudgetRequest;
use App\Services\CacheService;
use App\Services\QueryOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Carbon\Carbon;

class BudgetController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $currentMonth = $request->get('month', now()->format('Y-m'));
        $currentMonthDate = Carbon::parse($currentMonth . '-01');

        // Use optimized budget data with caching
        $budgetData = CacheService::getUserBudgets(Auth::user(), $currentMonth);

        // If no cached data or data is empty, get optimized budget data
        if (empty($budgetData['budgets'])) {
            $budgets = QueryOptimizationService::getBudgetData(
                Auth::user(),
                $currentMonthDate->month,
                $currentMonthDate->year
            );
        } else {
            // Transform cached budget data to match expected format
            $budgets = collect($budgetData['budgets'])->map(function($budget) {
                return (object) [
                    'id' => $budget->id ?? $budget['id'],
                    'category' => $budget->category ?? $budget['category'],
                    'limit' => (float) ($budget->limit ?? $budget['limit']),
                    'spent' => (float) ($budget->spent ?? $budget['spent']),
                    'actual_spent' => (float) ($budget->spent ?? $budget['spent']),
                    'remaining' => (float) ($budget->limit ?? $budget['limit']) - (float) ($budget->spent ?? $budget['spent']),
                    'percentage' => ($budget->limit ?? $budget['limit']) > 0 ? (($budget->spent ?? $budget['spent']) / ($budget->limit ?? $budget['limit'])) * 100 : 0,
                    'is_exceeded' => ($budget->spent ?? $budget['spent']) > ($budget->limit ?? $budget['limit']),
                ];
            });
        }

        // Get expense categories with caching
        $user = Auth::user();
        $categories = Cache::remember("expense_categories_" . $user->id, 900, function () use ($user) {
            return $user->transactions()
                ->where('type', 'expense')
                ->distinct()
                ->pluck('category')
                ->sort()
                ->values();
        });

        return Inertia::render('Budgets', [
            'budgets' => $budgets,
            'categories' => $categories,
            'currentMonth' => $currentMonth,
            'success' => session('success'),
        ]);
    }

    public function create()
    {
        // Get expense categories with caching
        $user = Auth::user();
        $categories = Cache::remember("expense_categories_" . $user->id, 900, function () use ($user) {
            return $user->transactions()
                ->where('type', 'expense')
                ->distinct()
                ->pluck('category')
                ->sort()
                ->values();
        });

        return Inertia::render('BudgetCreate', [
            'categories' => $categories
        ]);
    }

    public function store(BudgetRequest $request)
    {
        $data = $request->validated();

        // Convert month to first day of the month
        $data['month'] = Carbon::parse($data['month'] . '-01')->format('Y-m-d');

        $request->user()->budgets()->create($data);

        // Clear user cache after creating budget
        CacheService::clearUserCache($request->user());

        return redirect()->route('budgets.index')
            ->with('success', 'Budget created successfully');
    }

    public function edit(Budget $budget)
    {
        $this->authorize('update', $budget);

        // Get expense categories with caching
        $user = Auth::user();
        $categories = Cache::remember("expense_categories_" . $user->id, 900, function () use ($user) {
            return $user->transactions()
                ->where('type', 'expense')
                ->distinct()
                ->pluck('category')
                ->sort()
                ->values();
        });

        return Inertia::render('BudgetEdit', [
            'budget' => $budget,
            'categories' => $categories
        ]);
    }

    public function update(BudgetRequest $request, Budget $budget)
    {
        $this->authorize('update', $budget);

        $data = $request->validated();

        // Convert month to first day of the month
        $data['month'] = Carbon::parse($data['month'] . '-01')->format('Y-m-d');

        $budget->update($data);

        // Clear user cache after updating budget
        CacheService::clearUserCache($request->user());

        return redirect()->route('budgets.index')
            ->with('success', 'Budget updated successfully');
    }

    public function destroy(Request $request, Budget $budget)
    {
        $this->authorize('delete', $budget);

        $budget->delete();

        // Clear user cache after deleting budget
        CacheService::clearUserCache($request->user());

        return redirect()->route('budgets.index')
            ->with('success', 'Budget deleted successfully');
    }
}
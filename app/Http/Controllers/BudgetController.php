<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Transaction;
use App\Http\Requests\BudgetRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        
        // Get all budgets for the current month
        $budgets = Auth::user()->budgets()
            ->whereMonth('month', $currentMonthDate->month)
            ->whereYear('month', $currentMonthDate->year)
            ->get();

        // Get all transactions for the current month in one query
        $currentMonthTransactions = Auth::user()->transactions()
            ->where('type', 'expense')
            ->whereMonth('date', $currentMonthDate->month)
            ->whereYear('date', $currentMonthDate->year)
            ->get();

        // Calculate actual spending for each budget using the loaded transactions
        $budgets = $budgets->map(function($budget) use ($currentMonthTransactions) {
            $actualSpent = (float) $currentMonthTransactions
                ->where('category', $budget->category)
                ->sum('amount');
            
            $budget->actual_spent = $actualSpent;
            $budget->remaining = $budget->limit - $actualSpent;
            $budget->percentage = $budget->limit > 0 ? ($actualSpent / $budget->limit) * 100 : 0;
            $budget->is_exceeded = $actualSpent > $budget->limit;
            
            return $budget;
        });

        // Get available categories from transactions (cache this if possible)
        $categories = Auth::user()->transactions()
            ->where('type', 'expense')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return Inertia::render('Budgets', [
            'budgets' => $budgets,
            'categories' => $categories,
            'currentMonth' => $currentMonth,
            'success' => session('success'),
        ]);
    }

    public function create()
    {
        // Get expense categories from transactions
        $categories = Auth::user()->transactions()
            ->where('type', 'expense')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

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

        return redirect()->route('budgets.index')
            ->with('success', 'Budget created successfully');
    }

    public function edit(Budget $budget)
    {
        $this->authorize('update', $budget);

        $categories = Auth::user()->transactions()
            ->where('type', 'expense')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

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

        return redirect()->route('budgets.index')
            ->with('success', 'Budget updated successfully');
    }

    public function destroy(Budget $budget)
    {
        $this->authorize('delete', $budget);

        $budget->delete();

        return redirect()->route('budgets.index')
            ->with('success', 'Presupuesto eliminado exitosamente');
    }
} 
<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $currentMonth = $request->get('month', now()->format('Y-m'));
        
        $budgets = Auth::user()->budgets()
            ->whereMonth('month', Carbon::parse($currentMonth . '-01')->month)
            ->whereYear('month', Carbon::parse($currentMonth . '-01')->year)
            ->get();

        // Calculate actual spending for each budget
        $budgets = $budgets->map(function($budget) use ($currentMonth) {
            $actualSpent = Auth::user()->transactions()
                ->where('type', 'expense')
                ->where('category', $budget->category)
                ->whereMonth('date', Carbon::parse($currentMonth)->month)
                ->whereYear('date', Carbon::parse($currentMonth)->year)
                ->sum('amount');
            
            $budget->actual_spent = $actualSpent;
            $budget->remaining = $budget->limit - $actualSpent;
            $budget->percentage = $budget->limit > 0 ? ($actualSpent / $budget->limit) * 100 : 0;
            $budget->is_exceeded = $actualSpent > $budget->limit;
            
            return $budget;
        });

        // Get available categories from transactions
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'category' => 'required|string|max:255',
            'limit' => 'required|numeric|min:0.01',
            'month' => 'required|date_format:Y-m',
        ]);

        // Convert month to first day of the month
        $data['month'] = Carbon::parse($data['month'] . '-01')->format('Y-m-d');

        // Check if budget already exists for this category and month
        $existingBudget = Auth::user()->budgets()
            ->where('category', $data['category'])
            ->whereMonth('month', Carbon::parse($data['month'])->month)
            ->whereYear('month', Carbon::parse($data['month'])->year)
            ->first();

        if ($existingBudget) {
            return back()->withErrors(['category' => 'Ya existe un presupuesto para esta categoría en el mes seleccionado.']);
        }

        $request->user()->budgets()->create($data);

        return redirect()->route('budgets.index')
            ->with('success', 'Presupuesto creado exitosamente');
    }

    public function edit(Budget $budget)
    {
        // Ensure the budget belongs to the authenticated user
        if ($budget->user_id !== Auth::id()) {
            abort(403);
        }

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

    public function update(Request $request, Budget $budget)
    {
        // Ensure the budget belongs to the authenticated user
        if ($budget->user_id !== Auth::id()) {
            abort(403);
        }

        $data = $request->validate([
            'category' => 'required|string|max:255',
            'limit' => 'required|numeric|min:0.01',
            'month' => 'required|date_format:Y-m',
        ]);

        // Convert month to first day of the month
        $data['month'] = Carbon::parse($data['month'] . '-01')->format('Y-m-d');

        // Check if budget already exists for this category and month (excluding current budget)
        $existingBudget = Auth::user()->budgets()
            ->where('category', $data['category'])
            ->whereMonth('month', Carbon::parse($data['month'])->month)
            ->whereYear('month', Carbon::parse($data['month'])->year)
            ->where('id', '!=', $budget->id)
            ->first();

        if ($existingBudget) {
            return back()->withErrors(['category' => 'Ya existe un presupuesto para esta categoría en el mes seleccionado.']);
        }

        $budget->update($data);

        return redirect()->route('budgets.index')
            ->with('success', 'Presupuesto actualizado exitosamente');
    }

    public function destroy(Budget $budget)
    {
        // Ensure the budget belongs to the authenticated user
        if ($budget->user_id !== Auth::id()) {
            abort(403);
        }

        $budget->delete();

        return redirect()->route('budgets.index')
            ->with('success', 'Presupuesto eliminado exitosamente');
    }
} 
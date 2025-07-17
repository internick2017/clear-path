<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\BudgetService;
use App\Notifications\TransactionCategoryChangeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class TransactionController extends Controller
{
    protected $budgetService;

    public function __construct(BudgetService $budgetService)
    {
        $this->budgetService = $budgetService;
    }
    public function index(Request $request)
    {
        $query = Auth::user()->transactions()->orderBy('date', 'desc');
        
        // Filter by month if provided
        if ($request->filled('month')) {
            $query->whereMonth('date', $request->month);
        }
        
        // Filter by year if provided
        if ($request->filled('year')) {
            $query->whereYear('date', $request->year);
        }
        
        // Filter by type if provided
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        
        // Filter by category if provided
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        
        $transactions = $query->paginate(15);
        
        // Get unique categories for filter dropdown
        $categories = Auth::user()->transactions()
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();
        
        // Calculate monthly summary
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        $monthlyIncome = Auth::user()->transactions()
            ->where('type', 'income')
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->sum('amount');
            
        $monthlyExpenses = Auth::user()->transactions()
            ->where('type', 'expense')
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->sum('amount');
        
        $monthlySummary = [
            'income' => $monthlyIncome,
            'expenses' => $monthlyExpenses,
            'net' => $monthlyIncome - $monthlyExpenses
        ];
        
        return Inertia::render('Transactions', [
            'transactions' => $transactions,
            'categories' => $categories,
            'monthlySummary' => $monthlySummary,
            'filters' => $request->only(['month', 'year', 'type', 'category']),
            'success' => session('success'),
        ]);
    }

    public function create()
    {
        // Get existing categories for suggestions
        $categories = Auth::user()->transactions()
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();
            
        return Inertia::render('TransactionCreate', [
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:income,expense',
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'note' => 'nullable|string|max:1000',
        ]);

        $transaction = $request->user()->transactions()->create($data);

        // Check budget limit for expense transactions
        if ($transaction->type === 'expense') {
            $this->budgetService->checkBudgetLimit($request->user(), $transaction->category, $transaction->amount);
        }

        return redirect()->route('transactions.index')
            ->with('success', 'Transacción creada exitosamente');
    }

    public function edit(Transaction $transaction)
    {
        // Ensure the transaction belongs to the authenticated user
        if ($transaction->user_id !== Auth::id()) {
            abort(403);
        }
        
        $categories = Auth::user()->transactions()
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return Inertia::render('TransactionEdit', [
            'transaction' => $transaction,
            'categories' => $categories
        ]);
    }

    public function update(Request $request, Transaction $transaction)
    {
        // Ensure the transaction belongs to the authenticated user
        if ($transaction->user_id !== Auth::id()) {
            abort(403);
        }
        
        $data = $request->validate([
            'type' => 'required|in:income,expense',
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'note' => 'nullable|string|max:1000',
        ]);

        $previousCategory = $transaction->category;
        $transaction->update($data);

        // Notify if category changed
        if ($previousCategory !== $data['category']) {
            Notification::send($request->user(), new TransactionCategoryChangeNotification($transaction, $previousCategory));
        }

        // Check budget limit for expense transactions
        if ($transaction->type === 'expense') {
            $this->budgetService->checkBudgetLimit($request->user(), $transaction->category, $transaction->amount);
        }

        return redirect()->route('transactions.index')
            ->with('success', 'Transacción actualizada exitosamente');
    }

    public function destroy(Transaction $transaction)
    {
        // Ensure the transaction belongs to the authenticated user
        if ($transaction->user_id !== Auth::id()) {
            abort(403);
        }
        
        $transaction->delete();

        return redirect()->route('transactions.index')
            ->with('success', 'Transacción eliminada exitosamente');
    }
} 
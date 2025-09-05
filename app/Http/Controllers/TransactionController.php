<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Budget;
use App\Notifications\TransactionCategoryChangeNotification;
use App\Notifications\BudgetExceededNotification;
use App\Http\Requests\TransactionRequest;
use App\Services\AuditService;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class TransactionController extends Controller
{
    use AuthorizesRequests;

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

        // Filter by expense type if provided
        if ($request->filled('expense_type')) {
            $query->where('expense_type', $request->expense_type);
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

        $monthlyFixedExpenses = Auth::user()->transactions()
            ->where('type', 'expense')
            ->where('expense_type', 'fixed')
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->sum('amount');

        $monthlyVariableExpenses = Auth::user()->transactions()
            ->where('type', 'expense')
            ->where('expense_type', 'variable')
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->sum('amount');

        $monthlySummary = [
            'income' => $monthlyIncome,
            'expenses' => $monthlyExpenses,
            'fixed_expenses' => $monthlyFixedExpenses,
            'variable_expenses' => $monthlyVariableExpenses,
            'net' => $monthlyIncome - $monthlyExpenses
        ];

        return Inertia::render('Transactions', [
            'transactions' => $transactions,
            'categories' => $categories,
            'monthlySummary' => $monthlySummary,
            'filters' => $request->only(['month', 'year', 'type', 'category', 'expense_type']),
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

    public function store(TransactionRequest $request)
    {
        $transaction = $request->user()->transactions()->create($request->validated());

        // Log the transaction creation
        AuditService::logTransactionCreated($request->user(), $transaction, $request);

        // Clear user cache after creating transaction
        CacheService::clearUserCache($request->user());

        // Note: Budget checking and notification is handled by BudgetService
        // via the Transaction model's created event

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction created successfully');
    }

    public function edit(Transaction $transaction)
    {
        $this->authorize('update', $transaction);

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

    public function update(TransactionRequest $request, Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        $previousCategory = $transaction->category;
        $oldValues = $transaction->toArray();
        $transaction->update($request->validated());

        // Log the transaction update
        AuditService::logTransactionUpdated($request->user(), $transaction, $oldValues, $request);

        // Clear user cache after updating transaction
        CacheService::clearUserCache($request->user());

        // Notify if category changed
        if ($previousCategory !== $request->validated()['category']) {
            Notification::send($request->user(), new TransactionCategoryChangeNotification($transaction, $previousCategory));
        }

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction updated successfully');
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        $this->authorize('delete', $transaction);

        // Log the transaction deletion
        AuditService::logTransactionDeleted($request->user(), $transaction, $request);

        $transaction->delete();

        // Clear user cache after deleting transaction
        CacheService::clearUserCache($request->user());

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction deleted successfully');
    }
}
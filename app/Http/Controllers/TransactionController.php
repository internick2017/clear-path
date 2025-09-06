<?php

namespace App\Http\Controllers;

use App\Helpers\CurrencyHelper;
use App\Models\Transaction;
use App\Models\Budget;
use App\Models\Debt;
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

        // Get active debts for debt payment option
        $debts = Auth::user()->debts()
            ->where('status', 'active')
            ->select('id', 'name', 'amount')
            ->get();

        return Inertia::render('TransactionCreate', [
            'categories' => $categories,
            'debts' => $debts
        ]);
    }

    public function store(TransactionRequest $request)
    {
        $validatedData = $request->validated();
        $debtPayment = null;
        
        if ($validatedData['debt_id'] && $validatedData['type'] === 'expense') {
            $debt = Debt::find($validatedData['debt_id']);
            
            if (!$debt || $debt->user_id !== $request->user()->id) {
                return redirect()->back()
                    ->withErrors(['debt_id' => 'Deuda no encontrada o no autorizada.']);
            }

            // Check what type of debt operation this is
            if ($validatedData['is_debt_payment'] ?? false) {
                // Actual debt payment - reduces debt balance
                $result = $debt->createPaymentTransaction(
                    $validatedData['amount'],
                    $validatedData['date'],
                    null, // payment method not in form yet
                    $validatedData['note']
                );
                
                $transaction = $result['transaction'];
                $debtPayment = $result['payment'];
                $actionMessage = 'Transacción y pago de deuda creados exitosamente';
                
            } elseif ($validatedData['is_debt_purchase'] ?? false) {
                // Purchase with credit card - increases debt balance
                $transaction = $debt->addPurchase(
                    $validatedData['amount'],
                    $validatedData['date'],
                    $validatedData['category'],
                    $validatedData['note']
                );
                
                $actionMessage = 'Transacción creada y deuda aumentada exitosamente';
                
            } else {
                // Just linked for tracking - no debt balance change
                $transaction = $request->user()->transactions()->create($validatedData);
                $actionMessage = 'Transacción vinculada a deuda para seguimiento';
            }
        } else {
            // Regular transaction creation
            $transaction = $request->user()->transactions()->create($validatedData);
            $actionMessage = 'Transaction created successfully';
        }

        // Log the transaction creation
        AuditService::logTransactionCreated($request->user(), $transaction, $request);

        // Clear user cache after creating transaction
        CacheService::clearUserCache($request->user());

        // Note: Budget checking and notification is handled by BudgetService
        // via the Transaction model's created event

        return redirect()->route('transactions.index')
            ->with('success', $actionMessage);
    }

    public function edit(Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        $categories = Auth::user()->transactions()
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        // Get active debts for debt payment option
        $debts = Auth::user()->debts()
            ->where('status', 'active')
            ->select('id', 'name', 'amount')
            ->get();

        return Inertia::render('TransactionEdit', [
            'transaction' => $transaction,
            'categories' => $categories,
            'debts' => $debts
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
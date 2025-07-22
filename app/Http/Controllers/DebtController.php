<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Http\Requests\DebtRequest;
use App\Http\Requests\DebtPaymentRequest;
use Illuminate\Http\Request;
use App\Services\DebtPayoffService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class DebtController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $activeDebts = Auth::user()->debts()->active()->with('payments')->get()->map(function($debt) {
            return [
                'id' => $debt->id,
                'name' => $debt->name,
                'amount' => $debt->amount,
                'interest_rate' => $debt->interest_rate,
                'minimum_payment' => $debt->minimum_payment,
                'due_date' => $debt->due_date,
                'status' => $debt->status,
                'remaining_balance' => $debt->getRemainingBalance(),
                'total_paid' => $debt->getTotalPaidAmount(),
                'payment_progress' => $debt->payment_progress,
                'payments_count' => $debt->payments->count(),
            ];
        });

        $paidDebts = Auth::user()->debts()->paid()->with('payments')->get()->map(function($debt) {
            return [
                'id' => $debt->id,
                'name' => $debt->name,
                'amount' => $debt->amount,
                'paid_at' => $debt->paid_at,
                'total_paid' => $debt->getTotalPaidAmount(),
                'total_interest_paid' => $debt->getTotalInterestPaid(),
                'payments_count' => $debt->payments->count(),
            ];
        });
        
        return Inertia::render('Debts', [
            'activeDebts' => $activeDebts,
            'paidDebts' => $paidDebts,
            'success' => session('success'),
        ]);
    }

    public function create()
    {
        return Inertia::render('DebtCreate');
    }

    public function store(DebtRequest $request)
    {
        $debt = Auth::user()->debts()->create($request->validated());

        return redirect()->route('debts.index')
            ->with('success', 'Debt created successfully');
    }

    public function show(Debt $debt)
    {
        $this->authorize('view', $debt);

        $debt->load('payments');
        $payments = $debt->payments()->orderBy('payment_date', 'desc')->get();

        return Inertia::render('DebtDetails', [
            'debt' => [
                'id' => $debt->id,
                'name' => $debt->name,
                'amount' => $debt->amount,
                'interest_rate' => $debt->interest_rate,
                'minimum_payment' => $debt->minimum_payment,
                'due_date' => $debt->due_date,
                'status' => $debt->status,
                'remaining_balance' => $debt->getRemainingBalance(),
                'total_paid' => $debt->getTotalPaidAmount(),
                'total_principal_paid' => $debt->getTotalPrincipalPaid(),
                'total_interest_paid' => $debt->getTotalInterestPaid(),
                'payment_progress' => $debt->payment_progress,
            ],
            'payments' => $payments,
            'success' => session('success'),
        ]);
    }

    public function edit(Debt $debt)
    {
        $this->authorize('update', $debt);

        return Inertia::render('DebtEdit', [
            'debt' => $debt
        ]);
    }

    public function update(DebtRequest $request, Debt $debt)
    {
        $this->authorize('update', $debt);

        $debt->update($request->validated());

        return redirect()->route('debts.index')
            ->with('success', 'Debt updated successfully');
    }

    public function destroy(Debt $debt)
    {
        $this->authorize('delete', $debt);

        $debt->delete();

        return redirect()->route('debts.index')
            ->with('success', 'Debt deleted successfully');
    }

    public function recordPayment(DebtPaymentRequest $request, Debt $debt)
    {
        $this->authorize('recordPayment', $debt);

        $data = $request->validated();

        $debt->addPayment(
            $data['amount'],
            $data['payment_date'],
            $data['payment_method'] ?? null,
            $data['note'] ?? null
        );

        $message = 'Payment recorded successfully';
        if ($debt->fresh()->status === 'paid') {
            $message = 'Debt paid completely! 🎉';
        }

        return redirect()->back()->with('success', $message);
    }

    public function plan(Request $request, DebtPayoffService $service)
    {
        $method = $request->query('method', 'snowball');
        $extra_payment = (float) $request->query('extra_payment', 0);
        $user = Auth::user();
        $plan = $service->generatePlan($user, $method, $extra_payment);
        return Inertia::render('DebtPlan', [
            'plan' => $plan,
            'method' => $method,
            'extra_payment' => $extra_payment
        ]);
    }

    public function markAsPaid(Debt $debt)
    {
        $this->authorize('markAsPaid', $debt);

        $debt->markAsPaid();

        return redirect()->back()->with('success', 'Debt marked as paid successfully');
    }

    public function markAsActive(Debt $debt)
    {
        $this->authorize('markAsActive', $debt);

        $debt->markAsActive();

        return redirect()->back()->with('success', 'Debt marked as active successfully');
    }
}

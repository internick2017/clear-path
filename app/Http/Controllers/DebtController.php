<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Debt;
use Illuminate\Http\Request;
use App\Services\DebtPayoffService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DebtController extends Controller
{
    public function index()
    {
        $activeDebts = Auth::user()->debts()->active()->get();
        $paidDebts = Auth::user()->debts()->paid()->get();
        
        return Inertia::render('Debts', [
            'activeDebts' => $activeDebts,
            'paidDebts' => $paidDebts,
            'success' => session('success'),
        ]);
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
        // Ensure the debt belongs to the authenticated user
        if ($debt->user_id !== Auth::id()) {
            abort(403);
        }

        $debt->markAsPaid();

        return redirect()->back()->with('success', 'Deuda marcada como pagada exitosamente');
    }

    public function markAsActive(Debt $debt)
    {
        // Ensure the debt belongs to the authenticated user
        if ($debt->user_id !== Auth::id()) {
            abort(403);
        }

        $debt->markAsActive();

        return redirect()->back()->with('success', 'Deuda marcada como activa exitosamente');
    }
}

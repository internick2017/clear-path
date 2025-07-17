<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Services\DebtPayoffService;
use Illuminate\Support\Facades\Auth;

class DebtController extends Controller
{
    public function plan(Request $request, DebtPayoffService $service)
    {
        $method = $request->query('method', 'snowball');
        $extra_payment = (float) $request->query('extra_payment', 0);
        $user = Auth::user();
        $plan = $service->generatePlan($user, $method, $extra_payment);
        return \Inertia\Inertia::render('DebtPlan', [
            'plan' => $plan,
            'method' => $method,
            'extra_payment' => $extra_payment
        ]);
    }
}

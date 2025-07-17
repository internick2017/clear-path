<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GoalController extends Controller
{
    public function index()
    {
        $goals = Auth::user()->goals()->get();
        return Inertia::render('Goals', [
            'goals' => $goals,
        ]);
    }

    public function deposit(Request $request, $id)
    {
        $goal = Goal::where('user_id', Auth::id())->findOrFail($id);
        $amount = (float) $request->input('amount', 0);
        if ($amount <= 0) {
            return back()->withErrors(['amount' => 'La cantidad debe ser mayor a cero.']);
        }
        $newAmount = $goal->current_amount + $amount;
        if ($newAmount > $goal->target_amount) {
            $goal->current_amount = $goal->target_amount;
        } else {
            $goal->current_amount = $newAmount;
        }
        $goal->save();
        $message = null;
        if ($goal->current_amount >= $goal->target_amount) {
            $message = '🎉 ¡Meta alcanzada!';
        }
        return redirect()->route('goals.index')->with('success', $message);
    }
}

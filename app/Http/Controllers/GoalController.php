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
            'success' => session('success'),
        ]);
    }

    public function create()
    {
        return Inertia::render('GoalCreate');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:100',
            'target_amount' => 'required|numeric|min:1',
            'deadline' => 'required|date|after:today',
        ]);

        $request->user()->goals()->create([
            'title' => $data['title'],
            'target_amount' => $data['target_amount'],
            'current_amount' => 0,
            'deadline' => $data['deadline'],
        ]);

        return redirect()->route('goals.index')->with('success', 'Meta creada con éxito');
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

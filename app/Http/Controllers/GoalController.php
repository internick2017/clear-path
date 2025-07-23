<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Http\Requests\GoalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class GoalController extends Controller
{
    use AuthorizesRequests;

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

    public function store(GoalRequest $request)
    {
        $data = $request->validated();

        $request->user()->goals()->create([
            'title' => $data['title'],
            'target_amount' => $data['target_amount'],
            'current_amount' => 0,
            'deadline' => $data['deadline'],
        ]);

        return redirect()->route('goals.index')->with('success', 'Goal created successfully');
    }

    public function show(Goal $goal)
    {
        $this->authorize('view', $goal);

        return Inertia::render('Goals/Show', [
            'goal' => $goal
        ]);
    }

    public function edit(Goal $goal)
    {
        $this->authorize('update', $goal);

        return Inertia::render('GoalEdit', [
            'goal' => $goal
        ]);
    }

    public function update(GoalRequest $request, Goal $goal)
    {
        $this->authorize('update', $goal);

        $goal->update($request->validated());

        return redirect()->route('goals.index')
            ->with('success', 'Goal updated successfully');
    }

    public function destroy(Goal $goal)
    {
        $this->authorize('delete', $goal);

        $goal->delete();

        return redirect()->route('goals.index')
            ->with('success', 'Meta eliminada exitosamente');
    }

    public function deposit(Request $request, Goal $goal)
    {
        $this->authorize('deposit', $goal);
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
            $message = '🎉 Goal reached!';
        }
        return redirect()->route('goals.index')->with('success', $message);
    }
}

<?php

namespace App\Http\Controllers\Settings;

use App\Helpers\CurrencyHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CurrencyController extends Controller
{
    public function show()
    {
        return Inertia::render('Settings/Currency');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'display_currency' => [
                'required', 
                'string', 
                'in:' . implode(',', array_keys(config('currencies.supported')))
            ],
        ]);

        $user = Auth::user();
        $user->update(['display_currency' => $validated['display_currency']]);

        $currencyName = config("currencies.supported.{$validated['display_currency']}.name");
        
        return redirect()->back()->with('success', 
            "Moneda de visualización actualizada a {$currencyName} exitosamente."
        );
    }
}

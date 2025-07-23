<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuditController extends Controller
{
    /**
     * Display the user's audit logs
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $logs = AuditService::getUserLogs($user, 100);

        return Inertia::render('Audit/Index', [
            'logs' => $logs,
            'success' => session('success'),
        ]);
    }

    /**
     * Display audit logs for a specific model
     */
    public function show(Request $request, string $modelType, int $modelId)
    {
        $user = Auth::user();
        $logs = AuditService::getModelLogs($modelType, $modelId, 50);

        return Inertia::render('Audit/Show', [
            'logs' => $logs,
            'modelType' => $modelType,
            'modelId' => $modelId,
        ]);
    }
}

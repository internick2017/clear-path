<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    /**
     * Log a financial operation
     */
    public static function log(
        User $user,
        string $action,
        Model $model,
        array $oldValues = null,
        array $newValues = null,
        string $description = null,
        Request $request = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'description' => $description,
        ]);
    }

    /**
     * Log a transaction creation
     */
    public static function logTransactionCreated(User $user, Model $transaction, Request $request = null): AuditLog
    {
        return self::log(
            $user,
            'transaction_created',
            $transaction,
            null,
            $transaction->toArray(),
            "Transaction created: {$transaction->type} - {$transaction->category} - $" . number_format($transaction->amount, 2),
            $request
        );
    }

    /**
     * Log a transaction update
     */
    public static function logTransactionUpdated(User $user, Model $transaction, array $oldValues, Request $request = null): AuditLog
    {
        $changes = array_diff_assoc($transaction->toArray(), $oldValues);
        $changeDescriptions = [];

        foreach ($changes as $key => $value) {
            if (is_array($value)) {
                $changeDescriptions[] = "$key: " . json_encode($value);
            } else {
                $changeDescriptions[] = "$key: " . (string) $value;
            }
        }

        $description = "Transaction updated: " . implode(', ', $changeDescriptions);

        return self::log(
            $user,
            'transaction_updated',
            $transaction,
            $oldValues,
            $transaction->toArray(),
            $description,
            $request
        );
    }

    /**
     * Log a transaction deletion
     */
    public static function logTransactionDeleted(User $user, Model $transaction, Request $request = null): AuditLog
    {
        return self::log(
            $user,
            'transaction_deleted',
            $transaction,
            $transaction->toArray(),
            null,
            "Transaction deleted: {$transaction->type} - {$transaction->category} - $" . number_format($transaction->amount, 2),
            $request
        );
    }

    /**
     * Log a budget operation
     */
    public static function logBudgetOperation(User $user, string $action, Model $budget, array $oldValues = null, Request $request = null): AuditLog
    {
        $description = match($action) {
            'budget_created' => "Budget created: {$budget->category} - $" . number_format($budget->limit, 2),
            'budget_updated' => "Budget updated: {$budget->category} - $" . number_format($budget->limit, 2),
            'budget_deleted' => "Budget deleted: {$budget->category}",
            default => "Budget operation: $action"
        };

        return self::log(
            $user,
            $action,
            $budget,
            $oldValues,
            $action !== 'budget_deleted' ? $budget->toArray() : null,
            $description,
            $request
        );
    }

    /**
     * Log a goal operation
     */
    public static function logGoalOperation(User $user, string $action, Model $goal, array $oldValues = null, Request $request = null): AuditLog
    {
        $description = match($action) {
            'goal_created' => "Goal created: {$goal->title} - $" . number_format($goal->target_amount, 2),
            'goal_updated' => "Goal updated: {$goal->title} - $" . number_format($goal->target_amount, 2),
            'goal_deleted' => "Goal deleted: {$goal->title}",
            'goal_deposit' => "Goal deposit: {$goal->title} - $" . number_format($goal->current_amount, 2),
            default => "Goal operation: $action"
        };

        return self::log(
            $user,
            $action,
            $goal,
            $oldValues,
            $action !== 'goal_deleted' ? $goal->toArray() : null,
            $description,
            $request
        );
    }

    /**
     * Log a debt operation
     */
    public static function logDebtOperation(User $user, string $action, Model $debt, array $oldValues = null, Request $request = null): AuditLog
    {
        $description = match($action) {
            'debt_created' => "Debt created: {$debt->name} - $" . number_format($debt->balance, 2),
            'debt_updated' => "Debt updated: {$debt->name} - $" . number_format($debt->balance, 2),
            'debt_deleted' => "Debt deleted: {$debt->name}",
            'debt_payment' => "Debt payment: {$debt->name} - $" . number_format($debt->balance, 2),
            'debt_marked_paid' => "Debt marked as paid: {$debt->name}",
            'debt_marked_active' => "Debt marked as active: {$debt->name}",
            default => "Debt operation: $action"
        };

        return self::log(
            $user,
            $action,
            $debt,
            $oldValues,
            $action !== 'debt_deleted' ? $debt->toArray() : null,
            $description,
            $request
        );
    }

    /**
     * Get audit logs for a user
     */
    public static function getUserLogs(User $user, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::where('user_id', $user->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs for a specific model
     */
    public static function getModelLogs(string $modelType, int $modelId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return AuditLog::where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
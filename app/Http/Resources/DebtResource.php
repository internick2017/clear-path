<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebtResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'amount' => number_format($this->amount, 2),
            'interest_rate' => number_format($this->interest_rate, 2) . '%',
            'minimum_payment' => number_format($this->minimum_payment, 2),
            'due_date' => $this->due_date?->toIso8601String(),
            'strategy' => $this->strategy,
            'status' => $this->status,
            'is_paid' => $this->status === 'paid',
            'is_active' => $this->status === 'active',
            'paid_at' => $this->paid_at?->toIso8601String(),
            'monthly_interest' => number_format(
                $this->amount * ($this->interest_rate / 100 / 12), 
                2
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
} 
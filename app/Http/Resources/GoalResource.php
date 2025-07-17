<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $progressPercentage = $this->target_amount > 0 
            ? round(($this->current_amount / $this->target_amount) * 100, 2) 
            : 0;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'target_amount' => number_format($this->target_amount, 2),
            'current_amount' => number_format($this->current_amount, 2),
            'remaining_amount' => number_format($this->target_amount - $this->current_amount, 2),
            'deadline' => $this->deadline?->toIso8601String(),
            'progress_percentage' => $progressPercentage,
            'is_completed' => $this->current_amount >= $this->target_amount,
            'days_remaining' => now()->diffInDays($this->deadline, false),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
} 
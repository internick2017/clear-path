<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GoalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:100'],
            'target_amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        ];

        // For new goals, deadline must be in the future
        if ($this->isMethod('POST')) {
            $rules['deadline'] = ['required', 'date', 'after:today'];
        } else {
            // For updates, deadline can be today or in the future
            $rules['deadline'] = ['required', 'date', 'after_or_equal:today'];
        }

        return $rules;
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The goal title is required.',
            'title.max' => 'The goal title cannot exceed 100 characters.',
            'target_amount.required' => 'The target amount is required.',
            'target_amount.numeric' => 'The target amount must be a valid number.',
            'target_amount.min' => 'The target amount must be at least $0.01.',
            'target_amount.max' => 'The target amount cannot exceed $999,999.99.',
            'deadline.required' => 'The deadline is required.',
            'deadline.date' => 'The deadline must be a valid date.',
            'deadline.after' => 'The deadline must be in the future.',
            'deadline.after_or_equal' => 'The deadline must be today or in the future.',
        ];
    }

    /**
     * Get custom attributes for validation error messages.
     */
    public function attributes(): array
    {
        return [
            'title' => 'goal title',
            'target_amount' => 'target amount',
            'deadline' => 'deadline',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure target_amount is properly formatted as decimal
        if ($this->has('target_amount') && is_numeric($this->target_amount)) {
            $this->merge([
                'target_amount' => (float) $this->target_amount,
            ]);
        }

        // Ensure deadline is in Y-m-d format (only if it's a valid date)
        if ($this->has('deadline') && $this->deadline) {
            try {
                $this->merge([
                    'deadline' => \Carbon\Carbon::parse($this->deadline)->format('Y-m-d'),
                ]);
            } catch (\Exception $e) {
                // If date parsing fails, leave it as is for validation to handle
            }
        }
    }
} 
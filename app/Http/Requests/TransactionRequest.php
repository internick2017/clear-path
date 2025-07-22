<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionRequest extends FormRequest
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
        return [
            'type' => ['required', 'string', Rule::in(['income', 'expense'])],
            'category' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'The transaction type is required.',
            'type.in' => 'The transaction type must be either income or expense.',
            'category.required' => 'The category is required.',
            'category.max' => 'The category cannot exceed 255 characters.',
            'amount.required' => 'The amount is required.',
            'amount.numeric' => 'The amount must be a valid number.',
            'amount.min' => 'The amount must be at least $0.01.',
            'amount.max' => 'The amount cannot exceed $999,999.99.',
            'date.required' => 'The date is required.',
            'date.date' => 'The date must be a valid date.',
            'date.before_or_equal' => 'The date cannot be in the future.',
            'note.max' => 'The note cannot exceed 1000 characters.',
        ];
    }

    /**
     * Get custom attributes for validation error messages.
     */
    public function attributes(): array
    {
        return [
            'type' => 'transaction type',
            'category' => 'category',
            'amount' => 'amount',
            'date' => 'date',
            'note' => 'note',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure amount is properly formatted as decimal
        if ($this->has('amount') && is_numeric($this->amount)) {
            $this->merge([
                'amount' => (float) $this->amount,
            ]);
        }

        // Ensure date is in Y-m-d format (only if it's a valid date)
        if ($this->has('date') && $this->date) {
            try {
                $this->merge([
                    'date' => \Carbon\Carbon::parse($this->date)->format('Y-m-d'),
                ]);
            } catch (\Exception $e) {
                // If date parsing fails, leave it as is for validation to handle
            }
        }
    }
} 
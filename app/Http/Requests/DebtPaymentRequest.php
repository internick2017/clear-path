<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DebtPaymentRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'The payment amount is required.',
            'amount.numeric' => 'The payment amount must be a valid number.',
            'amount.min' => 'The payment amount must be at least $0.01.',
            'amount.max' => 'The payment amount cannot exceed $999,999.99.',
            'payment_date.required' => 'The payment date is required.',
            'payment_date.date' => 'The payment date must be a valid date.',
            'payment_date.before_or_equal' => 'The payment date cannot be in the future.',
            'payment_method.max' => 'The payment method cannot exceed 255 characters.',
            'note.max' => 'The note cannot exceed 1000 characters.',
        ];
    }

    /**
     * Get custom attributes for validation error messages.
     */
    public function attributes(): array
    {
        return [
            'amount' => 'payment amount',
            'payment_date' => 'payment date',
            'payment_method' => 'payment method',
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

        // Ensure payment_date is in Y-m-d format (only if it's a valid date)
        if ($this->has('payment_date') && $this->payment_date) {
            try {
                $this->merge([
                    'payment_date' => \Carbon\Carbon::parse($this->payment_date)->format('Y-m-d'),
                ]);
            } catch (\Exception $e) {
                // If date parsing fails, leave it as is for validation to handle
            }
        }
    }
} 
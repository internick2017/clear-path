<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DebtRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'original_amount' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'total_amount' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'amount_paid' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'currency' => ['required', 'string', 'in:' . implode(',', array_keys(config('currencies.supported')))],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_payment' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The debt name is required.',
            'name.max' => 'The debt name may not be greater than 255 characters.',
            'amount.required' => 'The debt amount is required.',
            'amount.numeric' => 'The debt amount must be a number.',
            'amount.min' => 'The debt amount must be at least $0.01.',
            'amount.max' => 'The debt amount may not be greater than $999,999.99.',
            'interest_rate.required' => 'The interest rate is required.',
            'interest_rate.numeric' => 'The interest rate must be a number.',
            'interest_rate.min' => 'The interest rate must be at least 0%.',
            'interest_rate.max' => 'The interest rate may not be greater than 100%.',
            'minimum_payment.required' => 'The minimum payment is required.',
            'minimum_payment.numeric' => 'The minimum payment must be a number.',
            'minimum_payment.min' => 'The minimum payment must be at least $0.01.',
            'minimum_payment.max' => 'The minimum payment may not be greater than $999,999.99.',
            'due_date.required' => 'The due date is required.',
            'due_date.date' => 'The due date must be a valid date.',
            'due_date.after_or_equal' => 'The due date must be today or a future date.',
            'note.max' => 'The note may not be greater than 1000 characters.',
            'currency.required' => 'The currency is required.',
            'currency.in' => 'The selected currency is not supported.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'debt name',
            'amount' => 'debt amount',
            'interest_rate' => 'interest rate',
            'minimum_payment' => 'minimum payment',
            'due_date' => 'due date',
            'note' => 'note',
            'currency' => 'currency',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('amount') && is_numeric($this->amount)) {
            $this->merge(['amount' => (float) $this->amount]);
        }
        
        if ($this->has('interest_rate') && is_numeric($this->interest_rate)) {
            $this->merge(['interest_rate' => (float) $this->interest_rate]);
        }
        
        if ($this->has('minimum_payment') && is_numeric($this->minimum_payment)) {
            $this->merge(['minimum_payment' => (float) $this->minimum_payment]);
        }
        
        if ($this->has('due_date')) {
            try {
                $this->merge(['due_date' => \Carbon\Carbon::parse($this->due_date)->format('Y-m-d')]);
            } catch (\Exception $e) {
                // Keep original value if parsing fails
            }
        }
    }
} 
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
            'currency' => ['required', 'string', 'in:' . implode(',', array_keys(config('currencies.supported')))],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
            'expense_type' => ['nullable', 'string', Rule::in(['fixed', 'variable'])],
            'debt_id' => ['nullable', 'integer', 'exists:debts,id'],
            'is_debt_payment' => ['nullable', 'boolean'],
            'is_debt_purchase' => ['nullable', 'boolean'],
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
            'currency.required' => 'The currency is required.',
            'currency.in' => 'The selected currency is not supported.',
            'expense_type.in' => 'El tipo de gasto debe ser fijo o variable.',
            'debt_id.exists' => 'La deuda seleccionada no existe.',
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
            'currency' => 'currency',
            'date' => 'date',
            'note' => 'note',
            'expense_type' => 'tipo de gasto',
            'debt_id' => 'deuda',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->type === 'expense' && empty($this->expense_type)) {
                $validator->errors()->add('expense_type', 'El tipo de gasto es requerido para gastos.');
            }
        });
    }
} 
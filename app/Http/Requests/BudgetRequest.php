<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class BudgetRequest extends FormRequest
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
        $budgetId = $this->route('budget')?->id;
        
        return [
            'category' => [
                'required',
                'string',
                'max:255',
                Rule::unique('budgets')->where(function ($query) {
                    $month = Carbon::parse($this->month . '-01')->format('Y-m-d');
                    return $query->where('user_id', auth()->id())
                                ->where('category', $this->category)
                                ->whereMonth('month', Carbon::parse($month)->month)
                                ->whereYear('month', Carbon::parse($month)->year);
                })->ignore($budgetId),
            ],
            'limit' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'month' => ['required', 'date_format:Y-m', 'after_or_equal:2020-01'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'category.required' => 'The category is required.',
            'category.max' => 'The category cannot exceed 255 characters.',
            'category.unique' => 'A budget already exists for this category in the selected month.',
            'limit.required' => 'The budget limit is required.',
            'limit.numeric' => 'The budget limit must be a valid number.',
            'limit.min' => 'The budget limit must be at least $0.01.',
            'limit.max' => 'The budget limit cannot exceed $999,999.99.',
            'month.required' => 'The month is required.',
            'month.date_format' => 'The month must be in YYYY-MM format.',
            'month.after_or_equal' => 'The month must be January 2020 or later.',
        ];
    }

    /**
     * Get custom attributes for validation error messages.
     */
    public function attributes(): array
    {
        return [
            'category' => 'category',
            'limit' => 'budget limit',
            'month' => 'month',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure limit is properly formatted as decimal
        if ($this->has('limit') && is_numeric($this->limit)) {
            $this->merge([
                'limit' => (float) $this->limit,
            ]);
        }

        // Ensure month is in Y-m format (only if it's a valid date)
        if ($this->has('month') && $this->month) {
            try {
                $this->merge([
                    'month' => Carbon::parse($this->month)->format('Y-m'),
                ]);
            } catch (\Exception $e) {
                // If date parsing fails, leave it as is for validation to handle
            }
        }
    }
} 
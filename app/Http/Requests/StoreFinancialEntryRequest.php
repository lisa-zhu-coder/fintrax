<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFinancialEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('financial.registros.create');
    }

    public function rules(): array
    {
        $rules = [
            'date' => 'required|date',
            'store_id' => 'required|exists:stores,id',
            'type' => 'required|in:daily_close,expense,income,expense_refund',
            'concept' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ];

        if ($this->type === 'daily_close') {
            $rules = array_merge($rules, [
                'cash_initial' => 'required|numeric|min:0',
                'tpv' => 'required|numeric|min:0',
                'cash_count' => 'nullable|array',
                'cash_count.*' => 'nullable|numeric|min:0|integer',
                'shopify_cash' => 'nullable|numeric|min:0',
                'shopify_tpv' => 'nullable|numeric|min:0',
                'vouchers_in' => 'nullable|numeric|min:0',
                'vouchers_out' => 'nullable|numeric|min:0',
                'expense_items' => 'nullable|array',
                'expense_items.*.concept' => 'required_with:expense_items|string|max:255',
                'expense_items.*.amount' => 'required_with:expense_items|numeric|min:0',
            ]);
        }

        if ($this->type === 'expense') {
            $rules = array_merge($rules, [
                'expense_category' => 'nullable|string|max:255',
                'expense_payment_method' => 'nullable|in:cash,bank,transfer,card',
                'expense_concept' => 'nullable|string|max:255',
                'expense_paid_cash' => 'nullable|boolean',
            ]);
        }

        if ($this->type === 'income') {
            $rules = array_merge($rules, [
                'income_category' => 'nullable|string|max:255',
                'income_concept' => 'nullable|string|max:255',
            ]);
        }

        if ($this->type === 'expense_refund') {
            $rules = array_merge($rules, [
                'refund_concept' => 'nullable|string|max:255',
                'refund_original_id' => 'nullable|string|max:255',
                'refund_type' => 'nullable|in:existing,new',
            ]);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'date.required' => 'La fecha es obligatoria.',
            'date.date' => 'La fecha debe ser una fecha válida.',
            'store_id.required' => 'Debes seleccionar una tienda.',
            'store_id.exists' => 'La tienda seleccionada no existe.',
            'type.required' => 'Debes seleccionar un tipo de registro.',
            'type.in' => 'El tipo de registro no es válido.',
            'amount.required' => 'El importe es obligatorio.',
            'amount.numeric' => 'El importe debe ser un número.',
            'amount.min' => 'El importe debe ser mayor o igual a 0.',
            'cash_initial.required' => 'El efectivo inicial es obligatorio para cierres diarios.',
            'tpv.required' => 'La tarjeta (TPV) es obligatoria para cierres diarios.',
        ];
    }
}

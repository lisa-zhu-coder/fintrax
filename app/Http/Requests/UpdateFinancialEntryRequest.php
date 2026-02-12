<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFinancialEntryRequest extends StoreFinancialEntryRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('financial.registros.edit');
    }
}

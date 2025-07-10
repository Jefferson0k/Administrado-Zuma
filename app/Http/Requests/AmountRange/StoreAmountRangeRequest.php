<?php

namespace App\Http\Requests\AmountRange;

use Illuminate\Foundation\Http\FormRequest;

class StoreAmountRangeRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(){
        return [
            'corporate_entity_id' => 'required|exists:corporate_entities,id',
            'desde' => 'required|numeric|min:0',
            'hasta' => 'nullable|numeric|gt:desde',
            'moneda' => 'required|in:PEN,USD',
        ];
    }
}

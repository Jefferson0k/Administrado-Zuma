<?php

namespace App\Http\Requests\Investment;

use Illuminate\Foundation\Http\FormRequest;

class InvestmentStoreRequest extends FormRequest{
    public function authorize(){
        return true;
    }
    public function rules(){
        return [
            'property_id' => 'required|exists:properties,id',
            'monto_invertido' => 'required|numeric|min:0.01',
        ];
    }
}

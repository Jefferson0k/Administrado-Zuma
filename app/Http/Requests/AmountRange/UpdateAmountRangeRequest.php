<?php

namespace App\Http\Requests\AmountRange;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAmountRangeRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(){
        return [
            
        ];
    }

}

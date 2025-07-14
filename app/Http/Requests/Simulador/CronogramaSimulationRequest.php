<?php

namespace App\Http\Requests\Simulador;

use Illuminate\Foundation\Http\FormRequest;

class CronogramaSimulationRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(): array{
        return [
            'property_id' => 'required|integer|exists:properties,id',
            'deadline_id' => 'required|integer|exists:deadlines,id',
        ];
    }
}

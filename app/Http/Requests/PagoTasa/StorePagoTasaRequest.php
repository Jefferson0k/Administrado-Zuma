<?php

namespace App\Http\Requests\PagoTasa;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\FixedTermSchedule;

class StorePagoTasaRequest extends FormRequest{
    public function authorize(): bool{
        return true;
    }
    public function rules(): array{
        return [
            'mes' => 'required|integer|min:1|max:12',
            'monto' => 'required|numeric|min:0',
            'moneda' => 'required|string|size:3',
            'id_fixed_term_schedule' => 'required|exists:fixed_term_schedules,id',
            'id_inversionista' => 'required|exists:investors,id',
        ];
    }
    public function withValidator($validator) {
        $validator->after(function ($validator) {
            $schedule = FixedTermSchedule::find($this->id_fixed_term_schedule);

            if ($schedule && $schedule->status !== 'pendiente') {
                $validator->errors()->add(
                    'id_fixed_term_schedule',
                    'Esta cuota ya fue pagada o no está en estado pendiente.'
                );
            }
        });
    }
}

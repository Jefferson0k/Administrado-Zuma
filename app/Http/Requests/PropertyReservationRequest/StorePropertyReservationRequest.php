<?php

namespace App\Http\Requests\PropertyReservations\PropertyReservationRequest;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // o usa políticas si deseas
    }

    public function rules(): array
    {
        return [
            'property_id' => 'required|ulid|exists:properties,id',
            'config_id' => 'nullable|exists:property_configuracions,id',
            'amount' => 'required|numeric|min:0.01',
        ];
    }
}

<?php

namespace App\Http\Requests\BLog;

use Illuminate\Foundation\Http\FormRequest;

class BlogStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'updated_user_id' => 'nullable|integer',
            'titulo' => 'nullable|string|max:255',
            'contenido' => 'nullable|string',
            'resumen' => 'nullable|string|max:1000',
            'imagen' => 'nullable|image|max:2048', // ajusta si no es imagen
            //'imagen' => 'nullable|string|max:255',
            //'product_id' => 'required|exists:products,id',
            'category_id' => 'nullable',
            'state_id' => 'required|exists:states,id',
            'fecha_programada' => 'nullable|date_format:Y-m-d H:i:s',
            'fecha_publicacion'=> 'nullable|date_format:Y-m-d H:i:s',
        ];
    }
}

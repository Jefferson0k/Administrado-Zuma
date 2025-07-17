<?php

namespace App\Http\Resources\Tasas\CorporateEntity;

use Illuminate\Http\Resources\Json\JsonResource;

class CorporateEntityResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'           => $this->id,
            'nombre'       => $this->nombre,
            'ruc'          => $this->ruc,
            'direccion'    => $this->direccion,
            'telefono'     => $this->telefono,
            'email'        => $this->email,
            'tipo_entidad' => $this->tipo_entidad,
            'estado'       => $this->estado,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,

            // ✅ URL completa del PDF
            'pdf_url' => $this->pdf
                ? url('storage/pdfs/' . $this->pdf)
                : null,
        ];
    }
}

<?php

namespace App\Http\Resources\Subastas\InvestorSimple;

use Illuminate\Http\Resources\Json\JsonResource;

class InvestorSimpleResource extends JsonResource
{
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'nombre' => $this->nombre ?? $this->name ?? null,
            'email' => $this->email ?? null,
            'telefono' => $this->telefono ?? null,
            'documento' => $this->documento ?? null,
        ];

        return $this->cleanNulls($data);
    }

    private function cleanNulls($array)
    {
        return collect($array)
            ->reject(fn($v) => is_null($v))
            ->toArray();
    }
}

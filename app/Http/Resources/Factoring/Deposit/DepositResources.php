<?php

namespace App\Http\Resources\Factoring\Deposit;

use Illuminate\Http\Resources\Json\JsonResource;

class DepositResources extends JsonResource
{
    public function toArray($request)
    {


      
        return [
            'id'              => $this->id,
            'nro_operation'   => $this->nro_operation,
            'currency'        => $this->currency,
            'amount'          => $this->amount, // ya formateado por el accessor
            'description'     => $this->description,
            'resource_path'   => $this->resource_path,
            'conclusion'      => $this->conclusion,

            'movement'        => $this->whenLoaded('movement'),
            'investor'        => $this->whenLoaded('investor'),
            'bank_account'    => $this->whenLoaded('bankAccount'),
            'created_by'      => $this->whenLoaded('createdBy'),
            'updated_by'      => $this->whenLoaded('updatedBy'),

            'created_at'      => $this->created_at?->toDateTimeString(),
            'updated_at'      => $this->updated_at?->toDateTimeString(),

        ];
    }


    /**
     * DB → UI
     * valid|invalid|pending|rejected|confirmed → approved|observed|pending|rejected
     */
    private static function dbToUi(?string $db): ?string
    {
        if ($db === null) return null;

        return match (strtolower($db)) {
            'valid', 'confirmed' => 'approved',
            'invalid'            => 'observed',
            'pending'            => 'pending',
            'rejected'           => 'rejected',
            default              => $db, // por si aparece algo inesperado
        };
    }

    /**
     * UI → DB (útil para controladores al guardar)
     * approved|observed|pending|rejected → valid|invalid|pending|rejected
     *
     * Ejemplo de uso en un controlador al guardar:
     * $movement->status = DepositResources::uiToDb($request->input('status0'));
     * $movement->confirm_status = DepositResources::uiToDb($request->input('status'));
     */
    public static function uiToDb(string $ui): string
    {
        return match (strtolower($ui)) {
            'approved' => 'valid',
            'observed' => 'invalid',
            'pending'  => 'pending',
            'rejected' => 'rejected',
            default    => throw new \InvalidArgumentException("Unknown UI status: {$ui}"),
        };
    }
}

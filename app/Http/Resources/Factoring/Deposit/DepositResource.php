<?php

namespace App\Http\Resources\Factoring\Deposit;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepositResource extends JsonResource
{


    public function toArray(Request $request): array
    {

        $status0Db = optional($this->movement)->status->value;           // 1ª validación (DB)
        $statusDb  = optional($this->movement)->confirm_status->value;   // 2ª validación (DB)
        return [
            "id" => $this->id,
            'bank_account_id' => $this->bank_account_id,
            'id_movimiento' => $this->movement?->id,
            'nomBanco' => $this->bankAccount?->bank?->name ?? 'Sin banco',
            'nro_operation' => $this->nro_operation,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'investor' => $this->investor->name . ' ' . $this->investor->first_last_name . ' ' . $this->investor->second_last_name,

            'foto' => $this->resource_path,


            'estado_bank_account' => $this->bankAccount?->status ?? 'Sin estado',
            'cc' => $this->bankAccount?->cc ?? 'sin cc',
            'cci' => $this->bankAccount?->cci ?? 'sin cci',
            'type' => $this->bankAccount?->type ?? 'sin tipo',
            'status_conclusion' => $this->status_conclusion,

            // Fechas formateadas
            'creacion' => $this->created_at
                ? Carbon::parse($this->created_at)->format('d-m-Y H:i:s A')
                : null,
            'fecha_aprobacion_1' => $this->movement?->aprobacion_1
                ? Carbon::parse($this->movement->aprobacion_1)->format('d-m-Y H:i:s A')
                : null,
            'fecha_aprobacion_2' => $this->movement?->aprobacion_2
                ? Carbon::parse($this->movement->aprobacion_2)->format('d-m-Y H:i:s A')
                : null,

            // Fechas ISO originales
            'creacion_iso' => $this->created_at,
            'fecha_aprobacion_1_iso' => $this->movement?->aprobacion_1,
            'fecha_aprobacion_2_iso' => $this->movement?->aprobacion_2,

            // Aprobado por concatenando name + apellidos
            'aprobado_por_1_dni' => $this->movement?->aprobadoPor1?->dni ?? null,
            'aprobado_por_2_dni' => $this->movement?->aprobadoPor2?->dni ?? null,

            'aprobado_por_1_nombre' => $this->movement?->aprobadoPor1
                ? $this->movement->aprobadoPor1->name . ' ' . $this->movement->aprobadoPor1->apellidos
                : null,
            'aprobado_por_2_nombre' => $this->movement?->aprobadoPor2
                ? $this->movement->aprobadoPor2->name . ' ' . $this->movement->aprobadoPor2->apellidos
                : null,



            'status0' => self::dbToUi($status0Db),
            'status'  => self::dbToUi($statusDb),

            'comment0'        => $this->comment0,     // comentario de la 1ª validación
            'comment'         => $this->comment,      // comentario de la 2ª validación (si lo usas)

            'attachments' => $this->attachments->map(fn($a) => [
                'id'   => $a->id,
                'name' => $a->name,
                'mime' => $a->mime,
                'size' => $a->size,
                'url'  => $a->url,      // usable in UI
                'is_image' => $a->is_image,
                'ext'  => $a->ext,
            ]),

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

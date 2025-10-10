<?php

namespace App\Http\Resources\Subastas\SolicitudApproval;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SolicitudApprovalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'solicitud_id' => $this->solicitud_id,
            'status' => $this->status,
            'comment' => $this->comment,
            'approved_by' => $this->usuario?->name.' '.$this->usuario?->apellidos,
            'approved_at' => $this->approved_at
                ? Carbon::parse($this->approved_at)->format('Y-m-d H:i:s')
                : null,
        ];
    }
}

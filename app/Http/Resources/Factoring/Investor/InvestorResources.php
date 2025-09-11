<?php

namespace App\Http\Resources\Factoring\Investor;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestorResources extends JsonResource
{
    public function toArray($request)
    {
        // Traducimos el status al español
        $statusTraducido = match($this->status) {
            'not validated' => 'No validado',
            'validated'     => 'Validado',
            default         => $this->status,
        };

        return [
            'id'        => $this->id,
            'name'      => $this->name . ' ' . $this->first_last_name . ' ' . $this->second_last_name,
            'document'  => $this->document,
            'alias'     => $this->email,
            'telephone' => $this->telephone,
            'status'    => $statusTraducido,
            'email'     => $this->email,
            'document_front' => $this->document_front ?? 'Sin foto',
            'document_back' => $this->document_back ?? 'Sin foto',
            'perfil' => $this->profile_photo_path ?? 'Sin perfil',
            'department' => $this->department,
            'province' => $this->province,
            'district' => $this->district,
            'personaexpuesta' => $this->is_pep,
            'relacionPolitica' => $this->has_relationship_pep,
            'emailverificacion'  => Carbon::parse($this->email_verified_at)->format('d-m-Y H:i:s A'),
            'creacion'  => Carbon::parse($this->created_at)->format('d-m-Y H:i:s A'),
        ];
    }
}

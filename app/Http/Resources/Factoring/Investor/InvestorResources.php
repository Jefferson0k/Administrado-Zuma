<?php

namespace App\Http\Resources\Factoring\Investor;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestorResources extends JsonResource
{
    public function toArray($request)
    {
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
            'document_back'  => $this->document_back ?? 'Sin foto',
            'perfil'    => $this->profile_photo_path ?? 'Sin perfil',
            'department'=> $this->department,
            'province'  => $this->province,
            'district'  => $this->district,
            'address'  => $this->address,
            'investor_photo_path' => $this->investor_photo,
            'personaexpuesta'     => $this->is_pep,
            'relacionPolitica'    => $this->has_relationship_pep,

            'file_path'          => $this->file_path,
            'approval1_status'   => $this->approval1_status,
            'approval1_by'       => $this->aprovacionuseruno?->dni,
            'approval1_comment'  => $this->approval1_comment,
            'approval1_at'       => $this->approval1_at
                                        ? Carbon::parse($this->approval1_at)->format('d-m-Y H:i:s A')
                                        : null,
            'approval2_status'   => $this->approval2_status,
            'approval2_by'       => $this->aprovacionuserdos?->dni,
            'approval2_comment'  => $this->approval2_comment,
            'approval2_at'       => $this->approval2_at
                                        ? Carbon::parse($this->approval2_at)->format('d-m-Y H:i:s A')
                                        : null,
            'emailverificacion'  => $this->email_verified_at
                                        ? Carbon::parse($this->email_verified_at)->format('d-m-Y H:i:s A')
                                        : null,
            'creacion'           => $this->created_at
                                        ? Carbon::parse($this->created_at)->format('d-m-Y H:i:s A')
                                        : null,
        ];
    }
}

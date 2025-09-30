<?php

namespace App\Http\Resources\Factoring\SubSector;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
class SubSectorResource extends JsonResource{
    public function toArray($request){
        return [
            'id' => $this->id,
            'name' => $this->name,
            'vinculado' => $this->vinculado,
            'creacion' => Carbon::parse($this->created_at)->format('d-m-Y H:i:s A'),
            'update' => Carbon::parse($this->updated_at)->format('d-m-Y H:i:s A'),
        ];
    }
}

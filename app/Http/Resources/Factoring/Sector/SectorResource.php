<?php

namespace App\Http\Resources\Factoring\Sector;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
class SectorResource extends JsonResource{
    public function toArray($request){
        return [
            'id' => $this->id,
            'name' => $this->name,
            'creacion' => Carbon::parse($this->created_at)->format('d-m-Y H:i:s A'),
            'update' => Carbon::parse($this->updated_at)->format('d-m-Y H:i:s A'),
        ];
    }
}

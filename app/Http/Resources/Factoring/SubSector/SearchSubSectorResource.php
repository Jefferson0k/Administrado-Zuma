<?php

namespace App\Http\Resources\Factoring\SubSector;

use Illuminate\Http\Resources\Json\JsonResource;
class SearchSubSectorResource extends JsonResource{
    public function toArray($request){
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}

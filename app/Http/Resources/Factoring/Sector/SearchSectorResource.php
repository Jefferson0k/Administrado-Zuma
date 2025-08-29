<?php

namespace App\Http\Resources\Factoring\Sector;

use Illuminate\Http\Resources\Json\JsonResource;
class SearchSectorResource extends JsonResource{
    public function toArray($request){
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}

<?php

namespace App\Http\Resources\Factoring\Company;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
class CompanyResource extends JsonResource{
    public function toArray($request){
        return [
            'id' => $this->id,
            'name' => $this->name,
            'risk' => $this->risk,
            'business_name' => $this->business_name,
            'incorporation_year' => $this->incorporation_year,
            'sales_volume' => $this->sales_volume,
            'document' => $this->document,
            'link_web_page' => $this->link_web_page,
            'description' => $this->description,
            'moneda' => $this->moneda,
            'sector_id' => $this->sector_id,
            'subsector_id' => $this->subsector_id,
            'sectornom' =>$this->sector->name,
            'subsectornom' =>$this->subsector->name,
            'creacion' => Carbon::parse($this->created_at)->format('d-m-Y H:i:s A'),
        ];
    }
}

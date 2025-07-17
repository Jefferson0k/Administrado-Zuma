<?php
// App\Http\Resources\PropertyInvestorResource.php
namespace App\Http\Resources\Subastas\Property;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyInvestorResource extends JsonResource{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'status' => $this->status,
            'investor' => $this->investor,
            'property' => $this->property,
            'payment_schedules' => $this->paymentSchedules,
        ];
    }
}

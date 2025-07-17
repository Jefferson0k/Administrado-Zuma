<?php

namespace App\Http\Resources\Tasas\FixedTermSchedule;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FixedTermScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'month'                 => $this->month,
            'payment_date'          => Carbon::parse($this->payment_date)->format('d-m-y'),
            'days'                  => $this->days,
            'base_amount'           => $this->base_amount,
            'interest_amount'       => $this->interest_amount,
            'second_category_tax'   => $this->second_category_tax,
            'interest_to_deposit'   => $this->interest_to_deposit,
            'capital_return'        => $this->capital_return,
            'capital_balance'       => $this->capital_balance,
            'total_to_deposit'      => $this->total_to_deposit,
            'status'                => $this->status,
        ];
    }
}

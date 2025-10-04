<?php

namespace App\Http\Resources\Invoices;

use Illuminate\Http\Resources\Json\JsonResource;
class InvoiceResource extends JsonResource{
    public function toArray($request){
        return [
            'id' => $this->id,
            'invoice_code' => $this->invoice_code,
            'amount' => $this->amount,
            'financed_amount_by_garantia' => $this->financed_amount_by_garantia,
            'financed_amount' => $this->financed_amount,
            'paid_amount' => $this->paid_amount,
            'rate' => $this->rate,
            'due_date' => $this->due_date,
            'status' => $this->status,
            'company_id' => 'Sin Definir',
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,


            'approval1_status' => $this->approval1_status,
            'approval2_status' => $this->approval2_status,
            'approval1_by'     => $this->approval1_by,
            'approval1_at'     => $this->approval1_at,
            'approval2_by'     => $this->approval2_by,
            'approval2_at'     => $this->approval2_at,

        ];
    }
}

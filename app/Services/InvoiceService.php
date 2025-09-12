<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Str;

class InvoiceService{
    public function create(array $data): Invoice{
        $company = Company::findOrFail($data['company_id']);
        $status = $data['status'] ?? 'inactive';
        $dueDate = Carbon::parse($data['estimated_pay_date'])->subDays(25);
        $prefix = $data['currency'] === 'PEN' ? '01' : '02';
        $companyCode = strtoupper(substr(preg_replace('/\s+/', '', $company->name), 0, 4));
        $suffix = strtoupper(Str::random(3));
        $codigo = $prefix . $companyCode . $suffix;

        return Invoice::create([
            'invoice_code'             => Str::ulid(),
            'codigo'                   => $codigo,
            'currency'                 => $data['currency'],
            'amount'                   => $data['amount'],
            'financed_amount_by_garantia' => $data['financed_amount_by_garantia'],
            'financed_amount'          => $data['amount'] - $data['financed_amount_by_garantia'],
            'rate'                     => $data['rate'],
            'due_date'                 => $dueDate,
            'estimated_pay_date'       => $data['estimated_pay_date'],
            'status'                   => $status,
            'company_id'               => $company->id,
            'loan_number'              => $data['loan_number'] ?? null,
            'RUC_client'               => $data['RUC_client'] ?? null,
            'invoice_number'           => $data['invoice_number'] ?? null,
            'paid_amount'              => $data['paid_amount'] ?? 0,
            'client_business_name'     => $data['client_business_name'] ?? null,
            'client_direccion'         => $data['client_direccion'] ?? null,
            'client_departamento'      => $data['client_departamento'] ?? null,
            'client_provincia'         => $data['client_provincia'] ?? null,
            'client_distrito'          => $data['client_distrito'] ?? null,
            'created_by'               => $data['created_by'],
            'updated_by'               => $data['updated_by'] ?? null,
        ]);
    }
    public function update(array $data, string $id): Invoice{
        $invoice = Invoice::findOrFail($id);
        $company = Company::findOrFail($data['company_id']);
        $status = $data['status'] ?? $invoice->status;
        $dueDate = Carbon::parse($data['estimated_pay_date'])->subDays(25);
        $invoice->update([
            'currency'                 => $data['currency'],
            'amount'                   => $data['amount'],
            'financed_amount_by_garantia' => $data['financed_amount_by_garantia'],
            'financed_amount'          => $data['amount'] - $data['financed_amount_by_garantia'],
            'rate'                     => $data['rate'],
            'due_date'                 => $dueDate,
            'estimated_pay_date'       => $data['estimated_pay_date'],
            'status'                   => $status,
            'company_id'               => $company->id,
            'loan_number'              => $data['loan_number'] ?? null,
            'RUC_client'               => $data['RUC_client'] ?? null,
            'invoice_number'           => $data['invoice_number'] ?? null,
            'paid_amount'              => $data['paid_amount'] ?? 0,
            'client_business_name'     => $data['client_business_name'] ?? null,
            'client_direccion'         => $data['client_direccion'] ?? null,
            'client_departamento'      => $data['client_departamento'] ?? null,
            'client_provincia'         => $data['client_provincia'] ?? null,
            'client_distrito'          => $data['client_distrito'] ?? null,
            'updated_by'               => $data['updated_by'],
        ]);
        return $invoice;
    }
}

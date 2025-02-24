<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'company_id' => ['sometimes', 'exists:companies,id'],
            'client_id' => ['sometimes', 'exists:clients,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'issue_date' => ['sometimes', 'date'],
            'subtotal' => ['sometimes', 'numeric', 'min:0'],
            'tax_amount' => ['sometimes', 'numeric', 'min:0'],
            'total' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'type' => ['sometimes', 'string', 'in:expense,income'],
            'status' => ['sometimes', 'string', 'in:draft,sent,paid,overdue,cancelled'],
            'pdf_path' => ['nullable', 'string'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReversalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction' => ['required', 'string', 'exists:financial_statements,hash'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\MovementType;
use App\Enums\OperationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class FinancialStatementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'operation_type' => ['required', new Enum(OperationType::class)],
            'type' => ['required', new Enum(MovementType::class)],
            'reversed' => ['sometimes', 'boolean'],
            'requester_hash' => ['required', 'string'],
            'receiver_hash' => ['required', 'string'],
            'amount' => ['required', 'integer', 'min:100'],
        ];
    }
}

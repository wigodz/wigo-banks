<?php

namespace App\Http\Requests;

use App\Enums\MovementType;
use App\Enums\OperationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'operation_type' => ['nullable', Rule::enum(OperationType::class)],
            'type' => ['nullable', Rule::enum(MovementType::class)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'receiver' => ['nullable', 'string', 'exists:users,hash'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(
            collect($this->only(['operation_type', 'type', 'date_from', 'date_to', 'receiver']))
                ->map(fn ($value) => $value === '' ? null : $value)
                ->all()
        );
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1', 'max:9999999'],
            'receiver' => ['required', 'string', 'exists:users,hash'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('receiver') === $this->user()?->hash) {
                $validator->errors()->add('receiver', 'Não é possível transferir para a própria conta.');
            }
        });
    }
}

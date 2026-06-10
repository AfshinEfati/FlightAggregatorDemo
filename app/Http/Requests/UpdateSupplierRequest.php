<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'base_url' => 'sometimes|url',
            'poll_interval_minutes' => 'sometimes|integer|min:1',
            'is_active' => 'sometimes|boolean',
            'timeout_seconds' => 'sometimes|integer|min:1',
            'retry_attempts' => 'sometimes|integer|min:0',
        ];
    }
}

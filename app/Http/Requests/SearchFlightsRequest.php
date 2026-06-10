<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchFlightsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'origin' => 'required|string|size:3',
            'destination' => 'required|string|size:3',
            'date' => 'nullable|date_format:Y-m-d',
        ];
    }
}

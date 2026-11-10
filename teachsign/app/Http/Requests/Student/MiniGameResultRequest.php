<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class MiniGameResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'hits' => ['required', 'integer', 'min:0', 'max:500'],
            'misses' => ['required', 'integer', 'min:0', 'max:500'],
            'max_combo' => ['required', 'integer', 'min:0', 'max:500'],
            'seconds' => ['required', 'integer', 'min:5', 'max:300'],
        ];
    }
}

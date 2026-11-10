<?php

namespace App\Http\Requests;

use App\Models\Lesson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'min:3', 'max:100'],
            'category' => ['sometimes', 'required', 'string', Rule::in(Lesson::CATEGORIES)],
            'media_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'min:10', 'max:5000'],
        ];
    }
}

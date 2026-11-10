<?php

namespace App\Http\Requests;

use App\Models\Lesson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:100'],
            'category' => ['required', 'string', Rule::in(Lesson::CATEGORIES)],
            'media_url' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function prepareForValidation(): void
    {
        $questions = $this->input('questions');

        if (! is_array($questions)) {
            return;
        }

        foreach ($questions as $index => $question) {
            if (! is_array($question)) {
                continue;
            }

            if (($question['type'] ?? 'choice') === 'essay') {
                $questions[$index]['correct_answer'] = null;
            } elseif (! array_key_exists('correct_answer', $question)) {
                $questions[$index]['correct_answer'] = '';
            } elseif (is_string($question['correct_answer'])) {
                $questions[$index]['correct_answer'] = trim($question['correct_answer']);
            }

            if (! array_key_exists('options', $question)) {
                $questions[$index]['options'] = ($question['type'] ?? 'choice') === 'essay' ? null : [];
            }

            if (! isset($question['options']) || ! is_array($question['options'])) {
                continue;
            }

            $questions[$index]['options'] = array_values(array_filter(
                array_map(fn ($option) => is_string($option) ? trim($option) : $option, $question['options']),
                fn ($option) => $option !== '' && $option !== null
            ));
        }

        $this->merge(['questions' => $questions]);
    }

    public function rules(): array
    {
        return [
            'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'questions' => ['required', 'array', 'min:1', 'max:50'],
            'questions.*.question' => ['required', 'string', 'min:5', 'max:2000'],
            'questions.*.type' => ['nullable', 'in:choice,essay'],
            'questions.*.options' => [$this->optionsRule()],
            'questions.*.options.*' => ['string', 'max:255', 'distinct'],
            'questions.*.correct_answer' => ['nullable', 'string', 'max:500', $this->answerRule()],
            'questions.*.media_url' => ['nullable', 'string', 'max:500'],
            'is_locked' => ['sometimes', 'boolean'],
            'unlock_at' => ['nullable', 'date', 'after:now'],
            'is_score_visible' => ['sometimes', 'boolean'],
            'duration_seconds' => ['nullable', 'integer', 'min:10', 'max:3600'],
        ];
    }

    private function questionType(string $attribute): string
    {
        $index = explode('.', $attribute)[1] ?? null;

        return (string) $this->input('questions.'.$index.'.type', 'choice');
    }

    private function optionsRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->questionType($attribute) === 'essay') {
                return;
            }

            if (! is_array($value)) {
                $fail('Soal pilihan ganda harus punya pilihan jawaban.');

                return;
            }

            if (count($value) < 2) {
                $fail('Minimal dua pilihan jawaban per soal.');
            }

            if (count($value) > 8) {
                $fail('Maksimal delapan pilihan jawaban per soal.');
            }
        };
    }

    private function answerRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->questionType($attribute) === 'essay') {
                return;
            }

            $index = explode('.', $attribute)[1] ?? null;
            $options = $this->input('questions.'.$index.'.options', []);

            if (! is_array($options) || ! in_array($value, $options, true)) {
                $fail('Kunci jawaban harus salah satu dari pilihan jawaban.');
            }
        };
    }

    public function messages(): array
    {
        return [
            'questions.required' => 'Kuis harus punya minimal satu soal.',
            'questions.*.question.min' => 'Pertanyaan minimal 5 karakter.',
            'questions.*.type.in' => 'Jenis soal harus pilihan ganda atau esai.',
        ];
    }
}

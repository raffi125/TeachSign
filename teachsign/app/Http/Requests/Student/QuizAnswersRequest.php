<?php

namespace App\Http\Requests\Student;

use App\Models\Quiz;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class QuizAnswersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
            'answers.*' => ['required', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $quiz = $this->route('quiz');

                if (! $quiz instanceof Quiz) {
                    return;
                }

                $answers = (array) $this->input('answers', []);
                $questions = $quiz->questions->keyBy('id');

                foreach ($answers as $questionId => $answer) {
                    $question = $questions->get($questionId);

                    if ($question === null) {
                        $validator->errors()->add('answers', 'Jawaban tidak cocok dengan soal kuis ini.');

                        break;
                    }

                    if ($question->type === 'essay') {
                        if (trim((string) $answer) === '') {
                            $validator->errors()->add('answers', 'Masih ada soal esai yang belum dijawab.');

                            break;
                        }

                        continue;
                    }

                    $choices = $question->options ?? [];

                    if (! in_array($answer, $choices, true)) {
                        $validator->errors()->add('answers', 'Jawaban harus salah satu pilihan yang tersedia.');

                        break;
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'answers.required' => 'Jawab semua soal sebelum mengirim.',
            'answers.*.required' => 'Masih ada soal yang belum dijawab.',
        ];
    }
}

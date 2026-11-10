<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        $words = Game::words()
            ->when($search !== '', fn ($query) => $query->where('word', 'like', "%{$search}%"))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest('id')
            ->paginate(15);

        return response()->json($words);
    }

    public function store(Request $request): JsonResponse
    {
        $word = Game::create($this->validated($request) + ['mode' => null, 'score' => null]);

        return response()->json($word, 201);
    }

    public function update(Request $request, Game $word): JsonResponse
    {
        abort_if($word->word === null, 404);

        $word->update($this->validated($request, $word));

        return response()->json($word->fresh());
    }

    public function destroy(Game $word): JsonResponse
    {
        abort_if($word->word === null, 404);

        $word->delete();

        return response()->json(['message' => 'Kata Mini Games berhasil dihapus.']);
    }

    private function validated(Request $request, ?Game $word = null): array
    {
        $request->merge([
            'word' => Str::upper(preg_replace('/\s+/', ' ', trim((string) $request->input('word')))),
        ]);

        $data = $request->validate([
            'word' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z]+(?: [A-Z]+)*$/',
                Rule::unique('games', 'word')->ignore($word?->id),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'word.required' => 'Kata wajib diisi.',
            'word.max' => 'Kata maksimal 50 karakter.',
            'word.regex' => 'Hanya huruf dan spasi yang diperbolehkan.',
            'word.unique' => 'Kata ini sudah ada.',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}

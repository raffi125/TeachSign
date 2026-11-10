<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\MiniGameResultRequest;
use App\Models\Game;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MiniGameController extends Controller
{
    private const MODES = [
        'sign' => ['title' => 'Sign Rush', 'desc' => 'Tebak huruf dari isyarat, 3 detik per ronde'],
        'word' => ['title' => 'Word Quest', 'desc' => 'Eja isyarat jadi kata, ketik jawabannya'],
        'flip' => ['title' => 'Flip Flap', 'desc' => 'Balik kartu, cocokkan huruf & isyarat'],
    ];

    private const LETTERS = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
        'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
    ];

    public function index(Request $request): View
    {
        $modes = self::MODES;
        $scores = Game::scores()
            ->where('user_id', $request->user()->id)
            ->pluck('score', 'mode');

        return view('student.game.index', compact('modes', 'scores'));
    }

    public function show(string $mode): View
    {
        abort_unless(array_key_exists($mode, self::MODES), 404);

        $letters = collect(self::LETTERS)
            ->map(fn (string $letter) => [
                'letter' => $letter,
                'image' => $this->letterImage($letter),
            ])
            ->all();

        $wordItems = Game::words()
            ->active()
            ->orderBy('word')
            ->get()
            ->map(fn (Game $item) => [
                'word' => $item->word,
                'letters' => strlen(str_replace(' ', '', $item->word)),
                'clues' => $this->clues($item->word),
            ])
            ->all();

        return view('student.game.show', compact('mode', 'letters', 'wordItems'));
    }

    private function letterImage(string $letter): string
    {
        return asset("images/abjad/{$letter}.webp").'?v='.filemtime(public_path("images/abjad/{$letter}.webp"));
    }

    private function clues(string $word): array
    {
        $clues = [];

        foreach (preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) as $char) {
            if ($char === ' ') {
                $clues[] = ['space' => true];

                continue;
            }

            $letter = strtoupper($char);

            if (in_array($letter, self::LETTERS, true)) {
                $clues[] = ['image' => $this->letterImage($letter)];
            }
        }

        return $clues;
    }

    public function submit(MiniGameResultRequest $request, string $mode): RedirectResponse
    {
        abort_unless(array_key_exists($mode, self::MODES), 404);

        $hits = (int) $request->validated('hits');
        $misses = (int) $request->validated('misses');
        $maxCombo = (int) $request->validated('max_combo');
        $seconds = (int) $request->validated('seconds');

        $score = match ($mode) {
            'word' => $hits * 15 + $maxCombo * 5 - $misses * 10,
            'flip' => 100 - $misses * 4 - intdiv(max(0, $seconds - 60), 2),
            default => $hits * 4 + $maxCombo * 2 - $misses * 5,
        };

        $score = max(10, min(100, (int) round($score)));

        $record = Game::firstOrNew([
            'user_id' => $request->user()->id,
            'mode' => $mode,
        ]);

        if (! $record->exists || $score > $record->score) {
            $record->score = $score;
            $record->save();
        }

        return redirect()
            ->route('student.games.index')
            ->with('game_result', [
                'score' => $score,
                'mode' => self::MODES[$mode]['title'],
                'hits' => $hits,
                'misses' => $misses,
                'max_combo' => $maxCombo,
                'seconds' => $seconds,
            ]);
    }
}

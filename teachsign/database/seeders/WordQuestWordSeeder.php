<?php

namespace Database\Seeders;

use App\Models\Game;
use Illuminate\Database\Seeder;

class WordQuestWordSeeder extends Seeder
{
    public function run(): void
    {
        $words = [
            'PAGI',
            'BABY',
            'HALO',
            'MAAF',
            'CINTA',
            'MAKAN',
            'MINUM',
            'TIDUR',
            'SENANG',
            'TERIMA KASIH',
        ];

        foreach ($words as $word) {
            Game::updateOrCreate(
                ['word' => $word],
                ['is_active' => true]
            );
        }
    }
}

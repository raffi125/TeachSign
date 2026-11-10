    <section class="card game-card">
        <div class="game-head">
            <div>
                <p class="muted">Tap emoji yang diminta sebelum waktu habis. Tiga kali salah, permainan berakhir.</p>
            </div>
            <div class="game-stats">
                <span class="badge badge-open" id="game-round">Ronde 1</span>
                <span class="badge badge-open" id="game-points">0 poin</span>
                <span class="badge badge-open" id="game-combo">Kombo 0</span>
                <span class="badge badge-open" id="game-lives">3 nyawa</span>
                <button type="button" class="btn btn-ghost btn-sm" id="game-sound">Suara</button>
            </div>
        </div>

        <div class="rush-timer">
            <span class="muted">Sisa waktu</span>
            <span class="rush-timer-value" id="game-timer">3.0s</span>
        </div>
        <div class="rush-bar"><span id="game-bar"></span></div>

        <div class="game-prompt">
            <span class="muted">Isyarat ini huruf apa?</span>
            <img src="" alt="Isyarat abjad" class="word-clue" id="game-target">
        </div>

        <div class="game-board" id="game-board">
            @for ($i = 0; $i < 4; $i++)
                <button type="button" class="game-tile" disabled aria-label="Pilihan huruf">?</button>
            @endfor
        </div>

        <div class="game-actions">
            <button type="button" class="btn btn-primary" id="game-start">Mulai</button>
            <button type="button" class="btn btn-danger btn-sm" id="game-surrender" hidden>Menyerah</button>
        </div>

        <div class="game-result-inline" id="game-result" hidden>
            <p class="game-result-title" id="game-result-title">Waktu habis!</p>
            <p class="muted" id="game-result-stats">—</p>
            <p class="muted" id="game-result-note">Menyimpan skor...</p>
        </div>

        <form method="POST" action="{{ route('student.game.submit', $mode) }}" id="game-form" hidden>
            @csrf
            <input type="hidden" name="hits" id="game-hits-input" value="0">
            <input type="hidden" name="misses" id="game-misses-input" value="0">
            <input type="hidden" name="max_combo" id="game-max-combo-input" value="0">
            <input type="hidden" name="seconds" id="game-seconds-input" value="0">
        </form>
    </section>

@push('scripts')
    <script>
        (() => {
            const LETTERS = @json($letters);
            const ROUND_MS = 3000;
            const MAX_LIVES = 3;
            const TILES = 4;

            const board = document.getElementById('game-board');
            const targetEl = document.getElementById('game-target');
            const roundEl = document.getElementById('game-round');
            const pointsEl = document.getElementById('game-points');
            const comboEl = document.getElementById('game-combo');
            const livesEl = document.getElementById('game-lives');
            const timerEl = document.getElementById('game-timer');
            const barEl = document.getElementById('game-bar');
            const resultEl = document.getElementById('game-result');
            const resultTitle = document.getElementById('game-result-title');
            const resultStats = document.getElementById('game-result-stats');
            const resultNote = document.getElementById('game-result-note');
            const startBtn = document.getElementById('game-start');
            const soundBtn = document.getElementById('game-sound');
            const surrenderBtn = document.getElementById('game-surrender');
            const form = document.getElementById('game-form');
            const hitsInput = document.getElementById('game-hits-input');
            const missesInput = document.getElementById('game-misses-input');
            const comboInput = document.getElementById('game-max-combo-input');
            const secondsInput = document.getElementById('game-seconds-input');

            let audio = null;
            let soundOn = true;
            let running = false;
            let lock = false;
            let lives = MAX_LIVES;
            let hits = 0;
            let misses = 0;
            let combo = 0;
            let maxCombo = 0;
            let round = 0;
            let sessionStart = 0;
            let roundStart = 0;
            let rafId = 0;
            let answer = null;

            const tone = (frequency, duration, type = 'sine') => {
                if (!soundOn) return;

                try {
                    audio = audio || new (window.AudioContext || window.webkitAudioContext)();
                    const oscillator = audio.createOscillator();
                    const gain = audio.createGain();
                    oscillator.type = type;
                    oscillator.frequency.value = frequency;
                    gain.gain.setValueAtTime(0.08, audio.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.0001, audio.currentTime + duration);
                    oscillator.connect(gain).connect(audio.destination);
                    oscillator.start();
                    oscillator.stop(audio.currentTime + duration);
                } catch (error) {
                    /* audio optional */
                }
            };

            const playGood = () => tone(760 + combo * 40, 0.1, 'triangle');
            const playBad = () => tone(150, 0.2, 'sawtooth');
            const playEnd = () => {
                tone(660, 0.1);
                setTimeout(() => tone(520, 0.12), 110);
                setTimeout(() => tone(360, 0.2), 230);
            };

            const pick = (count) => [...LETTERS].sort(() => Math.random() - 0.5).slice(0, count);

            const updateHud = () => {
                roundEl.textContent = `Ronde ${round}`;
                pointsEl.textContent = `${hits * 10} poin`;
                comboEl.textContent = `Kombo ${combo}`;
                livesEl.textContent = `${Math.max(0, lives)} nyawa`;
            };

            const paintTiles = (options) => {
                const tiles = board.querySelectorAll('.game-tile');

                tiles.forEach((tile, index) => {
                    const option = options[index];
                    tile.textContent = option.letter;
                    tile.dataset.letter = option.letter;
                    tile.disabled = false;
                    tile.classList.remove('is-hit', 'is-wrong');
                });
            };

            const stopTiles = () => {
                board.querySelectorAll('.game-tile').forEach((tile) => {
                    tile.disabled = true;
                });
            };

            const tick = (now) => {
                if (!running) return;

                const remaining = ROUND_MS - (now - roundStart);

                timerEl.textContent = `${Math.max(0, remaining / 1000).toFixed(1)}s`;
                barEl.style.width = `${Math.max(0, Math.min(100, (remaining / ROUND_MS) * 100))}%`;

                if (remaining <= 0) {
                    timeUp();

                    return;
                }

                rafId = requestAnimationFrame(tick);
            };

            const nextRound = () => {
                if (!running) return;

                round += 1;
                lock = false;
                answer = pick(1)[0];

                const distractors = LETTERS.filter((item) => item.letter !== answer.letter)
                    .sort(() => Math.random() - 0.5)
                    .slice(0, TILES - 1);
                const options = [answer, ...distractors].sort(() => Math.random() - 0.5);

                targetEl.src = answer.image;
                paintTiles(options);
                updateHud();

                roundStart = performance.now();
                cancelAnimationFrame(rafId);
                rafId = requestAnimationFrame(tick);
            };

            const loseLife = () => {
                lives -= 1;
                combo = 0;
                updateHud();
                playBad();

                if (lives <= 0) {
                    finish('Nyawa habis!');

                    return false;
                }

                return true;
            };

            const timeUp = () => {
                if (!running || lock) return;

                lock = true;
                cancelAnimationFrame(rafId);
                misses += 1;
                stopTiles();

                if (loseLife()) {
                    setTimeout(nextRound, 500);
                }
            };

            const choose = (tile) => {
                if (!running || lock) return;

                if (tile.dataset.letter === answer.letter) {
                    hits += 1;
                    combo += 1;
                    maxCombo = Math.max(maxCombo, combo);
                    updateHud();
                    playGood();

                    tile.classList.add('is-hit');
                    lock = true;

                    setTimeout(nextRound, 250);

                    return;
                }

                misses += 1;
                tile.classList.add('is-wrong');
                lock = true;

                if (loseLife()) {
                    setTimeout(nextRound, 400);
                }
            };

            const finish = (title) => {
                if (!running) return;

                running = false;
                lock = true;
                cancelAnimationFrame(rafId);
                stopTiles();

                const seconds = Math.max(5, Math.round((performance.now() - sessionStart) / 1000));

                hitsInput.value = hits;
                missesInput.value = misses;
                comboInput.value = maxCombo;
                secondsInput.value = seconds;

                resultTitle.textContent = title;
                resultStats.textContent = `${hits} tepat · ${misses} meleset · kombo ${maxCombo}`;
                resultNote.textContent = 'Menyimpan skor...';
                resultEl.hidden = false;
                surrenderBtn.hidden = true;
                playEnd();

                setTimeout(() => form.submit(), 1400);
            };

            const start = () => {
                running = true;
                lock = false;
                lives = MAX_LIVES;
                hits = 0;
                misses = 0;
                combo = 0;
                maxCombo = 0;
                round = 0;
                sessionStart = performance.now();

                startBtn.hidden = true;
                surrenderBtn.hidden = false;
                resultEl.hidden = true;

                nextRound();
            };

            board.addEventListener('click', (event) => {
                const tile = event.target.closest('.game-tile');

                if (tile && !tile.disabled) choose(tile);
            });

            startBtn.addEventListener('click', start);
            surrenderBtn.addEventListener('click', () => finish('Kamu menyerah.'));
            soundBtn.addEventListener('click', () => {
                soundOn = !soundOn;
                soundBtn.textContent = soundOn ? 'Suara' : 'Bisu';
            });
        })();
    </script>
@endpush

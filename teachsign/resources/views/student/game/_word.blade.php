    <section class="card game-card">
        <div class="game-head">
            <div>
                <p class="muted">Lihat gambarnya, lalu ketik jawaban yang benar. Salah atau kehabisan waktu mengurangi nyawa.</p>
            </div>
            <div class="game-stats">
                <span class="badge badge-open" id="word-round">Kata 1</span>
                <span class="badge badge-open" id="word-points">0 poin</span>
                <span class="badge badge-open" id="word-combo">Kombo 0</span>
                <span class="badge badge-open" id="word-lives">3 nyawa</span>
                <button type="button" class="btn btn-ghost btn-sm" id="word-sound">Suara</button>
            </div>
        </div>

        <div class="rush-timer">
            <span class="muted">Sisa waktu</span>
            <span class="rush-timer-value" id="word-timer">20.0s</span>
        </div>
        <div class="rush-bar"><span id="word-bar"></span></div>

        <div class="game-prompt">
            <span class="muted">Eja isyarat ini jadi satu kata</span>
            <div class="word-clue-sequence" id="word-clue"></div>
            <span class="word-hint" id="word-hint">—</span>
        </div>

        @if (empty($wordItems))
            <div class="alert alert-danger">
                Belum ada kata di Word Quest. Minta admin menambahkan kata dulu.
            </div>
        @endif

        <form id="word-answer" class="word-answer" autocomplete="off">
            <input type="text" id="word-input" class="input word-input" placeholder="Ketik kata..." maxlength="50" disabled>
            <button type="submit" class="btn btn-primary" id="word-submit" disabled>Jawab</button>
        </form>

        <div class="game-actions">
            <button type="button" class="btn btn-primary" id="word-start" @disabled(empty($wordItems))>Mulai</button>
            <button type="button" class="btn btn-danger btn-sm" id="word-surrender" hidden>Menyerah</button>
        </div>

        <div class="game-result-inline" id="word-result" hidden>
            <p class="game-result-title" id="word-result-title">Selesai!</p>
            <p class="muted" id="word-result-stats">—</p>
            <p class="muted" id="word-result-note">Menyimpan skor...</p>
        </div>

        <form method="POST" action="{{ route('student.game.submit', $mode) }}" id="word-form" hidden>
            @csrf
            <input type="hidden" name="hits" id="word-hits-input" value="0">
            <input type="hidden" name="misses" id="word-misses-input" value="0">
            <input type="hidden" name="max_combo" id="word-max-combo-input" value="0">
            <input type="hidden" name="seconds" id="word-seconds-input" value="0">
        </form>
    </section>

@push('scripts')
    <script>
        (() => {
            const WORDS = @json($wordItems);
            const WORD_SECONDS = 20;
            const START_LIVES = 3;

            const clueEl = document.getElementById('word-clue');
            const hintEl = document.getElementById('word-hint');
            const inputEl = document.getElementById('word-input');
            const submitBtn = document.getElementById('word-submit');
            const answerForm = document.getElementById('word-answer');
            const timerEl = document.getElementById('word-timer');
            const barEl = document.getElementById('word-bar');
            const roundEl = document.getElementById('word-round');
            const pointsEl = document.getElementById('word-points');
            const comboEl = document.getElementById('word-combo');
            const livesEl = document.getElementById('word-lives');
            const resultEl = document.getElementById('word-result');
            const resultTitle = document.getElementById('word-result-title');
            const resultStats = document.getElementById('word-result-stats');
            const startBtn = document.getElementById('word-start');
            const soundBtn = document.getElementById('word-sound');
            const surrenderBtn = document.getElementById('word-surrender');
            const form = document.getElementById('word-form');
            const hitsInput = document.getElementById('word-hits-input');
            const missesInput = document.getElementById('word-misses-input');
            const maxComboInput = document.getElementById('word-max-combo-input');
            const secondsInput = document.getElementById('word-seconds-input');

            let audio = null;
            let soundOn = true;
            let running = false;
            let lock = false;
            let hits = 0;
            let misses = 0;
            let combo = 0;
            let maxCombo = 0;
            let lives = START_LIVES;
            let wordsDone = 0;
            let points = 0;
            let currentWord = '';
            let startedAt = 0;
            let wordEndsAt = 0;
            let frame = null;
            let lastWord = '';

            const clean = (value) => value.trim().toUpperCase().replace(/\s+/g, ' ');

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

            const playHit = () => tone(880, 0.12, 'triangle');
            const playWrong = () => tone(160, 0.2, 'sawtooth');
            const playFanfare = () => {
                tone(660, 0.1);
                setTimeout(() => tone(880, 0.1), 100);
                setTimeout(() => tone(1100, 0.18), 200);
            };

            const updateHud = () => {
                roundEl.textContent = `Kata ${wordsDone + 1}`;
                pointsEl.textContent = `${points} poin`;
                comboEl.textContent = `Kombo ${combo}`;
                livesEl.textContent = `${lives} nyawa`;
            };

            const nextWord = () => {
                if (WORDS.length === 0) return;

                let pickWord = WORDS[Math.floor(Math.random() * WORDS.length)];

                if (WORDS.length > 1) {
                    while (pickWord.word === lastWord) {
                        pickWord = WORDS[Math.floor(Math.random() * WORDS.length)];
                    }
                }

                lastWord = pickWord.word;
                currentWord = clean(pickWord.word);
                lock = false;
                clueEl.innerHTML = pickWord.clues
                    .map((clue) => clue.space
                        ? '<span class="word-clue-gap"></span>'
                        : `<img src="${clue.image}" alt="">`)
                    .join('');
                hintEl.textContent = `${pickWord.letters} huruf`;
                inputEl.value = '';
                inputEl.disabled = false;
                submitBtn.disabled = false;
                inputEl.focus();
                wordEndsAt = performance.now() + WORD_SECONDS * 1000;
                updateHud();
                cancelAnimationFrame(frame);
                frame = requestAnimationFrame(tick);
            };

            const tick = () => {
                if (!running) return;

                const left = Math.max(0, wordEndsAt - performance.now());
                timerEl.textContent = `${(left / 1000).toFixed(1)}s`;
                barEl.style.width = `${(left / (WORD_SECONDS * 1000)) * 100}%`;

                if (left <= 0) {
                    loseLife('Waktu habis!');
                    return;
                }

                frame = requestAnimationFrame(tick);
            };

            const loseLife = (title) => {
                if (!running || lock) return;

                lock = true;
                misses += 1;
                combo = 0;
                lives -= 1;
                playWrong();
                updateHud();
                inputEl.disabled = true;
                submitBtn.disabled = true;

                if (lives <= 0) {
                    finish(title);
                    return;
                }

                setTimeout(nextWord, 400);
            };

            const finish = (title) => {
                if (!running) return;

                running = false;
                cancelAnimationFrame(frame);
                barEl.style.width = '0%';

                const seconds = Math.max(5, Math.round((performance.now() - startedAt) / 1000));

                hitsInput.value = hits;
                missesInput.value = misses;
                maxComboInput.value = maxCombo;
                secondsInput.value = seconds;

                resultTitle.textContent = title;
                resultStats.textContent = `${hits} kata benar · ${misses} salah · kombo terbaik ${maxCombo}`;
                resultEl.hidden = false;
                surrenderBtn.hidden = true;
                playFanfare();

                setTimeout(() => form.submit(), 1400);
            };

            answerForm.addEventListener('submit', (event) => {
                event.preventDefault();

                if (!running || lock) return;

                const guess = clean(inputEl.value);

                if (guess === '') return;

                if (guess === currentWord) {
                    hits += 1;
                    combo += 1;
                    maxCombo = Math.max(maxCombo, combo);
                    points += 100 * (combo >= 4 ? 3 : combo >= 2 ? 2 : 1);
                    wordsDone += 1;
                    playHit();
                    updateHud();
                    lock = true;
                    inputEl.disabled = true;
                    submitBtn.disabled = true;
                    setTimeout(nextWord, 450);

                    return;
                }

                misses += 1;
                combo = 0;
                lives -= 1;
                inputEl.value = '';
                inputEl.classList.add('is-wrong');
                playWrong();
                updateHud();

                setTimeout(() => inputEl.classList.remove('is-wrong'), 300);

                if (lives <= 0) {
                    inputEl.disabled = true;
                    submitBtn.disabled = true;
                    finish('Nyawa habis!');
                }
            });

            const start = () => {
                running = true;
                hits = 0;
                misses = 0;
                combo = 0;
                maxCombo = 0;
                lives = START_LIVES;
                wordsDone = 0;
                points = 0;
                startedAt = performance.now();

                startBtn.hidden = true;
                surrenderBtn.hidden = false;
                nextWord();
            };

            startBtn.addEventListener('click', start);
            surrenderBtn.addEventListener('click', () => finish('Kamu menyerah.'));
            soundBtn.addEventListener('click', () => {
                soundOn = !soundOn;
                soundBtn.textContent = soundOn ? 'Suara' : 'Bisu';
            });
        })();
    </script>
@endpush

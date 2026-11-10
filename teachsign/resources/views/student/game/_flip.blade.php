    <section class="card game-card">
        <div class="game-head">
            <div>
                <p class="muted">Balik dua kartu dan cocokkan huruf dengan isyaratnya. Semakin sedikit salah, semakin tinggi skor.</p>
            </div>
            <div class="game-stats">
                <span class="badge badge-open" id="flip-pairs">0/6 pasangan</span>
                <span class="badge badge-open" id="flip-misses">0 salah</span>
                <span class="badge badge-open" id="flip-timer">01:30</span>
                <button type="button" class="btn btn-ghost btn-sm" id="flip-sound">Suara</button>
            </div>
        </div>

        <div class="flip-board" id="flip-board"></div>

        <div class="game-actions">
            <button type="button" class="btn btn-primary" id="flip-start">Mulai</button>
            <button type="button" class="btn btn-danger btn-sm" id="flip-surrender" hidden>Menyerah</button>
        </div>

        <div class="game-result-inline" id="flip-result" hidden>
            <p class="game-result-title" id="flip-result-title">Selesai!</p>
            <p class="muted" id="flip-result-stats">—</p>
            <p class="muted" id="flip-result-note">Menyimpan skor...</p>
        </div>

        <form method="POST" action="{{ route('student.game.submit', $mode) }}" id="flip-form" hidden>
            @csrf
            <input type="hidden" name="hits" id="flip-hits-input" value="0">
            <input type="hidden" name="misses" id="flip-misses-input" value="0">
            <input type="hidden" name="max_combo" value="0">
            <input type="hidden" name="seconds" id="flip-seconds-input" value="0">
        </form>
    </section>

@push('scripts')
    <script>
        (() => {
            const LETTERS = @json($letters);
            const PAIRS = 6;
            const DURATION = 90;

            const board = document.getElementById('flip-board');
            const pairsEl = document.getElementById('flip-pairs');
            const missesEl = document.getElementById('flip-misses');
            const timerEl = document.getElementById('flip-timer');
            const resultEl = document.getElementById('flip-result');
            const resultTitle = document.getElementById('flip-result-title');
            const resultStats = document.getElementById('flip-result-stats');
            const startBtn = document.getElementById('flip-start');
            const soundBtn = document.getElementById('flip-sound');
            const surrenderBtn = document.getElementById('flip-surrender');
            const form = document.getElementById('flip-form');
            const hitsInput = document.getElementById('flip-hits-input');
            const missesInput = document.getElementById('flip-misses-input');
            const secondsInput = document.getElementById('flip-seconds-input');

            let audio = null;
            let soundOn = true;
            let running = false;
            let lock = false;
            let selected = [];
            let matched = 0;
            let misses = 0;
            let moves = 0;
            let secondsLeft = DURATION;
            let startedAt = 0;
            let clock = null;

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

            const playFlip = () => tone(520, 0.08, 'square');
            const playHit = () => tone(880, 0.12, 'triangle');
            const playWrong = () => tone(160, 0.2, 'sawtooth');
            const playFanfare = () => {
                tone(660, 0.1);
                setTimeout(() => tone(880, 0.1), 100);
                setTimeout(() => tone(1100, 0.18), 200);
            };

            const format = (total) => {
                const minutes = String(Math.floor(total / 60)).padStart(2, '0');
                const rest = String(Math.max(0, total % 60)).padStart(2, '0');
                return `${minutes}:${rest}`;
            };

            const updateHud = () => {
                pairsEl.textContent = `${matched / 2}/${PAIRS} pasangan`;
                missesEl.textContent = `${misses} salah`;
                timerEl.textContent = format(secondsLeft);
            };

            const matchedPairs = () => Math.floor(matched / 2);

            const buildDeck = () => {
                const picked = [...LETTERS].sort(() => Math.random() - 0.5).slice(0, PAIRS);
                const deck = [];

                picked.forEach((item, index) => {
                    deck.push({ pair: index, kind: 'letter', label: item.letter });
                    deck.push({ pair: index, kind: 'image', label: item.image });
                });

                deck.sort(() => Math.random() - 0.5);
                board.innerHTML = '';

                deck.forEach((card) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'flip-card';
                    button.dataset.pair = card.pair;
                    button.dataset.kind = card.kind;
                    const face = card.kind === 'image'
                        ? `<span class="flip-face"><img src="${card.label}" alt=""></span>`
                        : `<span class="flip-face">${card.label}</span>`;
                    button.innerHTML = `${face}<span class="flip-back">?</span>`;
                    button.addEventListener('click', () => flip(button));
                    board.appendChild(button);
                });
            };

            const finish = (title) => {
                if (!running) return;

                running = false;
                clearInterval(clock);

                const seconds = Math.max(5, Math.round((performance.now() - startedAt) / 1000));

                hitsInput.value = matchedPairs();
                missesInput.value = misses;
                secondsInput.value = seconds;

                resultTitle.textContent = title;
                resultStats.textContent = `${matchedPairs()} pasangan · ${misses} salah · ${seconds} detik`;
                resultEl.hidden = false;
                surrenderBtn.hidden = true;
                playFanfare();

                setTimeout(() => form.submit(), 1400);
            };

            const flip = (card) => {
                if (!running || lock || card.classList.contains('is-flipped')) return;

                card.classList.add('is-flipped');
                playFlip();
                selected.push(card);

                if (selected.length < 2) return;

                lock = true;
                moves += 1;
                const [first, second] = selected;

                if (first.dataset.pair === second.dataset.pair && first.dataset.kind !== second.dataset.kind) {
                    matched += 2;
                    selected = [];
                    lock = false;
                    playHit();
                    updateHud();
                    first.classList.add('is-matched');
                    second.classList.add('is-matched');

                    if (matched === PAIRS * 2) finish('Semua pasangan cocok!');

                    return;
                }

                misses += 1;
                playWrong();
                updateHud();

                setTimeout(() => {
                    first.classList.remove('is-flipped');
                    second.classList.remove('is-flipped');
                    selected = [];
                    lock = false;
                }, 800);
            };

            const start = () => {
                running = true;
                lock = false;
                selected = [];
                matched = 0;
                misses = 0;
                moves = 0;
                secondsLeft = DURATION;
                startedAt = performance.now();

                buildDeck();
                updateHud();
                startBtn.hidden = true;
                surrenderBtn.hidden = false;

                clock = setInterval(() => {
                    secondsLeft -= 1;
                    updateHud();

                    if (secondsLeft <= 0) finish('Waktu habis!');
                }, 1000);
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

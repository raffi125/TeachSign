@extends('layouts.app')

@section('title', $quiz->title ?: 'Kerjakan Kuis')
@section('heading', $quiz->title ?: 'Kerjakan Kuis')

@section('actions')
    <a href="{{ route('student.lessons.show', $lesson) }}" class="btn btn-ghost">‹ Kembali ke pelajaran</a>
@endsection

@section('content')
    <section class="card">
        <div class="quiz-play-head">
            <div>
                <p class="muted">{{ $lesson->title }}</p>
                @if ($quiz->duration_seconds !== null)
                    <span class="quiz-timer" id="quiz-timer" data-deadline="{{ $deadline }}">
                        <span id="quiz-timer-text">
                            {{ sprintf('%02d:%02d', intdiv($remaining, 60), $remaining % 60) }}
                        </span>
                    </span>
                @else
                    <span class="badge badge-open">Tanpa batas waktu</span>
                @endif
            </div>
            <span class="quiz-position" id="quiz-position">
                Soal 1 dari {{ $quiz->questions->count() }}
            </span>
        </div>

        @if ($quiz->questions->count() > 1)
            <nav class="quiz-nav" aria-label="Navigator soal">
                @foreach ($quiz->questions as $index => $question)
                    <button type="button" class="quiz-nav-item {{ $index === 0 ? 'is-active' : '' }}"
                            data-target="{{ $index }}">{{ $index + 1 }}</button>
                @endforeach
            </nav>
        @endif

        <form id="quiz-form" method="POST" action="{{ route('student.quizzes.submit', [$lesson, $quiz]) }}" class="quiz-play">
            @csrf

            @foreach ($quiz->questions as $index => $question)
                <fieldset class="quiz-slide {{ $index === 0 ? 'is-active' : '' }}" data-index="{{ $index }}">
                    <legend class="quiz-play-question">{{ $question->question }}</legend>

                    @php($media = \App\Support\MediaEmbed::parse($question->media_url ?? null))

                    @if ($media['type'] === 'youtube' || $media['type'] === 'drive')
                        <div class="media-embed question-media">
                            <iframe src="{{ $media['embed'] }}" title="Media soal {{ $index + 1 }}"
                                    loading="lazy" allowfullscreen
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    referrerpolicy="strict-origin-when-cross-origin"></iframe>
                        </div>
                    @elseif ($media['type'] === 'video')
                        <video class="question-media" controls preload="metadata" src="{{ $question->media_url }}"></video>
                    @elseif ($media['type'] === 'image')
                        <img class="question-media" src="{{ $question->media_url }}" alt="Media soal {{ $index + 1 }}">
                    @elseif ($media['type'] === 'link')
                        <a href="{{ $question->media_url }}" target="_blank" rel="noopener" class="media-link">Lihat media soal ↗</a>
                    @endif

                    @if ($question->type === 'essay')
                        <textarea name="answers[{{ $question->id }}]" class="textarea" rows="4" maxlength="500"
                                  placeholder="Tulis jawabanmu di sini">{{ old('answers.'.$question->id) }}</textarea>
                        <p class="hint">Jawaban esai akan dinilai manual oleh admin.</p>
                    @else
                        <div class="answer-options">
                            @foreach ($question->options ?? [] as $option)
                                <label class="answer-option">
                                    <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option }}"
                                           @checked(old('answers.'.$question->id) === $option)>
                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </fieldset>
            @endforeach

            <div class="form-actions quiz-actions">
                <button type="button" class="btn btn-ghost" id="quiz-prev" disabled>‹ Sebelumnya</button>
                <button type="button" class="btn btn-secondary" id="quiz-next">Berikutnya ›</button>
                <button type="submit" class="btn btn-primary" id="quiz-submit">Kirim Jawaban</button>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            const slides = [...document.querySelectorAll('.quiz-slide')];
            const navItems = [...document.querySelectorAll('.quiz-nav-item')];
            const positionLabel = document.getElementById('quiz-position');
            const prevButton = document.getElementById('quiz-prev');
            const nextButton = document.getElementById('quiz-next');
            const submitButton = document.getElementById('quiz-submit');
            let current = 0;

            const show = (index) => {
                if (index < 0 || index >= slides.length) return;
                current = index;

                slides.forEach((slide, i) => slide.classList.toggle('is-active', i === index));
                navItems.forEach((item, i) => item.classList.toggle('is-active', i === index));
                positionLabel.textContent = `Soal ${index + 1} dari ${slides.length}`;
                prevButton.disabled = index === 0;
                nextButton.hidden = index === slides.length - 1;
                submitButton.hidden = index !== slides.length - 1;
            };

            navItems.forEach((item) => item.addEventListener('click', () => show(Number(item.dataset.target))));
            prevButton?.addEventListener('click', () => show(current - 1));
            nextButton?.addEventListener('click', () => show(current + 1));

            show(0);
        })();
    </script>

    @if ($quiz->duration_seconds !== null)
        <script>
            (() => {
                const timerEl = document.getElementById('quiz-timer');
                if (!timerEl) return;

                const deadline = Number(timerEl.dataset.deadline);
                const label = document.getElementById('quiz-timer-text');
                const form = document.getElementById('quiz-form');
                let submitted = false;

                const tick = () => {
                    const left = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
                    const minutes = String(Math.floor(left / 60)).padStart(2, '0');
                    const seconds = String(left % 60).padStart(2, '0');

                    label.textContent = `${minutes}:${seconds}`;
                    timerEl.classList.toggle('is-low', left <= 10);

                    if (left <= 0 && !submitted) {
                        submitted = true;
                        form?.requestSubmit();
                    }
                };

                tick();
                setInterval(tick, 1000);
            })();
        </script>
    @endif
@endpush

@php
    $isEdit = isset($quiz);
    $blankQuestion = ['question' => '', 'media_url' => '', 'type' => 'choice', 'options' => ['', '', '', ''], 'correct_answer' => ''];
    $questionValues = old('questions', $isEdit
        ? $quiz->questions->map(fn ($question) => [
            'question' => $question->question,
            'media_url' => $question->media_url ?? '',
            'type' => $question->type ?? 'choice',
            'options' => $question->options ?? [],
            'correct_answer' => $question->correct_answer,
        ])->all()
        : [$blankQuestion]);
    $questionValues = is_array($questionValues) && $questionValues !== [] ? array_values($questionValues) : [$blankQuestion];
@endphp

<div class="form-grid">
    <div class="field">
        <label for="lesson_id">Pelajaran</label>
        <select id="lesson_id" name="lesson_id" class="select" required>
            <option value="">Pilih pelajaran</option>
            @foreach ($lessons as $lesson)
                <option value="{{ $lesson->id }}"
                    @selected((int) old('lesson_id', $selectedLesson ?? $quiz->lesson_id ?? 0) === $lesson->id)>
                    {{ $lesson->title }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label for="title">Judul kuis</label>
        <input id="title" type="text" name="title" class="input" maxlength="150" required
               value="{{ old('title', $quiz->title ?? '') }}" placeholder="Contoh: Kuis Huruf A-C">
        <p class="hint">Satu kuis boleh berisi banyak soal.</p>
    </div>

    <div class="field">
        <label for="unlock_at">Jadwal buka otomatis <span class="muted">(opsional)</span></label>
        <input id="unlock_at" type="datetime-local" name="unlock_at" class="input"
               value="{{ old('unlock_at', $isEdit && $quiz->unlock_at ? $quiz->unlock_at->format('Y-m-d\TH:i') : '') }}">
        <p class="hint">Kosongkan bila tidak memakai auto-unlock.</p>
    </div>

    <div class="field">
        <label for="duration_seconds">Batas waktu (detik) <span class="muted">(opsional)</span></label>
        <input id="duration_seconds" type="number" name="duration_seconds" class="input"
               min="10" max="3600" step="5" placeholder="Contoh: 60"
               value="{{ old('duration_seconds', $quiz->duration_seconds ?? '') }}">
        <p class="hint">Kosongkan bila tanpa batas. Hitungan mundur mulai saat kuis dibuka.</p>
    </div>

    <div class="field field-inline">
        <input type="hidden" name="is_locked" value="0">
        <label class="checkbox">
            <input type="checkbox" name="is_locked" value="1"
                   @checked(old('is_locked', $quiz->is_locked ?? true))>
            <span>Kunci kuis (siswa belum bisa mengerjakan)</span>
        </label>
    </div>

    <div class="field field-inline">
        <input type="hidden" name="is_score_visible" value="0">
        <label class="checkbox">
            <input type="checkbox" name="is_score_visible" value="1"
                   @checked(old('is_score_visible', $quiz->is_score_visible ?? false))>
            <span>Tampilkan nilai kuis ke siswa</span>
        </label>
    </div>
</div>

<div class="card-header question-list-head">
    <h2>Soal</h2>
    <button type="button" class="btn btn-secondary btn-sm" onclick="addQuestion()">+ Tambah soal</button>
</div>

<div id="question-list" class="question-list">
    @foreach ($questionValues as $qIndex => $questionValue)
        @php
            $type = ($questionValue['type'] ?? 'choice') === 'essay' ? 'essay' : 'choice';
            $options = $questionValue['options'] ?? [];
            $options = is_array($options) ? array_values($options) : [];
            $options = $options === [] ? [''] : $options;
        @endphp
        <div class="question-block" data-index="{{ $qIndex }}">
            <div class="question-block-head">
                <span class="game-number question-number"></span>
                <button type="button" class="btn btn-ghost btn-sm" onclick="removeQuestion(this)">Hapus soal</button>
            </div>

            <div class="field field-full">
                <label>Pertanyaan</label>
                <textarea name="questions[{{ $qIndex }}][question]" class="textarea" rows="2"
                          maxlength="2000" required>{{ $questionValue['question'] ?? '' }}</textarea>
            </div>

            <div class="field field-full">
                <label>Media soal <span class="muted">(opsional)</span></label>
                <input type="text" name="questions[{{ $qIndex }}][media_url]" class="input" maxlength="500"
                       value="{{ $questionValue['media_url'] ?? '' }}"
                       placeholder="URL gambar/video YouTube/Drive">
                <p class="hint">Kosongkan bila soal tidak memakai media.</p>
            </div>

            <div class="field">
                <label>Jenis soal</label>
                <select name="questions[{{ $qIndex }}][type]" class="select question-type"
                        onchange="toggleQuestionType(this)">
                    <option value="choice" @selected($type === 'choice')>Pilihan ganda</option>
                    <option value="essay" @selected($type === 'essay')>Esai</option>
                </select>
            </div>

            <div class="field field-full choice-fields" @if ($type === 'essay') hidden @endif>
                <span class="label">Pilihan jawaban</span>
                <div class="option-list">
                    @foreach ($options as $option)
                        <div class="option-row">
                            <input type="text" name="questions[{{ $qIndex }}][options][]" class="input" value="{{ $option }}"
                                   maxlength="255" placeholder="Pilihan" oninput="syncCorrectAnswer(this)"
                                   @disabled($type === 'essay')>
                            <button type="button" class="btn btn-ghost btn-sm" onclick="removeOption(this)"
                                    @disabled($type === 'essay')>Hapus</button>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="addOption(this)"
                        @disabled($type === 'essay')>+ Tambah pilihan</button>
                <p class="hint">Minimal 2, maksimal 8 pilihan per soal. Teks pilihan harus unik.</p>
            </div>

            <div class="field choice-fields" @if ($type === 'essay') hidden @endif>
                <label>Kunci jawaban</label>
                <select name="questions[{{ $qIndex }}][correct_answer]" class="select" required
                        @disabled($type === 'essay')>
                    @foreach ($options as $option)
                        @if ($option !== '')
                            <option value="{{ $option }}" @selected(($questionValue['correct_answer'] ?? '') === $option)>{{ $option }}</option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div class="field field-full essay-fields" @if ($type === 'choice') hidden @endif>
                <p class="hint">Soal esai tidak memakai kunci jawaban. Jawaban siswa dinilai manual lewat menu Penilaian.</p>
            </div>
        </div>
    @endforeach
</div>

<div class="form-actions">
    <a href="{{ route('admin.quizzes.index') }}" class="btn btn-ghost">Batal</a>
    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Simpan Perubahan' : 'Tambah Kuis' }}</button>
</div>

@once
    @push('scripts')
        <script>
            let questionCounter = {{ count($questionValues) }};

            function refreshNumbers() {
                document.querySelectorAll('#question-list .question-block').forEach((block, index) => {
                    block.querySelector('.question-number').textContent = index + 1;
                });
            }

            function toggleQuestionType(select) {
                const block = select.closest('.question-block');
                const isEssay = select.value === 'essay';

                block.querySelectorAll('.choice-fields').forEach(el => {
                    el.hidden = isEssay;
                    el.querySelectorAll('input, select, button').forEach(field => field.disabled = isEssay);
                });

                block.querySelectorAll('.essay-fields').forEach(el => {
                    el.hidden = !isEssay;
                    el.querySelectorAll('input, select, button').forEach(field => field.disabled = !isEssay);
                });
            }

            function syncCorrectAnswer(input) {
                const block = input.closest('.question-block');
                const select = block.querySelector('.choice-fields select');
                if (!select) return;

                const previous = select.value;
                const values = [...block.querySelectorAll('.option-row input[type="text"]')]
                    .map(el => el.value.trim())
                    .filter(value => value !== '');

                select.innerHTML = '';
                values.forEach(value => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = value;
                    if (value === previous) option.selected = true;
                    select.appendChild(option);
                });
            }

            function addQuestion() {
                const list = document.getElementById('question-list');
                const index = questionCounter++;
                const block = document.createElement('div');
                block.className = 'question-block';
                block.dataset.index = index;
                block.innerHTML = '<div class="question-block-head">'
                    + '<span class="game-number question-number"></span>'
                    + '<button type="button" class="btn btn-ghost btn-sm" onclick="removeQuestion(this)">Hapus soal</button>'
                    + '</div>'
                    + '<div class="field field-full"><label>Pertanyaan</label>'
                    + '<textarea name="questions[' + index + '][question]" class="textarea" rows="2" maxlength="2000" required></textarea></div>'
                    + '<div class="field field-full"><label>Media soal <span class="muted">(opsional)</span></label>'
                    + '<input type="text" name="questions[' + index + '][media_url]" class="input" maxlength="500" placeholder="URL gambar/video YouTube/Drive">'
                    + '<p class="hint">Kosongkan bila soal tidak memakai media.</p></div>'
                    + '<div class="field"><label>Jenis soal</label>'
                    + '<select name="questions[' + index + '][type]" class="select question-type" onchange="toggleQuestionType(this)">'
                    + '<option value="choice">Pilihan ganda</option><option value="essay">Esai</option></select></div>'
                    + '<div class="field field-full choice-fields"><span class="label">Pilihan jawaban</span>'
                    + '<div class="option-list"></div>'
                    + '<button type="button" class="btn btn-secondary btn-sm" onclick="addOption(this)">+ Tambah pilihan</button></div>'
                    + '<div class="field choice-fields"><label>Kunci jawaban</label>'
                    + '<select name="questions[' + index + '][correct_answer]" class="select" required></select></div>'
                    + '<div class="field field-full essay-fields" hidden>'
                    + '<p class="hint">Soal esai tidak memakai kunci jawaban. Jawaban siswa dinilai manual lewat menu Penilaian.</p></div>';
                list.appendChild(block);
                const addButton = block.querySelector('button[onclick="addOption(this)"]');
                addOption(addButton);
                addOption(addButton);
                toggleQuestionType(block.querySelector('.question-type'));
                refreshNumbers();
            }

            function removeQuestion(button) {
                const list = document.getElementById('question-list');
                if (list.querySelectorAll('.question-block').length <= 1) return;
                button.closest('.question-block').remove();
                refreshNumbers();
            }

            function addOption(button) {
                const block = button.closest('.question-block');
                const list = block.querySelector('.option-list');
                if (list.children.length >= 8) return;
                const row = document.createElement('div');
                row.className = 'option-row';
                row.innerHTML = '<input type="text" class="input" maxlength="255" placeholder="Pilihan" oninput="syncCorrectAnswer(this)">'
                    + '<button type="button" class="btn btn-ghost btn-sm" onclick="removeOption(this)">Hapus</button>';
                list.appendChild(row);
                syncCorrectAnswer(row.querySelector('input'));
            }

            function removeOption(button) {
                const block = button.closest('.question-block');
                const list = block.querySelector('.option-list');
                if (list.children.length <= 1) return;
                button.closest('.option-row').remove();
                syncCorrectAnswer(list.querySelector('input'));
            }

            refreshNumbers();
            document.querySelectorAll('.question-block').forEach(block => {
                toggleQuestionType(block.querySelector('.question-type'));
                const firstOption = block.querySelector('.option-row input');
                if (firstOption) syncCorrectAnswer(firstOption);
            });
        </script>
    @endpush
@endonce

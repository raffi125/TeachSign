@php($isEdit = isset($lesson))

<div class="form-grid">
    <div class="field">
        <label for="title">Judul</label>
        <input id="title" type="text" name="title" class="input"
               value="{{ old('title', $lesson->title ?? '') }}" maxlength="100" required>
    </div>

    <div class="field">
        <label for="category">Kategori</label>
        <select id="category" name="category" class="select" required>
            <option value="">Pilih kategori</option>
            @foreach (\App\Models\Lesson::CATEGORIES as $cat)
                <option value="{{ $cat }}" @selected(old('category', $lesson->category ?? '') === $cat)>{{ $cat }}</option>
            @endforeach
        </select>
    </div>

    <div class="field field-full">
        <label for="media_url">URL media <span class="muted">(opsional)</span></label>
        <input id="media_url" type="text" name="media_url" class="input"
               value="{{ old('media_url', $lesson->media_url ?? '') }}" maxlength="255"
               placeholder="https://www.youtube.com/watch?v=... atau https://drive.google.com/file/d/.../view">
        <p class="hint">Mendukung YouTube, Google Drive (share "anyone with the link"), atau URL file langsung (.mp4/.jpg).</p>

        <div class="media-preview" id="lesson-media-preview" aria-live="polite">
            <p class="muted">Pratinjau media muncul di sini.</p>
        </div>
    </div>

    <div class="field field-full">
        <label for="description">Panduan gerakan</label>
        <textarea id="description" name="description" class="textarea" rows="6" maxlength="5000" required>{{ old('description', $lesson->description ?? '') }}</textarea>
        <p class="hint">Jelaskan detail gerakan tangan secara runtut (10–5000 karakter).</p>
    </div>
</div>

<div class="form-actions">
    <a href="{{ route('admin.lessons.index') }}" class="btn btn-ghost">Batal</a>
    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Simpan Perubahan' : 'Tambah Pelajaran' }}</button>
</div>

@push('scripts')
<script>
(() => {
    const input = document.getElementById('media_url');
    const box = document.getElementById('lesson-media-preview');
    if (!input || !box) return;

    const extension = (path, list) => list.some((ext) => path.endsWith(ext));

    function render() {
        const url = input.value.trim();
        box.replaceChildren();

        if (!url) {
            box.innerHTML = '<p class="muted">Pratinjau media muncul di sini.</p>';
            return;
        }

        const youtube = url.match(/(?:youtu\.be\/|youtube(?:-nocookie)?\.com\/(?:watch\?v=|embed\/|shorts\/|live\/))([A-Za-z0-9_-]{6,})/i);
        const driveFile = url.match(/\/file\/d\/([A-Za-z0-9_-]+)/i) || url.match(/[?&]id=([A-Za-z0-9_-]+)/i);
        const path = url.split('?')[0].toLowerCase();
        let embedUrl = null;
        let kind = null;

        if (youtube) {
            embedUrl = 'https://www.youtube-nocookie.com/embed/' + youtube[1];
            kind = 'embed';
        } else if (url.toLowerCase().includes('drive.google.com') && driveFile) {
            embedUrl = 'https://drive.google.com/file/d/' + driveFile[1] + '/preview';
            kind = 'embed';
        } else if (extension(path, ['.mp4', '.webm', '.ogg'])) {
            kind = 'video';
        } else if (extension(path, ['.jpg', '.jpeg', '.png', '.gif', '.webp'])) {
            kind = 'image';
        } else {
            kind = 'link';
        }

        if (kind === 'embed') {
            const frame = document.createElement('iframe');
            frame.src = embedUrl;
            frame.title = 'Pratinjau media';
            frame.loading = 'lazy';
            frame.allowFullscreen = true;
            frame.referrerPolicy = 'strict-origin-when-cross-origin';
            const wrap = document.createElement('div');
            wrap.className = 'media-embed';
            wrap.appendChild(frame);
            box.appendChild(wrap);
        } else if (kind === 'video') {
            const video = document.createElement('video');
            video.className = 'lesson-media';
            video.controls = true;
            video.preload = 'metadata';
            video.src = url;
            box.appendChild(video);
        } else if (kind === 'image') {
            const image = document.createElement('img');
            image.className = 'lesson-media';
            image.src = url;
            image.alt = 'Pratinjau media';
            box.appendChild(image);
        } else {
            const link = document.createElement('a');
            link.className = 'media-link';
            link.href = url;
            link.target = '_blank';
            link.rel = 'noopener';
            link.textContent = 'Buka media ↗';
            box.appendChild(link);
        }
    }

    input.addEventListener('input', render);
    render();
})();
</script>
@endpush

@php($isEdit = isset($word))

<div class="form-grid">
    <div class="field field-full">
        <label for="word">Kata</label>
        <input id="word" type="text" name="word" class="input"
               value="{{ old('word', $word->word ?? '') }}" maxlength="50" required
               placeholder="Contoh: PAGI">
        <p class="hint">Huruf dan spasi saja. Kata otomatis disimpan dalam huruf kapital.</p>
    </div>

    <div class="field field-inline">
        <input type="hidden" name="is_active" value="0">
        <label class="checkbox">
            <input type="checkbox" name="is_active" value="1"
                   @checked(old('is_active', $word->is_active ?? true))>
            <span>Aktifkan kata ini di Mini Games</span>
        </label>
    </div>
</div>

<div class="form-actions">
    <a href="{{ route('admin.word-quest.index') }}" class="btn btn-ghost">Batal</a>
    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Simpan Perubahan' : 'Tambah Kata' }}</button>
</div>

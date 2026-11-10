@php($isEdit = isset($user))

<div class="form-grid">
    <div class="field">
        <label for="username">Username</label>
        <input id="username" type="text" name="username" class="input"
               value="{{ old('username', $user->username ?? '') }}" maxlength="50" required>
        <p class="hint">3–50 karakter: huruf, angka, titik, garis bawah.</p>
    </div>

    <div class="field">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" class="input"
               value="{{ old('email', $user->email ?? '') }}" maxlength="100" required>
    </div>

    <div class="field">
        <label for="role">Peran</label>
        <select id="role" name="role" class="select" required>
            <option value="student" @selected(old('role', $user->role ?? 'student') === 'student')>Student</option>
            <option value="admin" @selected(old('role', $user->role ?? '') === 'admin')>Admin</option>
        </select>
    </div>

    <div class="field">
        <label for="password">
            Kata sandi {{ $isEdit ? '(opsional)' : '' }}
        </label>
        <input id="password" type="password" name="password" class="input" {{ $isEdit ? '' : 'required' }}>
        <p class="hint">Minimal 8 karakter, huruf besar &amp; kecil serta angka.</p>
    </div>

    <div class="field">
        <label for="password_confirmation">Konfirmasi kata sandi</label>
        <input id="password_confirmation" type="password" name="password_confirmation" class="input" {{ $isEdit ? '' : 'required' }}>
    </div>
</div>

<div class="form-actions">
    <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Batal</a>
    <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Simpan Perubahan' : 'Tambah Pengguna' }}</button>
</div>

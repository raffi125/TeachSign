# 🤟 TeachSign

**TeachSign** adalah *Learning Management System* (LMS) berbasis web yang dirancang untuk pembelajaran bahasa isyarat secara interaktif melalui materi visual, evaluasi kuis, dan gamifikasi (*mini-games*). Platform ini dibuat secara efisien dan ringkas hanya menggunakan 2 tabel utama pada basis data.

---

## 👥 Anggota Kelompok

| No. | Nama | NPM |
| :---: | :--- | :---: |
| 1 | Dandy Erlangga Aryaputra | `140810210032` |
| 2 | Raffi Fauzan | `140810250067` |

---

## 🎯 Tujuan & Target Sustainable Development Goals (SDGs)

Pengembangan TeachSign berkontribusi pada pencapaian tujuan pembangunan berkelanjutan (**SDGs**), antara lain:

* **SDG 4: Pendidikan Berkualitas (Quality Education)**  
  Memberikan edukasi bahasa isyarat yang berkualitas, interaktif, inklusif, dan mudah diakses oleh siapa saja.
* **SDG 10: Berkurangnya Kesenjangan (Reduced Inequalities)**  
  Meningkatkan kesadaran dan jumlah masyarakat umum yang mahir menggunakan bahasa isyarat untuk mendorong aksesibilitas serta kesetaraan komunikasi bagi komunitas Tuli.

---

## 👨‍👩‍👧‍👦 Target Pengguna

* **Masyarakat Umum** (Pemula yang ingin mempelajari bahasa isyarat dari dasar).

---

## 🖼️ Mockup Tampilan

![Mockup TeachSign](https://via.placeholder.com/800x450.png?text=Mockup+Tampilan+TeachSign)
*(Tambahkan URL gambar mockup atau lampirkan berkas gambar di folder repositori)*

---

## 🗄️ Skema Database

Sistem ini menggunakan **2 tabel utama** untuk mengelola seluruh data pengguna, materi, evaluasi, hingga fitur gamifikasi.

### 1. Tabel `users` (Manajemen Akun & Hak Akses)

| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | `INT` / `UUID` | **Primary Key** |
| `username` | `VARCHAR(50)` | Nama pengguna |
| `email` | `VARCHAR(100)` | Email unik pengguna (**Unique**) |
| `password_hash` | `VARCHAR(255)` | Hash kata sandi untuk keamanan |
| `role` | `ENUM('admin', 'student')` | Hak akses pengguna |
| `created_at` | `TIMESTAMP` | Waktu pendaftaran |

---

### 2. Tabel `lessons` (Konten Edukasi, Kuis, & Game)

| Kolom | Tipe Data | Keterangan |
| :--- | :--- | :--- |
| `id` | `INT` / `UUID` | **Primary Key** |
| `title` | `VARCHAR(100)` | Judul materi/pelajaran |
| `category` | `VARCHAR(50)` | Kategori materi (misal: Abjad, Angka, Kata Sehari-hari) |
| `media_url` | `VARCHAR(255)` | URL berkas video/gambar gestur isyarat |
| `description` | `TEXT` | Panduan detail gerakan tangan |
| `quiz_data` | `JSON` / `TEXT` | Struktur soal & kunci jawaban kuis pilihan ganda |
| `game_data` | `JSON` / `TEXT` | Konfigurasi *mini-game* (misal: pencocokan kartu/memori) |
| `created_by` | `INT` / `UUID` | **Foreign Key** $\rightarrow$ `users.id` |
| `updated_at` | `TIMESTAMP` | Waktu pembaharuan terakhir |

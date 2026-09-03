# TeachSign
README repository yang berisi :
1. Anggota Kelompok :
	1. Dandy Erlangga Aryaputra - 140810210032
	2. Raffi Fauzan - 140810250067
2. Fungsi :
	1. TeachSign adalah Learning Management System (LMS) berbasis web untuk pembelajaran bahasa isyarat secara interaktif melalui materi visual, evaluasi kuis, serta gamifikasi (mini-games). Sistem dirancang efisien menggunakan 2 tabel database.
3. Tujuan (memenuhi min. 1 poin SDG) :
	1. Memberikan edukasi berkualitas, interaktif dan mudah akses tentang bahasa isyarat
	2. Meningkatkan jumlah masyarakat yang dapat menggunakan bahasa isyarat
4. Target Pengguna : 
	1. Masyarakat Umum
5. Mockup Kasar Sederhana : 
	1. 
6. Skema Database : 
	A.  Tabel 'users' (Manajemen Akun & Hak Akses)
	   - id            : INT / UUID (Primary Key)
	   - username      : VARCHAR(50)
	   - email         : VARCHAR(100) (Unique)
	   - password_hash : VARCHAR(255)
	   - role          : ENUM('admin', 'student')
	   - created_at    : TIMESTAMP
	
	B. Tabel 'lessons' (Konten Edukasi, Kuis, & Game)
	   - id            : INT / UUID (Primary Key)
	   - title         : VARCHAR(100)
	   - category      : VARCHAR(50)
	   - media_url     : VARCHAR(255) (URL video/gambar gestur isyarat)
	   - description   : TEXT (Panduan gerakan tangan)
	   - quiz_data     : JSON / TEXT (Struktur soal & kunci kuis pilihan ganda)
	   - game_data     : JSON / TEXT (Konfigurasi mini-game, misal: pasangan kartu)
	   - created_by    : INT / UUID (Foreign Key -> users.id)
	   - updated_at    : TIMESTAMP

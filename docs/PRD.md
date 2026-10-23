# Product Requirement Document (PRD) — SkillBridge (PHP Multi-Role Edition)

## 1. Ringkasan Produk

**SkillBridge** adalah aplikasi web dinamis yang menghubungkan pemetaan keterampilan (_skills_) dan bukti kompetensi (_evidence/portfolio_) mahasiswa dengan kebutuhan industri melalui tiga konsol terintegrasi yang dipisahkan berdasarkan peran (_role_):

1. **Konsol Mahasiswa (`mahasiswa`)**: Membantu mahasiswa & _fresh graduate_ mendata keahlian, mengunggah bukti kompetensi, memilih satu target karier, dan mengidentifikasi _skill gap_ secara jelas.
2. **Konsol HR / Perusahaan (`hr`)**: Memfasilitasi tim rekruter perusahaan mitra dalam mengelola profil perusahaan, mendefinisikan kebutuhan skill per posisi (_target roles_), memantau _pipeline_ kandidat, dan mengelola tim rekruter.
3. **Konsol Admin Platform (`admin`)**: Memungkinkan administrator mengelola pengguna, administrator, master data keterampilan (_skills_), target karier (_careers_), pemetaan _career-skill_, serta moderasi lowongan dan pengaturan sistem.

## 2. Tim Pengembang & Mata Kuliah

- **Mata Kuliah:** Desain dan Pemrograman Web (Proyek Berbasis Masalah / PjBL)
- **Dosen Pengampu:** Dimas Wahyu Wibowo, S.T., M.T.
- **Susunan Tim:**
  - **Ketua & Pengembang Utama:** Haikal Maghsin
  - **Analis & Desainer:** Marvelino Husca
  - **Sekretaris & Dokumentator:** M. Javier Thufail
  - **QA & Penguji:** Geraldi Rama Nugraha

## 3. Batasan Teknologi & Scope

### 3.1. Stack Teknologi Wajib

- **Frontend:** HTML5 Semantik, CSS3 Native (Flexbox, Grid, Custom Properties), Vanilla JavaScript (ES6+).
- **Backend:** PHP 8.x Native dengan arsitektur modular (`includes/layouts/`, `includes/auth/`, `includes/config/`).
- **Database:** PostgreSQL (Supabase) melalui ekstensi `PDO_PGSQL`.
- **Tanpa Framework Eksternal:** Tidak menggunakan Bootstrap, Tailwind, React, Vue, jQuery, maupun Laravel/CodeIgniter.

### 3.2. Batasan Non-Scope (Fitur yang Dilarang Dibuat pada Modul Mahasiswa)

- Tidak ada persentase kecocokan karier (_match rate_ %) atau skor kesiapan karier numerik.
- Tidak ada progress bar persentase atau rasio _"x of y skills covered"_.
- Tidak ada analisis repositori GitHub otomatis.
- Halaman _Skill Gap_ hanya menampilkan dua kategori daftar: **Skills You Already Have** (Dimiliki) dan **What to Explore Next** (Perlu Dikembangkan).

## 4. Sistem Desain & Layout Per Role

Setiap role memiliki karakteristik tata letak (_layout_) dan file CSS tersendiri yang dimuat melalui `includes/layouts/head.php`:

| Role / Konteks     | Folder Halaman           | Stylesheet yang Dimuat                | Komponen Navigasi Utama                                                                                                           |
| :----------------- | :----------------------- | :------------------------------------ | :-------------------------------------------------------------------------------------------------------------------------------- |
| **Publik & Auth**  | `public/*.php`           | `css/style.css`                       | `<header class="navbar">` (`public_header.php`) & `<footer class="lp-footer">` (`public_footer.php`)                              |
| **Mahasiswa**      | `public/mahasiswa/*.php` | `css/style.css`                       | Top Navigation Bar Pill (`mahasiswa_header.php`) & Footer Mahasiswa (`mahasiswa_footer.php`)                                      |
| **HR / Recruiter** | `public/hr/*.php`        | `css/style.css` + `css/recruiter.css` | `<aside class="rec-sidebar">` (`hr_sidebar.php`), `<header class="rec-header">` (`hr_header.php`), & `hr_footer.php`              |
| **Admin Platform** | `public/admin/*.php`     | `css/style.css` + `css/admin.css`     | `<aside class="admin-sidebar">` (`admin_sidebar.php`), `<header class="admin-header">` (`admin_header.php`), & `admin_footer.php` |

## 5. Spesifikasi Halaman & Pemetaan Modul PHP

### 5.1. Modul Publik & Autentikasi (`public/`)

1. **Landing Page (`public/index.php` — eks `index.html`)**: Halaman muka publik dengan informasi fitur SkillBridge, jalur pembelajaran, peluang karier, serta tombol menuju Login dan Register.
2. **Login (`public/login.php` — eks `login.html`)**: Autentikasi email & password. Setelah berhasil login, sistem mengarahkan pengguna ke konsol sesuai kolom `role`:
   - `mahasiswa` -> `public/mahasiswa/dashboard.php`
   - `hr` -> `public/hr/index.php`
   - `admin` -> `public/admin/index.php`
3. **Register (`public/register.php` — eks `register.html`)**: Pendaftaran akun baru (nama, email, password) dengan validasi server-side dan penyimpanan hash password (`password_hash()`).
4. **Logout (`public/logout.php`)**: Menghapus sesi aktif (`session_destroy()`) dan mengarahkan kembali ke `login.php`.

### 5.2. Modul Role Mahasiswa (`public/mahasiswa/`)

1. **Dashboard (`mahasiswa/dashboard.php` — eks `dashboard.html`)**: Menampilkan ringkasan target karier aktif, total skill yang dimiliki, 3 _evidence_ terbaru, jumlah _skill gap_, dan akses cepat _Add Evidence_.
2. **My Skills (`mahasiswa/skills.php` — eks `skills.html`)**: CRUD daftar keterampilan mahasiswa disertai indikator otomatis **Strong** (>= 2 _evidence_ terkait) atau **Growing** (< 2 _evidence_ terkait).
3. **Evidence (`mahasiswa/evidence.php` — eks `evidence.html`)**: CRUD bukti kompetensi (_Project_, _Certificate_, _Portfolio_, _Other_) beserta tautan URL dan pemetaan ke skill yang relevan.
4. **Career Target (`mahasiswa/career.php` — eks `career.html`)**: Eksplorasi pilihan target karier beserta daftar _required skills_ dan tombol **Set as Target**.
5. **Skill Gap (`mahasiswa/gap.php` — eks `gap.html`)**: Perbandingan langsung antara skill target karier dengan skill mahasiswa dalam dua daftar (_Skills You Already Have_ vs _What to Explore Next_) beserta jumlah _evidence_ per skill.
6. **Profile (`mahasiswa/profile.php` — eks `profile.html`)**: Pengelolaan informasi profil mahasiswa dan pembaruan password.
7. **Upload & Review CV (`mahasiswa/cv.php` & `mahasiswa/cv_review.php`)**: Unggah file CV (PDF/TXT) untuk pencocokan kata kunci skill dan konfirmasi daftar skill sebelum ditambahkan ke _My Skills_.

### 5.3. Modul Role HR / Perusahaan (`public/hr/`)

1. **Profil Perusahaan & Overview (`hr/index.php` — eks `recruiter.html`)**: Ringkasan konsol perusahaan mitra dan pengelolaan identitas perusahaan.
2. **Target Role & Kebutuhan Skill (`hr/roles.php` — eks `recruiter-roles.html`)**: Pengelolaan posisi/peran yang dibutuhkan perusahaan beserta spesifikasi skill wajib dan pendukung.
3. **Pipeline Kandidat (`hr/candidates.php` — eks `recruiter-candidates.html`)**: Peninjauan daftar kandidat mahasiswa, bukti portofolio (_evidence_), dan status seleksi kandidat.
4. **Log Aktivitas Recruiter (`hr/activity.php` — eks `recruiter-activity.html`)**: Riwayat aktivitas peninjauan kandidat dan perubahan lowongan oleh tim HR.
5. **Manajemen Tim Recruiter (`hr/team.php` — eks `recruiter-team.html`)**: Pengelolaan anggota tim HR/rekruter dalam satu perusahaan.
6. **Pengaturan Akun HR (`hr/settings.php` — eks `recruiter-settings.html`)**: Pengaturan preferensi akun rekruter dan notifikasi perusahaan.

### 5.4. Modul Role Admin (`public/admin/`)

1. **Admin Dashboard (`admin/index.php` — eks `admin.html`)**: Ringkasan metrik platform (total pengguna, total skill, karier, lowongan aktif, dan aktivitas sistem).
2. **Manajemen Pengguna (`admin/users.php` — eks `admin-users.html`)**: Daftar, pencarian, filter, dan pengelolaan status akun mahasiswa serta HR.
3. **Manajemen Administrator (`admin/admins.php` — eks `admin-admins.html`)**: Pengelolaan akun dengan hak akses administrator platform.
4. **Manajemen Keterampilan (`admin/skills.php` — eks `admin-skills.html`)**: CRUD master data _skills_ beserta kategori dan deskripsinya.
5. **Manajemen Target Karier (`admin/careers.php` — eks `admin-careers.html`)**: CRUD master data _careers_ (target karier yang tersedia di platform).
6. **Career-Skill Mapping (`admin/mapping.php` — eks `admin-mapping.html`)**: Pemetaan relasi antara target karier dan skill yang dibutuhkan (`career_skill_requirements`).
7. **Manajemen Lowongan (`admin/vacancies.php` — eks `admin-vacancies.html`)**: Moderasi dan pemantauan daftar peran/lowongan yang dipublikasikan perusahaan.
8. **Pengaturan Sistem (`admin/settings.php` — eks `admin-settings.html`)**: Konfigurasi parameter global aplikasi SkillBridge.

## 6. Skema Basis Data (`db_pbl.sql`)

Skema PostgreSQL mencakup tabel inti di `db_pbl.sql` dengan penambahan atribut `role` pada tabel `users` untuk mendukung pemisahan akses 3 role:

1. **`users`**: `id` (PK), `name`, `email` (UNIQUE), `password_hash`, `role` (`ENUM: 'mahasiswa', 'hr', 'admin'`, default `'mahasiswa'`), `target_career_id` (FK -> `careers.id`), `created_at`, `updated_at`.
2. **`careers`**: `id` (PK), `name`, `description`.
3. **`skills`**: `id` (PK), `name`, `category`, `description`.
4. **`career_skill_requirements`**: `id` (PK), `career_id` (FK -> `careers.id`), `skill_id` (FK -> `skills.id`), `required_level`, `is_mandatory`, `UNIQUE(career_id, skill_id)`.
5. **`user_skills`**: `id` (PK), `user_id` (FK -> `users.id`), `skill_id` (FK -> `skills.id`), `skill_confidence`, `last_updated`, `UNIQUE(user_id, skill_id)`.
6. **`evidence` / `evidences`**: `id` (PK), `user_id` (FK -> `users.id`), `skill_id` (FK -> `skills.id`), `source_type` (`cv`, `github`, `portfolio`, `experience`, `assessment`), `metadata` (JSONB — judul, tipe, URL, deskripsi), `confidence_score`, `created_at`.
7. **`assessment_questions`**: `id` (PK), `skill_id` (FK -> `skills.id`), `question_text`, `question_type`, `options` (JSONB), `weight`.
8. **Tabel Pendukung Modul HR (`companies` & `company_recruiters`)**: Menyimpan profil perusahaan mitra dan relasi anggota tim HR terhadap akun `users` ber-role `hr`.

## 7. Data Seed Awal

- **Target Karier Utama:** _Frontend Developer_, _Backend Developer_, _Data Analyst_, _UI/UX Designer_.
- **Daftar Keterampilan Awal:** HTML, CSS, JavaScript, React, Vue, PHP, Node.js, Python, PostgreSQL, MySQL, SQL, Tableau, Power BI, Pandas, Figma, Wireframing, Prototyping, User Research.

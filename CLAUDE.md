# CLAUDE.md — SkillBridge (PHP Native Multi-Role Architecture)

Dokumen ini adalah sumber aturan utama (**AI Rules & Guardrails**) untuk AI Coding Assistant (Claude Code, Cursor, GitHub Copilot, Windsurf) dan seluruh anggota tim saat mengembangkan dan memigrasikan **SkillBridge** dari HTML statis ke **PHP 8.x Native Modular**.

## 1. Konteks Proyek

- **Nama Proyek:** SkillBridge (PjBL Mata Kuliah _Desain dan Pemrograman Web_)
- **Tujuan:** Platform pemetaan keterampilan mahasiswa terhadap target karier, pengelolaan bukti kompetensi (_evidence/portfolio_), konsol rekrutmen perusahaan (HR/Recruiter), dan konsol administrasi platform (Admin).
- **Sumber Kebenaran:** `docs/PRD.md` untuk spesifikasi fitur & database; mockup di `docs/design/` untuk acuan visual.

## 2. Tech Stack & Batasan Library (Hard Constraints)

- **Backend:** PHP 8.x Native + PostgreSQL (Supabase via `PDO_PGSQL`).
- **Frontend:** HTML5 Semantik, CSS3 Native (Flexbox, Grid, CSS Custom Properties di `:root`), Vanilla JavaScript (ES6+, DOM, Event, Fetch API).
- **Font:** Google Fonts `Inter` (weights 400, 500, 600, 700, 800) dengan fallback `system-ui, sans-serif`.
- **DILARANG KERAS:**
  1. Menambahkan framework PHP (Laravel, CodeIgniter, Symfony) atau library eksternal tanpa izin eksplisit.
  2. Menambahkan framework CSS/JS (Tailwind, Bootstrap, React, Vue, jQuery, Alpine.js).
  3. Menambahkan CDN font/icon eksternal selain Google Fonts `Inter` (gunakan SVG inline atau file di `public/assets/icons/`).
  4. Menggunakan bundler atau npm package di sisi frontend.

## 3. Arsitektur Direktori (Pemisahan 3 Role & Layout Modular)

Seluruh halaman **WAJIB** dipisahkan berdasarkan role pengguna (`mahasiswa`, `hr`, `admin`) dan menggunakan komponen layout terpusat di `includes/layouts/`:

```text
SkillBridge/
├── includes/                        # Komponen Server-Side & Layout Partials
│   ├── config/
│   │   ├── app.php                  # Konstanta BASE_URL, fungsi base_url(), asset(), e()
│   │   └── database.php             # Koneksi PDO PostgreSQL (Supabase)
│   ├── auth/
│   │   ├── session.php              # Manajemen session PHP
│   │   ├── csrf.php                 # Generator & validator CSRF token
│   │   └── middleware.php           # Guard require_login() & require_role($role)
│   └── layouts/                     # Template Header, Sidebar, Footer per Role
│       ├── head.php                 # <!DOCTYPE html>, <head>, meta CSRF, CSS, window.APP_BASE_URL
│       ├── scripts.php              # Script global (ui.js, api.js) + $page_js
│       ├── public_header.php        # Navbar halaman publik (<header class="navbar">)
│       ├── public_footer.php        # Footer halaman publik (<footer class="lp-footer">)
│       ├── mahasiswa_header.php     # Topbar navigasi khusus Mahasiswa
│       ├── mahasiswa_footer.php     # Footer konsol Mahasiswa + penutup tag
│       ├── hr_header.php            # Topbar HR (<header class="rec-header">)
│       ├── hr_sidebar.php           # Sidebar HR (<aside class="rec-sidebar">)
│       ├── hr_footer.php            # Footer konsol HR + penutup wrapper
│       ├── admin_header.php         # Topbar Admin (<header class="admin-header">)
│       ├── admin_sidebar.php        # Sidebar Admin (<aside class="admin-sidebar">)
│       └── admin_footer.php         # Footer konsol Admin + penutup wrapper
├── public/                          # Document Root Web Server
│   ├── index.php                    # Landing Page Publik
│   ├── login.php                    # Halaman Login
│   ├── register.php                 # Halaman Registrasi
│   ├── logout.php                   # Handler Logout
│   ├── mahasiswa/                   # Role Mahasiswa (dashboard, career, gap, skills, evidence, profile, cv, cv_review)
│   ├── hr/                          # Role HR/Recruiter (index, roles, candidates, activity, team, settings)
│   ├── admin/                       # Role Admin (index, users, admins, skills, careers, mapping, vacancies, settings)
│   ├── css/                         # style.css, recruiter.css, admin.css
│   ├── js/                          # ui.js, api.js, landing.js, admin-nav.js, pages/*.js
│   └── assets/                      # icons/, landing/
├── db/                              # schema.sql, seed.sql, optional_hr_role.sql
└── tools/                           # create_admin.php (CLI)

(Data JSON dummy ada di public/data/. Role DB 'user' dipetakan ke role aplikasi 'mahasiswa'.)
```

## 4. Aturan Modularisasi PHP (WAJIB Dipatuhi AI)

1. **Single Source of Truth untuk Layout:**
   - **DILARANG** menulis tag `<!DOCTYPE html>`, `<head>`, `<header class="navbar">`, `<aside class="rec-sidebar">`, `<header class="rec-header">`, `<aside class="admin-sidebar">`, `<header class="admin-header">`, atau `<footer>` secara langsung di dalam file halaman (`public/mahasiswa/*.php`, `public/hr/*.php`, `public/admin/*.php`).
   - Setiap file halaman hanya boleh berisi:
     1. Pemanggilan middleware (`require_role('mahasiswa' | 'hr' | 'admin')`).
     2. Pendefinisian variabel halaman (`$page_title`, `$active_nav`, `$role_css`, `$page_js`).
     3. `require_once` untuk `head.php`, `sidebar`/`header` sesuai role, konten `<main>`, dan `footer` sesuai role.
2. **Preservasi Selector CSS & DOM JavaScript (Zero Visual Regression):**
   - **DILARANG** mengubah nama class CSS yang sudah ada di `public/css/style.css`, `public/css/recruiter.css`, maupun `public/css/admin.css`.
   - **DILARANG** mengubah atribut `id`, `data-*` (termasuk `<body data-nav="...">`), atau struktur DOM di dalam `<main>` yang menjadi target selector dari `public/js/pages/*.js`.
   - Saat mengekstrak `<aside>` atau `<header>` ke dalam `includes/layouts/`, markup HTML dan class CSS harus **100% identik** dengan file HTML aslinya; hanya atribut `href` dan class `active` yang dibuat dinamis menggunakan `$active_nav`.
3. **Aturan Path & URL (`base_url()` & `asset()`):**
   - **DILARANG** menulis path relatif mentah seperti `href="css/style.css"`, `src="js/ui.js"`, atau `href="admin-users.html"` karena akan menyebabkan error _404 Not Found_ saat halaman diakses dari dalam subfolder `public/mahasiswa/`, `public/hr/`, atau `public/admin/`.
   - **WAJIB** menggunakan `asset('css/...')`, `asset('js/...')`, dan `base_url('admin/users.php')` di PHP, serta `window.APP_BASE_URL` pada pemanggilan `fetch()` di `public/js/api.js`.
   - Seluruh link navigasi yang sebelumnya menunjuk ke `*.html` wajib diperbarui ke rute `*.php` sesuai tabel pemetaan di `docs/PRD.md`.

## 5. Aturan Keamanan & Kualitas Kode

1. **Pencegahan XSS (Server & Client):**
   - Di PHP: Wajib membungkus seluruh output dinamis dengan fungsi `e($value)` (`htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')`).
   - Di JS: Render data dinamis menggunakan `textContent` dan `createElement` (**DILARANG** menggunakan `innerHTML` dengan data dari user atau API). Link eksternal wajib memakai `target="_blank" rel="noopener noreferrer"`.
2. **Proteksi CSRF:**
   - `includes/layouts/head.php` wajib memuat `<meta name="csrf-token" content="<?= e(csrf_token()) ?>">`.
   - Setiap `<form method="POST">` wajib menyertakan `<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">`.
   - Request Fetch ke backend wajib menyertakan header `X-CSRF-Token`.
3. **Pencegahan SQL Injection:**
   - Seluruh interaksi database di `includes/` wajib menggunakan **PDO Prepared Statements** (`$pdo->prepare(...)` dan `$stmt->execute(...)`). **DILARANG** melakukan interpolasi variabel langsung ke dalam string SQL.
4. **Batasan Fitur Non-Scope (Sesuai PRD):**
   - Pada modul Mahasiswa (`gap.php` & `dashboard.php`), **DILARANG** membuat skor kesiapan karier dalam bentuk persentase kecocokan (_match rate_ %), progress bar persentase, rasio _"x of y skills covered"_, atau analisis GitHub otomatis. Halaman _Skill Gap_ hanya menampilkan dua daftar: **Skills You Already Have** dan **What to Explore Next**.

## 6. Urutan Eksekusi Migrasi untuk AI

Jika diminta melakukan migrasi dari HTML ke PHP, kerjakan secara bertahap dengan urutan berikut:

1. **Tahap 1:** Buat `includes/config/app.php`, `includes/auth/session.php`, `includes/auth/csrf.php`, dan `includes/auth/middleware.php` (sediakan mode _development fallback_ agar halaman tetap bisa dibuka saat pengujian UI sebelum login DB aktif).
2. **Tahap 2:** Buat seluruh partial layout di `includes/layouts/` (`head.php`, `scripts.php`, serta header/sidebar/footer untuk Publik, Mahasiswa, HR, dan Admin).
3. **Tahap 3:** Konversi dan pindahkan 23 file `.html` ke file `.php` di `public/`, `public/mahasiswa/`, `public/hr/`, dan `public/admin/`.
4. **Tahap 4:** Perbarui referensi link `.html` dan path `fetch()` di `public/js/ui.js`, `public/js/api.js`, `public/js/admin-nav.js`, dan `public/js/pages/*.js`.
5. **Tahap 5:** Uji dengan `php -S localhost:8000 -t public`, pastikan tidak ada aset 404, lalu hapus file `.html` lama yang sudah digantikan `.php`.

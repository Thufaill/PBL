# AI Rules & Guardrails: Perombakan Responsif & Koreksi Button SkillBridge

Dokumen ini adalah panduan instruksi khusus untuk AI Coding Assistant (Claude Code, Cursor, GitHub Copilot, Windsurf) guna memperbaiki **tampilan responsif (Mobile, Tablet, Desktop)** dan **mengkoreksi seluruh komponen tombol (Button System & Link Navigasi)** pada repositori [HaikalMaghsin/SkillBridge](https://www.google.com/search?q=https%3A%2F%2Fgithub.com%2FHaikalMaghsin%2FSkillBridge) pasca-migrasi PHP (`commit 5ebcbd5` & `cf1a061`).

## 1. Batasan Mutlak (Hard Constraints)

1. **DILARANG** menambahkan framework CSS/JS eksternal (Tailwind, Bootstrap, React, Vue, jQuery, Alpine.js). Wajib menggunakan **CSS3 Native** (Flexbox, Grid, Custom Properties) dan **Vanilla JavaScript (ES6+)**.
2. **DILARANG** mengubah struktur folder 3 role yang sudah berjalan (`public/mahasiswa/`, `public/hr/`, `public/admin/`, dan `includes/layouts/`).
3. **DILARANG** merusak selector ID atau atribut `data-*` yang dipakai oleh skrip di `public/js/pages/*.js`.

## 2. Daftar Koreksi Bug Tombol & Link Pasca-Migrasi PHP (Wajib Diperbaiki AI)

### 2.1. Koreksi Redirect Login Sesuai Role (`public/js/pages/login.js` & `public/login.php`)
- **Masalah:** Setelah login berhasil, `public/js/pages/login.js` masih menjalankan `location.href = "dashboard.php"`. Karena `login.php` berada di `public/login.php`, browser mencari `public/dashboard.php` (**404 Not Found**) dan belum membedakan role pengguna.
- **Aturan Perbaikan:**
  Ubah redirect menggunakan `window.APP_BASE_URL` dan arahkan sesuai role akun yang login:
  - Role `mahasiswa` -> `${window.APP_BASE_URL || ''}/mahasiswa/dashboard.php`
  - Role `hr` -> `${window.APP_BASE_URL || ''}/hr/index.php`
  - Role `admin` -> `${window.APP_BASE_URL || ''}/admin/index.php`

### 2.2. Koreksi Tombol Logout & Menu di Layout (`includes/layouts/*.php`)
- **Masalah:** Pada `mahasiswa_header.php`, `hr_sidebar.php`, dan `admin_sidebar.php`, tombol Logout masih menggunakan `<a href="<?= $path_prefix ?>logout.php">`. Jika `$path_prefix` tidak didefinisikan di suatu halaman, tombol mengarah ke rute yang salah (404).
- **Aturan Perbaikan:**
  Ganti seluruh `$path_prefix` pada link navigasi dan tombol Logout di `includes/layouts/*.php` dengan fungsi helper `<?= e(base_url('logout.php')) ?>`, `<?= e(base_url('mahasiswa/...')) ?>`, `<?= e(base_url('hr/...')) ?>`, dan `<?= e(base_url('admin/...')) ?>`.

### 2.3. Sinkronisasi `public/js/ui.js` dengan Header PHP
- **Masalah:** `NAV_ITEMS` di `public/js/ui.js` masih berisi `href: "dashboard.php"` (path relatif) dan fungsi `renderNavbar()` berisiko menimpa (`overwrite`) elemen `<header class="navbar">` yang sudah di-render oleh `mahasiswa_header.php` atau `public_header.php`.
- **Aturan Perbaikan:**
  1. Ubah seluruh `href` pada `NAV_ITEMS` di `public/js/ui.js` menjadi `${window.APP_BASE_URL || ''}/mahasiswa/...`.
  2. Jika `<header class="navbar">` sudah memiliki elemen navigasi dari server (PHP), `ui.js` **dilarang me-render ulang HTML navbar**, cukup memasang event listener (toggle menu mobile & logout).

### 2.4. Koreksi Tombol Pencarian di Landing Page (`public/js/landing.js`)
- **Masalah:** Pencarian cepat di `public/js/landing.js` mengarahkan ke `"mahasiswa/skills.php?q=..."` tanpa `window.APP_BASE_URL`.
- **Aturan Perbaikan:**
  Gunakan `${window.APP_BASE_URL || ''}/mahasiswa/skills.php` atau arahkan guest ke `login.php` dengan parameter query yang aman.

### 2.5. Koreksi Semantik `<button>` & Event Listener (`public/**/*.php` & `public/js/pages/*.js`)
1. **Wajib Atribut `type` Eksplisit:**
   - Setiap `<button>` yang **bukan** pengirim form utama (seperti tombol "Batal", "Tutup Modal", filter, tab, tambah/edit/hapus item, toggle menu) **WAJIB** memiliki `type="button"` agar tidak memicu *accidental form submit / page reload*.
   - Hanya tombol submit utama di dalam `<form>` yang boleh memakai `type="submit"`.
2. **Pemisahan `<a class="btn">` vs `<button class="btn">`:**
   - Tombol yang berpindah halaman/URL **WAJIB** memakai `<a href="..." class="btn ...">`.
   - Tombol yang memicu aksi JS/modal di halaman yang sama **WAJIB** memakai `<button type="button" class="btn ...">`.
3. **Event Delegation untuk Tombol di Tabel Dinamis:**
   - Pada `public/js/pages/*.js` (`admin-*.js`, `recruiter-*.js`, `skills.js`, `evidence.js`), gunakan *event delegation* pada container tabel (`tbody.addEventListener('click', ...)`) agar tombol Edit, Detail, dan Hapus yang di-render secara dinamis selalu merespons klik.

## 3. Standarisasi CSS Button System (`public/css/style.css`)

Perbarui definisi `.btn` di `public/css/style.css` (sekitar baris 268–327) agar seluruh varian tombol di halaman Publik, Mahasiswa, HR, dan Admin memiliki styling yang utuh:

```css
/* Base Button */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 40px;
  padding: 10px 18px;
  font-family: inherit;
  font-size: 14px;
  font-weight: 600;
  line-height: 1.2;
  text-align: center;
  text-decoration: none;
  white-space: nowrap;
  border-radius: 10px;
  border: 1px solid transparent;
  cursor: pointer;
  user-select: none;
  transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease, transform 0.1s ease, box-shadow 0.18s ease;
}

.btn:focus-visible {
  outline: 2px solid var(--color-primary, #1e3a8a);
  outline-offset: 2px;
}

.btn:active:not(:disabled) {
  transform: translateY(1px);
}

.btn:disabled,
.btn.is-disabled {
  opacity: 0.55;
  cursor: not-allowed;
  pointer-events: none;
}

/* Button Variants */
.btn-primary {
  background-color: var(--color-primary, #1e3a8a);
  color: #ffffff;
}
.btn-primary:hover:not(:disabled) {
  background-color: var(--color-primary-hover, #1e40af);
}

.btn-accent {
  background-color: var(--color-accent, #ec4899);
  color: #ffffff;
}
.btn-accent:hover:not(:disabled) {
  background-color: var(--color-accent-hover, #db2777);
}

.btn-secondary {
  background-color: #f1f5f9;
  color: #0f172a;
  border-color: #cbd5e1;
}
.btn-secondary:hover:not(:disabled) {
  background-color: #e2e8f0;
}

.btn-outline {
  background-color: transparent;
  color: #1e293b;
  border-color: #cbd5e1;
}
.btn-outline:hover:not(:disabled) {
  background-color: #f8fafc;
  border-color: #94a3b8;
}

.btn-danger {
  background-color: #fee2e2;
  color: #b91c1c;
  border-color: #fecaca;
}
.btn-danger:hover:not(:disabled) {
  background-color: #ef4444;
  color: #ffffff;
  border-color: #ef4444;
}

.btn-ghost {
  background-color: transparent;
  color: inherit;
  border-color: transparent;
}
.btn-ghost:hover:not(:disabled) {
  background-color: rgba(15, 23, 42, 0.06);
}

/* Button Sizes & Modifiers */
.btn-sm {
  min-height: 34px;
  padding: 6px 12px;
  font-size: 13px;
  border-radius: 8px;
}

.btn-lg,
.btn-44 {
  min-height: 44px;
  padding: 12px 22px;
  font-size: 15px;
}

.btn-icon {
  width: 40px;
  min-height: 40px;
  padding: 0;
  flex-shrink: 0;
}

.btn-block,
.w-100 {
  width: 100%;
  display: flex;
}
```

## 4. Aturan Desain Responsif Multi-Device (Mobile, Tablet, Desktop)

Gunakan 3 breakpoint konsisten di `public/css/style.css`, `public/css/recruiter.css`, dan `public/css/admin.css`:
- **Mobile Kecil:** `@media (max-width: 639px)`
- **Mobile / Phablet:** `@media (max-width: 767px)`
- **Tablet:** `@media (max-width: 1023px)`

### 4.1. Navbar Publik & Mahasiswa Responsif (`public_header.php`, `mahasiswa_header.php`, `style.css`, `ui.js`)
1. Tambahkan tombol hamburger `.navbar-toggle` di dalam `<header class="navbar">`:
   ```html
   <button type="button" class="navbar-toggle" aria-label="Buka menu navigasi" aria-expanded="false">
     <span class="navbar-toggle-bar"></span>
     <span class="navbar-toggle-bar"></span>
     <span class="navbar-toggle-bar"></span>
   </button>
   ```
2. Pada `@media (max-width: 767px)` di `public/css/style.css`:
   - Ubah `.navbar` dari `height: 88px` menjadi `height: auto; min-height: 64px; padding: 12px 16px; flex-wrap: wrap; position: sticky; top: 0; z-index: 100;`.
   - Tampilkan `.navbar-toggle` (`display: inline-flex;`), dan sembunyikan `.navbar-nav` serta `.navbar-actions` secara default (`display: none; width: 100%; flex-direction: column; align-items: stretch; gap: 8px; padding-top: 12px;`).
   - Saat `.navbar.is-open` aktif, tampilkan `.navbar-nav` dan `.navbar-actions` (`display: flex;`) serta buat tombol di dalam `.navbar-actions .btn` menjadi `width: 100%; justify-content: center;`.

### 4.2. Sidebar Off-Canvas Drawer untuk HR & Admin (`hr_sidebar.php`, `admin_sidebar.php`, `recruiter.css`, `admin.css`)
1. Di `hr_header.php` (`.rec-header`) dan `admin_header.php` (`.admin-header`), tambahkan tombol pembuka sidebar di sisi kiri judul halaman:
   ```html
   <button type="button" class="sidebar-toggle btn btn-ghost btn-icon" aria-label="Buka menu sidebar" aria-expanded="false">
     <!-- SVG icon menu 3 garis -->
   </button>
   ```
2. Di `hr_sidebar.php` dan `admin_sidebar.php`, tambahkan `<div class="sidebar-backdrop" data-close-sidebar></div>`.
3. Pada `@media (max-width: 1023px)` di `public/css/recruiter.css` dan `public/css/admin.css`:
   - Hapus perilaku *chip-bar* horizontal sempit yang memotong teks menu.
   - Atur `.rec-sidebar` dan `.admin-sidebar` menjadi *Off-Canvas Drawer*:
     ```css
     .rec-sidebar,
     .admin-sidebar {
       position: fixed;
       top: 0;
       left: 0;
       bottom: 0;
       width: 280px;
       max-width: 85vw;
       height: 100dvh;
       z-index: 200;
       flex-direction: column;
       overflow-y: auto;
       transform: translateX(-100%);
       transition: transform 0.25s ease;
     }
     .rec-sidebar.is-open,
     .admin-sidebar.is-open {
       transform: translateX(0);
     }
     ```
   - Atur `.rec-main`, `.admin-workspace`, dan `.admin-main` agar memiliki `margin-left: 0; width: 100%; padding: 16px;`.

### 4.3. Grid, Tabel, Modal, & Grup Tombol Responsif (`style.css`, `recruiter.css`, `admin.css`)
1. **Grid Kartu Responsif:**
   - Pada `@media (max-width: 1023px)`: `.grid-3`, `.grid-4`, `.career-grid`, `.admin-stats-grid` menjadi `grid-template-columns: repeat(2, minmax(0, 1fr));`.
   - Pada `@media (max-width: 767px)`: `.grid-2`, `.grid-3`, `.grid-4`, `.grid-cols-2`, `.career-grid`, `.dashboard-grid`, `.admin-stats-grid` wajib menjadi 1 kolom (`grid-template-columns: minmax(0, 1fr); gap: 16px;`).
2. **Tabel Responsif:**
   - Bungkus setiap `<table class="table">` dengan `<div class="table-responsive">` (`width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;`).
   - Bungkus grup tombol di dalam sel `<td>` dengan `<div class="table-actions">` (`display: inline-flex; align-items: center; gap: 6px; flex-wrap: nowrap; white-space: nowrap;`).
3. **Grup Tombol di Header, Card, & Modal:**
   - `.page-header-actions`, `.card-actions`, dan `.filter-bar` wajib memakai `display: flex; flex-wrap: wrap; align-items: center; gap: 10px;`.
   - Pada `@media (max-width: 639px)`, `.modal-footer` dan `.form-actions` wajib memakai `flex-direction: column-reverse; align-items: stretch; gap: 8px;` dengan `.btn { width: 100%; min-height: 44px; }`.

## 5. Urutan Langkah Kerja untuk AI

1. **Langkah 1 — Perbaikan Navigasi & Redirect JS:** Perbaiki `public/js/pages/login.js`, `public/js/ui.js`, `public/js/landing.js`, dan link `base_url('logout.php')` di `includes/layouts/*.php`.
2. **Langkah 2 — Upgrade CSS Button & Responsive Breakpoints:** Perbarui `public/css/style.css`, `public/css/recruiter.css`, dan `public/css/admin.css` sesuai spesifikasi Bagian 3 dan Bagian 4.
3. **Langkah 3 — Tambah Toggle Mobile di Layout PHP:** Tambahkan `.navbar-toggle` di `public_header.php` & `mahasiswa_header.php`, serta `.sidebar-toggle` + `.sidebar-backdrop` di `hr_header.php`, `hr_sidebar.php`, `admin_header.php`, & `admin_sidebar.php`, lalu pasang event listener-nya di `public/js/ui.js`.
4. **Langkah 4 — Audit `<button>` & `.table-responsive` di 23 Halaman PHP + JS:** Pastikan seluruh `<button>` memiliki `type="button"` / `type="submit"`, seluruh `<table>` dibungkus `.table-responsive`, dan seluruh tombol aksi di tabel dibungkus `.table-actions`.

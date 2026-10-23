# PRD Addendum: Spesifikasi Desain Responsif & Standarisasi Button SkillBridge

**Dokumen:** Spesifikasi Teknis UI/UX Responsif & Sistem Komponen Tombol (Button System)
**Target Repositori:** [HaikalMaghsin/SkillBridge](https://www.google.com/search?q=https%3A%2F%2Fgithub.com%2FHaikalMaghsin%2FSkillBridge) (Pasca-Migrasi PHP Multi-Role)
**Cakupan:** Seluruh 23 Halaman PHP (`public/`, `public/mahasiswa/`, `public/hr/`, `public/admin/`), 12 Layout Partials (`includes/layouts/`), 3 Stylesheet (`style.css`, `recruiter.css`, `admin.css`), dan Script Frontend (`public/js/`).

## 1. Latar Belakang & Masalah yang Diselesaikan

Setelah migrasi dari HTML statis ke arsitektur PHP Multi-Role (`commit 5ebcbd5`), diperlukan penyempurnaan pada dua aspek utama antarmuka pengguna:

1. **Responsivitas Multi-Perangkat (Mobile, Tablet, Desktop):**
   - Navbar atas pada halaman Publik dan Mahasiswa masih terkunci pada tinggi tetap (`height: 88px`) tanpa menu *hamburger*, sehingga item navigasi dan tombol profil berhimpitan pada layar di bawah `768px`.
   - Sidebar pada konsol HR (`.rec-sidebar`) dan Admin (`.admin-sidebar`) berubah menjadi bilah horizontal sempit di bagian atas layar pada perangkat mobile, menyulitkan perpindahan antar-modul.
   - Grid kartu (`.grid-2`, `.grid-3`, `.career-grid`) dan tabel data (`.table`) belum seluruhnya menyesuaikan jumlah kolom maupun scroll horizontal secara mandiri pada layar smartphone.
2. **Standarisasi & Koreksi Fungsionalitas Tombol (Button System):**
   - Beberapa rute tombol dan pengalihan halaman (*redirect*) pasca-konversi PHP masih merujuk ke path lama (seperti redirect `login.js` ke `"dashboard.php"` alih-alih `"mahasiswa/dashboard.php"`, serta penggunaan `$path_prefix` pada tombol Logout).
   - Varian class CSS tombol di `public/css/style.css` (seperti `.btn-outline`, `.btn-secondary`, `.btn-danger`, `.btn-ghost`) belum terdefinisi lengkap untuk mendukung kebutuhan konsol HR dan Admin.
   - Sejumlah elemen `<button>` di dalam form dan modal belum memiliki atribut `type="button"`, sehingga memicu pengiriman form (*page reload*) yang tidak disengaja saat diklik.

## 2. Spesifikasi Breakpoint & Tata Letak Responsif

| Kategori Perangkat | Rentang Lebar Layar | Perilaku Navbar (Publik & Mahasiswa) | Perilaku Sidebar (HR & Admin) | Perilaku Grid Kartu | Perilaku Grup Tombol Modal/Form |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Mobile Kecil** | `< 640px` | Topbar `64px` + Hamburger Menu (`.navbar-toggle`), menu & tombol aksi 1 kolom penuh saat dibuka | Off-canvas slide-over drawer (`280px` / `85vw`) + overlay backdrop gelap | 1 kolom penuh (`1fr`), jarak antar-kartu `16px` | Menumpuk vertikal (`column-reverse`), lebar tombol `100%`, tinggi minimal `44px` |
| **Mobile / Phablet** | `640px – 767px` | Topbar `64px` + Hamburger Menu (`.navbar-toggle`), dropdown vertikal | Off-canvas slide-over drawer (`280px`) + overlay backdrop gelap | 1 kolom penuh (`1fr`) | Sejajar kanan (`flex-end`) dengan `flex-wrap: wrap` |
| **Tablet** | `768px – 1023px` | Topbar horizontal penuh dengan jarak item adaptif | Off-canvas slide-over drawer (`280px`) agar area tabel utama tetap luas (`margin-left: 0`) | Maksimal 2 kolom (`repeat(2, minmax(0, 1fr))`) | Sejajar kanan (`flex-end`) |
| **Desktop** | `>= 1024px` | Topbar horizontal penuh (`height: 88px`, `padding: 20px 32px`) | Sidebar tetap (*fixed*) di kiri selebar `256px`, konten utama `margin-left: 256px` | 2 hingga 4 kolom penuh sesuai desain halaman | Sejajar kanan (`flex-end`) |

## 3. Spesifikasi Komponen Navigasi Mobile

### 3.1. Navbar Publik (`public_header.php`) & Mahasiswa (`mahasiswa_header.php`)
- Menyediakan tombol `.navbar-toggle` (ikon 3 garis horizontal yang beranimasi menjadi ikon silang `X` saat `.navbar.is-open` aktif).
- Dilengkapi atribut aksesibilitas `aria-label="Buka menu navigasi"` dan `aria-expanded="false|true"`.
- Menekan tombol `Escape` atau mengklik di luar area navbar pada layar mobile otomatis menutup menu yang sedang terbuka.

### 3.2. Sidebar HR (`hr_sidebar.php`) & Admin (`admin_sidebar.php`)
- Menyediakan tombol `.sidebar-toggle` di sisi kiri `.rec-header` dan `.admin-header` pada layar `< 1024px`.
- Saat dibuka, sidebar bergeser masuk dari kiri (`transform: translateX(0)`) di atas konten utama dengan latar belakang redup (`.sidebar-backdrop`).
- Mengklik `.sidebar-backdrop` atau menekan tombol `Escape` otomatis menutup drawer sidebar.

## 4. Spesifikasi Sistem Tombol (Button Design & Behavior System)

### 4.1. Hirarki Varian Visual Tombol
| Nama Class | Kegunaan Utama | Warna Latar (Default -> Hover) | Warna Teks & Border |
| :--- | :--- | :--- | :--- |
| `.btn-primary` | Aksi utama halaman / simpan form | Biru Navy `#1e3a8a` -> `#1e40af` | Teks putih `#ffffff`, tanpa border |
| `.btn-accent` | Call-to-Action (CTA) unggulan | Pink/Rose `#ec4899` -> `#db2777` | Teks putih `#ffffff`, tanpa border |
| `.btn-secondary` | Aksi sekunder / filter / batal | Abu-abu terang `#f1f5f9` -> `#e2e8f0` | Teks `#0f172a`, border `#cbd5e1` |
| `.btn-outline` | Tombol alternatif berbingkai | Transparan -> `#f8fafc` | Teks `#1e293b`, border `#cbd5e1` |
| `.btn-danger` | Hapus item / tolak kandidat / keluar | Merah lembut `#fee2e2` -> `#ef4444` | Teks `#b91c1c` -> `#ffffff`, border `#fecaca` |
| `.btn-ghost` | Tombol ikon di header / tutup modal | Transparan -> `rgba(15, 23, 42, 0.06)` | Teks mengikuti induk, tanpa border |

### 4.2. Standar Dimensi & Penataan Tombol
1. **Ukuran Standar (`.btn`)**: Tinggi minimal `40px` (`44px` di layar sentuh mobile), padding horizontal `18px`, radius sudut `10px`, ukuran font `14px` (`font-weight: 600`), dan teks satu baris (`white-space: nowrap`).
2. **Ukuran Kecil (`.btn-sm`)**: Tinggi minimal `34px`, padding `6px 12px`, ukuran font `13px`, radius sudut `8px` — digunakan untuk tombol aksi di dalam baris tabel dan kartu kompak.
3. **Ukuran Ikon (`.btn-icon`)**: Dimensi persegi `40px × 40px` (`padding: 0`), wajib memiliki atribut `aria-label`.
4. **Penataan di Dalam Tabel (`.table-actions`)**: Seluruh tombol aksi pada kolom terakhir tabel HR dan Admin wajib berada di dalam `<div class="table-actions">` dengan `display: inline-flex; gap: 6px; flex-wrap: nowrap;` agar tidak pernah terpecah menjadi dua baris.

### 4.3. Kontrak Rute & Aksi Tombol Pasca-Migrasi PHP
1. **Tombol Login (`public/login.php` & `public/js/pages/login.js`)**: Mengarahkan pengguna secara akurat menggunakan `window.APP_BASE_URL` ke `/mahasiswa/dashboard.php`, `/hr/index.php`, atau `/admin/index.php` berdasarkan role akun.
2. **Tombol Logout (`includes/layouts/*.php`)**: Selalu mengarah ke `<?= e(base_url('logout.php')) ?>` dari seluruh konsol (`mahasiswa`, `hr`, maupun `admin`).
3. **Tombol Non-Submit**: Seluruh `<button>` untuk membuka/menutup modal, memfilter daftar, berpindah tab, atau memicu aksi CRUD via Fetch/localStorage wajib menggunakan `type="button"`.

## 5. Kriteria Penerimaan (Acceptance Criteria / QA Checklist)

1. **Uji Resolusi 375px (iPhone SE / Mobile) & 768px (iPad / Tablet) di DevTools:**
   - Tidak ada *horizontal scrollbar* di level `<body>` pada seluruh 23 halaman (semua tabel terbungkus `.table-responsive`).
   - Tombol hamburger di navbar Publik & Mahasiswa serta tombol drawer sidebar di konsol HR & Admin berfungsi membuka dan menutup menu dengan mulus.
2. **Uji Klik Seluruh Tombol:**
   - Tidak ada tombol yang menghasilkan *404 Not Found* (termasuk saat login, logout, dan berpindah menu di dalam subfolder `mahasiswa/`, `hr/`, dan `admin/`).
   - Klik tombol "Batal" atau "Tutup" di dalam modal tidak me-reload halaman.
   - Seluruh tombol di konsol HR dan Admin memiliki warna, border, dan efek hover yang jelas sesuai varian `.btn-*`.

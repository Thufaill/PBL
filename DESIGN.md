# DESIGN.md — SkillBridge Architectural Modernist System

Dokumen ini adalah spesifikasi desain (*Single Source of Truth*) untuk sistem antarmuka **SkillBridge**, mengadopsi arketipe **The Architectural Modernist** (Linear / Stripe / Vercel style) dari metodologi **Impeccable**.

---

## 1. Persona & Karakter Visual

- **Tone of Voice**: Percaya diri, presisi, intelektual, berwibawa, dan tenang (*calm precision*).
- **Inspirasi**: Linear.app, Vercel Dashboard, Stripe Docs & Dashboard.
- **Filosofi**: Setiap piksel, margin, dan border memiliki tujuan fungsional. Eliminasi seluruh dekorasi semu ("AI slop", gradient ungu-pink acak, border ganda yang bertumpuk).

---

## 2. Palet Token Warna (:root)

### 2.1 Netral (Cool Architectural Slate)
- `--bg-canvas`: `#f8fafc` (Slate 50 — canvas latar sejuk, anti-silau)
- `--surface`: `#ffffff` (Pure White untuk elevated cards & data surfaces)
- `--surface-subtle`: `#f1f5f9` (Slate 100 — inset cards, input wells, hover rows)
- `--surface-elevated`: `#ffffff`
- `--text-primary`: `#090d16` (Deep Obsidian Slate — kontras tajam, rasio > 14:1)
- `--text-secondary`: `#334155` (Slate 700 — deskripsi dan sub-heading)
- `--text-muted`: `#475569` (Slate 600 — lolos WCAG AA 5.5:1 pada latar putih)
- `--text-light`: `#64748b` (Slate 500 — metadata, tanggal, label mikro)
- `--text-inverse`: `#ffffff`

### 2.2 Border & Hairline Rules
- `--border`: `#e2e8f0` (Slate 200 — hairline 1px tegas dan bersih)
- `--border-subtle`: `#f1f5f9`
- `--border-hover`: `#cbd5e1` (Slate 300)
- `--border-focus`: `#2563eb` (Cobalt 600)

### 2.3 Brand & Semantik Fungsional
- `--primary`: `#2563eb` (Royal Cobalt 600)
- `--primary-hover`: `#1d4ed8` (Cobalt 700)
- `--primary-dark`: `#0f172a` (Slate 900)
- `--primary-subtle`: `#eff6ff` (Cobalt 50)
- `--accent`: `#e11d48` (Rose 600 — aksen aksi sekunder; kontras teks putih ±4,9:1, lolos WCAG AA)
- `--accent-hover`: `#be123c` (Rose 700)
- `--accent-sparkle`: `#f59e0b` (Warm Amber 500 — indikator progress & milestone)

### 2.4 Semantic Badges
- **Success / Verified / Strong**:
  - Background: `#ecfdf5` (Emerald 50)
  - Border: `#a7f3d0` (Emerald 200)
  - Text: `#047857` (Emerald 700)
- **Warning / Reviewing / Growing**:
  - Background: `#fffbeb` (Amber 50)
  - Border: `#fde68a` (Amber 200)
  - Text: `#b45309` (Amber 700)
- **Info / New / Catalog**:
  - Background: `#eff6ff` (Blue 50)
  - Border: `#bfdbfe` (Blue 200)
  - Text: `#1d4ed8` (Blue 700)
- **Danger / Error / Shortfall**:
  - Background: `#fef2f2` (Red 50)
  - Border: `#fecaca` (Red 200)
  - Text: `#b91c1c` (Red 700)

---

## 3. Tipografi & Skala Hirarki

- **Font Family**: `"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, system-ui, sans-serif;`
- **Typographic Scale** (direvisi 24 Okt 2026 — dikencangkan ±15–20% agar sesuai kepadatan konsol):
  - **Hero Display / H1**: `32px` (ponsel `28px`), `font-weight: 800`, `letter-spacing: -0.03em`, `line-height: 1.15`; landing memakai hero sendiri `46px` desktop / `30px` ponsel
  - **Section Title / H2**: `24px` (ponsel `22px`), `font-weight: 700`, `letter-spacing: -0.025em`, `line-height: 1.2`
  - **Card Heading / H3**: `16px`, `font-weight: 600`, `letter-spacing: -0.015em`, `line-height: 1.3`
  - **Body Text**: `14px` (`--font-base`), `font-weight: 400 / 500`, `line-height: 1.55`
  - **Eyebrow / Category Label**: `10.5px`, `font-weight: 700`, `letter-spacing: 0.1em`, `text-transform: uppercase`, `color: var(--primary)`
  - **Metadata / Tabular Figures**: `font-variant-numeric: tabular-nums;`, `font-size: 12.5px`
  - **Data Value**: `26–28px`, `font-weight: 800`, `letter-spacing: -0.02em`
  - Kontrol form: `38px` tinggi di desktop, `44px` di ponsel; input ponsel `16px` agar iOS tidak melakukan zoom otomatis.

---

## 4. Spacing, Elevasi & Sudut Geometris

### 4.1 Harmonic Radii
- `--radius-xs`: `4px` (Chips mikro, badge kecil)
- `--radius-sm`: `6px` (Tombol standar, input form, icon boxes)
- `--radius-md`: `10px` (Cards standar, dropdowns)
- `--radius-lg`: `14px` (Large cards, banner modals)
- `--radius-xl`: `18px` (Hero containers, large panels)
- `--radius-round`: `9999px` (Pills, user avatars)

### 4.2 Linear-Grade Elevation System
- `--shadow-xs`: `0 1px 2px 0 rgba(15, 23, 42, 0.04), 0 0 0 1px rgba(15, 23, 42, 0.03);`
- `--shadow-sm`: `0 2px 6px -1px rgba(15, 23, 42, 0.06), 0 0 0 1px rgba(15, 23, 42, 0.04);`
- `--shadow-md`: `0 8px 18px -3px rgba(15, 23, 42, 0.08), 0 2px 6px -2px rgba(15, 23, 42, 0.04);`
- `--shadow-lg`: `0 16px 32px -4px rgba(15, 23, 42, 0.10), 0 4px 8px -2px rgba(15, 23, 42, 0.04);`
- `--shadow-focus`: `0 0 0 3px rgba(37, 99, 235, 0.25);`
- `--shadow-btn`: `0 1px 2px rgba(0, 0, 0, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.15);`

### 4.3 Transitions & Dynamics
- `--ease-spring`: `cubic-bezier(0.4, 0, 0.2, 1)`
- Interactive buttons & cards: Hover terangkat `-2px` hingga `-3px`, active depression `scale(0.98)`

---

## 5. Perilaku Responsif

Satu sistem, dua wajah: desktop menampilkan kepadatan data, ponsel menampilkan keterbacaan.

### 5.1 Breakpoint
- **≥ 1400px (desktop lebar)**: tabel data native, sidebar tetap, topbar penuh.
- **1024–1399px (laptop/tablet lanskap)**: sidebar tetap, tetapi tabel data berubah menjadi kartu karena ruang konten di samping sidebar belum cukup untuk enam kolom.
- **768–1023px (tablet)**: sidebar menjadi off-canvas, tabel tetap kartu, toolbar masih satu baris.
- **< 768px (ponsel)**: seluruh konsol mobile — toolbar dan aksi memenuhi lebar, grid statistik dua kolom.
- Tabel data tidak pernah memaksa geser horizontal pada lebar < 1400px.
- `html` dan `body` memakai `overflow-x: clip` sebagai jaminan keras: pada rentang `320–1440px` tidak ada halaman yang boleh menggulir horizontal. `clip` dipilih (bukan `hidden`) agar tidak membuat scroll container baru sehingga header sticky tetap bekerja.

### 5.2 Navigasi & Topbar
- Header publik dan mahasiswa memakai navigasi satu baris pada `≥1024px`; pada `1024–1279px` padding bar, jarak antar item, dan gap aksi dirapatkan agar CTA tidak menempel tepi viewport.
- Di bawah `1024px`, seluruh tautan navigasi, aksi akun, dan profil masuk ke drawer yang dibuka tombol menu `44×44` di kanan; tidak ada menu yang terpotong atau perlu digeser horizontal.
- Header admin: tinggi `64px`, judul workspace dipotong dengan ellipsis, pill role disembunyikan agar judul dan avatar tidak berdesakan.
- Header mahasiswa: logo + nama platform di kiri, tombol menu `44×44` di kanan.
- Profil mahasiswa di drawer: nama + avatar menjadi satu tautan `44px`; logout dipisah sebagai ikon `44×44`, bukan teks yang menempel pada nama.
- Semua tautan/aksi navigasi memakai target sentuh minimal `44×44px`.

### 5.3 Tabel Data → Kartu Bertumpuk
- Di bawah `1400px`, setiap `<tr>` menjadi kartu bertumpuk: latar putih, border `1px`, radius `--radius-lg`, padding `16px`. Isi kartu memakai grid dua kolom; sel identitas dan aksi melebar penuh.
- `<thead>` disembunyikan secara visual (tetap ada untuk pembaca layar). Label kolom ditempel ke setiap sel melalui `data-label` dan `td::before`; label dihasilkan otomatis oleh `labelTable()` di `public/js/ui.js` dari teks `<th>`, termasuk untuk baris yang dirender ulang lewat `MutationObserver`.
- Sel identitas menjadi header kartu (lebar penuh, hairline bawah); sel aksi menjadi footer kartu (lebar penuh, hairline atas, tombol minimal `44px`).
- Baris kosong (`colspan`) melebar penuh tanpa label.
- Wadah kartu luar (`.admin-card`) diratakan di ponsel agar tidak terjadi kartu di dalam kartu.
- Tabel `<div>` dasbor admin mengikuti pola yang sama: header kolom disembunyikan, nilai diberi label statis atau baris meta dua kolom.
- **Tidak boleh ada geser horizontal** untuk membaca data di ponsel.

### 5.4 Grid, Toolbar, dan Pager
- Grid statistik admin: dua kolom di ponsel; kartu terakhir yang jumlahnya ganjil melebar penuh agar tidak menjadi kartu yatim.
- Toolbar: input pencarian dan select memenuhi lebar; tinggi kontrol `44px`; tombol aksi memenuhi lebar.

### 5.5 Empty State & Isi Sel
- Daftar kosong selalu punya pesan eksplisit (mis. "Belum ada target role."), bukan ruang kosong.
- Sel skill yang tidak punya isi menampilkan "Tidak ada" dengan warna `--text-muted`, bukan area kosong tanpa keterangan.

### 5.6 People Search & Public Profile
- Form pencarian profil publik berada di navbar mahasiswa (drawer di bawah `1024px`) dan header admin (disembunyikan di bawah `1024px`; admin memakai tautan sidebar "Cari Profil").
- Halaman `/mahasiswa/search.php` dan `/admin/search.php` memakai panel pencarian yang sama: input `44px`, status `aria-live`, dan grid kartu `minmax(260px, 1fr)` yang menjadi satu kolom di ponsel.
- Hasil dibatasi maksimal 30 profil per pencarian; bila terpotong, tampilkan catatan untuk mempersempit kata kunci.
- Kartu orang memuat avatar atau inisial, nama, institusi/program/semester, lokasi, target karier, maksimal 4 skill + `+N`, dan jumlah evidence. Seluruh teks dirender dengan `textContent`.
- Profil publik dibuka lewat `/profile.php?token=<uuid>` tanpa login: hero (avatar, nama, target karier, badge "Profil Publik"), bio, detail, dan skill per kategori. Halaman memakai `noindex, nofollow` serta `referrer: no-referrer`.
- Token yang tidak valid, profil Private, atau akun nonaktif menghasilkan pesan "Profil tidak tersedia" yang sama (tanpa membocorkan status akun).

---

## 6. Aksesibilitas & Kualitas

- **Kontras**: teks putih di atas `--accent` wajib memakai Rose 600 ke atas; Rose 500 gagal AA (3,67:1) dan tidak dipakai untuk tombol berteks putih.
- **Tombol nonaktif**: memakai latar `--surface-subtle`, border `--border`, teks `--text-light`, tanpa shadow dan tanpa opacity ganda — tetap terbaca, jelas tidak dapat ditekan.
- **Target sentuh**: minimal `44×44px` untuk seluruh tombol, tautan navigasi, dan kontrol pager di ponsel.
- **Fokus**: outline `2px` `--primary` dengan offset `2px` pada elemen interaktif.
- **Label tabel**: label seluler berasal dari `<th>` asli, sehingga struktur tabel tetap tersedia bagi pembaca layar (thead hanya di-clip, tidak dihapus).
- **Ikon**: `favicon.svg` memakai mark "S" putih di kotak cobalt `--primary`; seluruh halaman memuatnya lewat `head.php` sehingga tidak ada permintaan favicon 404.

---

## 7. Aturan Copy

- **Bahasa**: konsol mahasiswa berbahasa Inggris; halaman publik dan admin berbahasa Indonesia.
- **Pluralisasi bahasa Inggris**: hitungan `1` selalu tunggal — `1 skill`, `1 item`, `1 evidence item`; bukan `1 skills` / `1 items`.
- **Bahasa Indonesia**: tidak memakai bentuk jamak berimbuhan; `1 item`, `2 item`, `1 kandidat`, `3 kandidat`.
- **Tanpa nilai kosong**: jangan pernah menampilkan `null`, `undefined`, atau pemisah menggantung (` · `). Gabungkan bagian dengan filter nilai kosong lebih dulu, mis. `[name, tagline].filter(Boolean).join(" · ")`.
- **Hitungan dan tata bahasa harus sepakat**: `1 of your 1 skill has…`, `2 of your 3 skills have…`.
- **Aksi nonaktif**: tombol yang belum bisa dipakai (mis. "Simpan Mapping" tanpa role terpilih) dinonaktifkan, bukan hanya menampilkan pesan setelah diklik.

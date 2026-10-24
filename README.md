# SkillBridge

Platform web pemetaan skill dan skill gap terhadap target karier. PHP 8 native + PostgreSQL, tanpa framework.

## Struktur

```text
includes/   config (app, database), auth (session, csrf, middleware), api/bootstrap.php, layouts
public/     document root: halaman per role (mahasiswa/, admin/), api/ (endpoint JSON), css/, js/, assets/
db/         migrate.sql (aditif kecuali 007), schema.sql (destruktif, DB kosong), seed_demo.sql (data demo, opsional)
tools/      create_admin.php, check_schema_parity.php
docker/     entrypoint.sh
storage/    foto profil avatar_<id>.<ext> — di luar document root, tidak boleh publik
```

## Menjalankan di lokal

1. Buat database, lalu muat skema.

   Database kosong atau development:

   ```bash
   createdb skillbridge
   psql skillbridge -f db/schema.sql
   ```

   Database yang sudah berisi data (mis. Supabase produksi): jangan jalankan `db/schema.sql` — file itu
   membuka dengan `DROP TABLE ... CASCADE`. Jalankan `db/migrate.sql`; bagian 001–006 dan 008 aditif serta
   idempoten, tetapi bagian 007 **menghapus permanen** seluruh data modul HR. Cadangkan database
   (`pg_dump`) ke lokasi di luar repo sebelum menjalankannya.

   ```bash
   pg_dump <database> > backup_sebelum_007.sql
   psql <database> -f db/migrate.sql
   ```

2. `cp .env.example .env`, lalu sesuaikan `DB_*` (ekstensi PHP `pdo_pgsql` wajib aktif).

3. Opsional, muat data demo: `psql skillbridge -f db/seed_demo.sql`.

4. Buat akun pengelola:

   ```bash
   php tools/create_admin.php admin@contoh.id "Nama Admin"
   ```

5. Jalankan server (document root = `public/`):

   ```bash
   php -S localhost:8000 -t public
   ```

Mahasiswa mendaftar sendiri lewat `/register.php`. Untuk menguji tampilan tanpa database, set `APP_DEMO=1`
(membuka sesi demo mahasiswa/admin; **jangan** diaktifkan pada build yang dinilai atau di produksi).

## Role

| Di database (`user_role`) | Di aplikasi | Halaman |
|---|---|---|
| `user`  | `mahasiswa` | `public/mahasiswa/` |
| `admin` | `admin`     | `public/admin/` |

## Penyimpanan data

Semua data domain dan konsol disimpan di PostgreSQL dan dibaca/ditulis lewat endpoint JSON di `public/api/*.php`:

- Mahasiswa: skill yang dimiliki, bukti (evidence), target karier, skill gap, profil, visibilitas, foto, password.
- Admin: statistik, pengguna, administrator, katalog skill, target karier, relasi mapping, konten, pengaturan.
- People search: semua user login dapat mencari profil mahasiswa yang Public dan membuka profil publik lewat
  token UUID; email tidak pernah ditampilkan.

`public/js/api.js` adalah satu-satunya pemanggil endpoint tersebut. `localStorage` tidak dipakai lagi untuk
data aplikasi; `api.js` justru membersihkan kunci lama (`sb.*`) sekali saat halaman dimuat.
Angka pada dashboard mahasiswa dan admin dihitung lewat query, bukan ditulis di frontend.

Tulis memakai `POST` dengan header `X-HTTP-Method-Override: PATCH|DELETE` dan `X-CSRF-Token`
(token tersedia di `<meta name="csrf-token">` pada setiap halaman yang butuh login).

## Verifikasi skema

`db/schema.sql` dan `db/migrate.sql` harus menghasilkan struktur yang sama:

```bash
php tools/check_schema_parity.php <db_hasil_schema> <db_hasil_migrasi>
```

## Deploy

- Document root harus `public/`. `Dockerfile` sudah menyiapkannya (PHP 8.3 + Apache + `pdo_pgsql`).
- Isi environment variable `DB_*` dan `DB_SSLMODE` di hosting (lihat `.env.example`).
- Supabase: pakai **Session pooler** (port 5432, IPv4). Untuk Transaction pooler (6543) set `DB_EMULATE_PREPARES=1`.
- Setelah database terhubung: jalankan `db/migrate.sql` (lihat langkah 1), lalu `tools/create_admin.php`.
- `storage/` harus di luar document root dan bisa ditulis PHP. Pada hosting kontainer, mount folder ini sebagai
  volume atau pindahkan foto ke Supabase Storage; tanpa itu foto profil hilang setiap container dibangun ulang.

## Catatan status

Login, register, dan seluruh penyimpanan data domain sudah memakai database. Yang belum ada: fitur unggah dan
review CV, suite tes otomatis, CI, dan penhardening tambahan (rate limit, security header, CSRF pada alur non-API).
Kritik lengkap dan roadmap perbaikannya ada di `Laporan/next.md`.

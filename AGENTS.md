# AGENTS.md — Peta Operasional SkillBridge

**Untuk agent sesi berikutnya.** Dokumen ini dirancang supaya kamu tidak perlu membaca seluruh project dulu. Baca file ini, lalu langsung buka file yang dirujuk.

| | |
| --- | --- |
| **Peta ini menggambarkan** | working tree 24 Oktober 2026 — modul HR dihapus total, direktori profil publik dan pencarian ditambahkan (belum di-commit) |
| **Aturan keras** (larangan framework, keamanan, non-scope UI) | tetap di `CLAUDE.md` §2, §4.2, §5 |
| **Roadmap & kritik** | `Laporan/next.md` (ID temuan P0/P1/P2) |
| **Bahasa** | Jawab dan tulis UI copy dalam Bahasa Indonesia kecuali halaman konsol mahasiswa memakai copy berbahasa Inggris |

> **Sebelum mulai:** project ini sedang dikerjakan dengan intensitas tinggi (beberapa commit dalam satu hari, dan ada sesi agent yang berjalan paralel). Jalankan `git log --oneline -3` sebelum percaya butiran apa pun di sini, dan jangan menjalankan `git reset --hard`, `git checkout --`, `git clean`, atau `git stash` — pekerjaan yang belum di-commit bisa hilang bersamanya.

---

## 1. Model 30 Detik

SkillBridge = web app dua role (mahasiswa, admin) untuk memetakan keterampilan mahasiswa terhadap target karier, menampilkan *skill gap*, dan menyediakan direktori profil publik lewat token.

**Satu kalimat arsitektur:** halaman PHP merender + menjaga sesi → JavaScript memanggil endpoint JSON di `public/api/` → endpoint bicara ke PostgreSQL lewat PDO. **Database adalah satu-satunya sumber kebenaran.** Mock `public/data/*.json` dan penyimpanan domain di `localStorage` sudah dihapus.

```
Browser
  └─ GET /mahasiswa/skills.php
       └─ check_auth('mahasiswa')            includes/auth/auth.php:66
            └─ head.php → header → <main> → footer    includes/layouts/
                 └─ browser memuat js/ui.js, js/api.js, js/pages/skills.js
                      └─ API.ensureSession() → GET /api/session.php
                           └─ api_guard('mahasiswa') → PDO → v_user_skill_evidence, evidence, student_profiles
                      └─ API.getSkillCatalog() → GET /api/catalog.php (cache sekali per halaman)
                      └─ tulis: API.addSkill(id) → POST /api/skills.php
                           header X-CSRF-Token + (PATCH/DELETE = POST + X-HTTP-Method-Override)
```

---

## 2. Role, Auth, dan Guard

| Role di DB (`user_role`) | Role di aplikasi | Folder halaman | Guard | Redirect setelah login |
| --- | --- | --- | --- | --- |
| `user` | `mahasiswa` | `public/mahasiswa/` | `check_auth('mahasiswa')` | `mahasiswa/dashboard.php` |
| `admin` | `admin` | `public/admin/` | `check_auth('admin')` | `admin/index.php` |

**HR sudah dihapus total (keputusan 24 Oktober 2026).** Tabel, endpoint, halaman, akun, dan seluruh CSS/JS HR sudah tidak ada; jangan menambahkan kembali.

- Pemetaan `user ↔ mahasiswa`: `db_role_to_app_role()` / `app_role_to_db_role()` (`includes/auth/auth.php:19-25`).
- **Semua halaman memanggil `check_auth()`, bukan `require_role()`.** `includes/auth/middleware.php` ada tetapi tidak dipakai satu halaman pun — jangan ikuti `CLAUDE.md` §4.1 yang menormakan `require_role()`.
- Session menyimpan satu kunci `user`: `id, name, email, role, role_label, initials`.
- Login: prepared statement + `password_verify` terhadap dummy hash saat email tidak ada (mitigasi timing), lalu `session_regenerate_id(true)` (`includes/auth/auth.php:118-173`).
- Register (`public/register.php`): validasi server-side → transaction `INSERT users (role='user')` + `INSERT student_profiles` → auto-login. Menghormati `system_settings.registration_open` dan `default_visibility`.
- CSRF: token 32 byte di `<meta name="csrf-token">` (`includes/layouts/head.php:24`), diverifikasi `hash_equals` dari `$_POST['csrf_token']` atau header `X-CSRF-Token` (`includes/auth/csrf.php`).
- **`APP_DEMO=1` itu bypass total.** `check_auth()` (`auth.php:79-91`) dan `api_guard()` (`includes/api/bootstrap.php:68-82`) membuat sesi demo otomatis dengan `user_id` keras 1 (mahasiswa) dan 3 (admin) — dan id itu bertabrakan dengan baris asli hasil `db/seed_demo.sql`, jadi mode demo dengan DB nyata bisa **membaca dan menulis data akun sungguhan**. Panel demo di login sudah dibungkus `if (demo_mode())` (`public/login.php:78-94`), kredensial demo hanya aktif bila koneksi DB gagal (`auth.php:156-170`). Jangan pernah menyalakan flag ini di build yang dinilai.
- Jangan pernah menampilkan isi `DB_*` dari `.env` di jawaban, laporan, atau commit.

---

## 3. Halaman → Skrip → Endpoint

Halaman publik: `public/index.php` (landing), `login.php`, `register.php`, `logout.php` (handler), `profile.php` (profil publik via token, tanpa login).

**Mahasiswa** (`$nav_active` + `data-nav`, skrip ditulis manual di ujung file):

| Halaman | Page JS | Endpoint yang dipakai |
| --- | --- | --- |
| `dashboard.php` | `pages/dashboard.js` | `session`, `catalog` |
| `skills.php` | `pages/skills.js` | `session`, `catalog`, `target`, `skills` |
| `evidence.php` | `pages/evidence.js` | `session`, `catalog`, `evidence` |
| `career.php` | `pages/career.js` | `session`, `catalog`, `target` |
| `profile.php` | `pages/profile.js` | `session`, `profile`, `password`, `profile-photo`, `catalog`, `target` |
| `search.php` | `pages/search.js` | `search` |

**Admin** (`$admin_page` → `data-admin-page`, `$page_script` dimuat `admin_footer.php:17`):

| Halaman | Page JS | Endpoint |
| --- | --- | --- |
| `index.php` | `admin.js` | `admin/stats` |
| `users.php` | `admin-users.js` | `admin/users` |
| `admins.php` | `admin-admins.js` | `admin/admins` |
| `skills.php` | `admin-skills.js` | `admin/skills` |
| `careers.php` | `admin-careers.js` | `admin/careers` |
| `mapping.php` | `admin-mapping.js` | `admin/mapping`, `admin/skills`, `admin/careers` |
| `settings.php` | `admin-settings.js` | `account`, `admin/settings`, `password` |
| `search.php` | `search.js` | `search` |

`public/profile.php` memakai `pages/public-profile.js` dan endpoint `public-profile` / `public-photo`; halaman ini tidak memakai layout mahasiswa/admin, hanya `public_header.php` + `public_footer.php`.

**HR sudah dihapus total** pada 24 Oktober 2026 (tabel, endpoint, halaman, layout, CSS, dan JS). Jangan menambahkan kembali.

`login.js` dan `register.js` tidak memanggil `API.*` — keduanya form HTML biasa.

---

## 4. Inventaris Endpoint (`public/api/`, 20 file)

Setiap endpoint hanya `require_once` `includes/api/bootstrap.php`. Guard: **G** = `api_guard(role)`, **WG** = `api_write_guard(role)` = guard + CSRF. Semua cabang tulis (POST/PATCH/DELETE) memakai WG — terverifikasi: 27 cabang `case 'POST'|'PATCH'|'DELETE'` + 1 `api_write_guard()` di awal `password.php` = 28 pemanggilan WG.

### 4.1 Mahasiswa & bersama

| Endpoint | Method | Guard | Tabel/view | Key respons |
| --- | --- | --- | --- | --- |
| `session.php` | GET | G `mahasiswa` | `student_profiles`, `v_user_skill_evidence`, `evidence`+`evidence_skills` | `user, target_career_id, skills, evidence, profile` |
| `catalog.php` | GET | G (role apa pun) | `careers`, `career_skill_requirements`, `skills` | `careers, skills, categories` |
| `skills.php` | GET POST PATCH DELETE | G / WG `mahasiswa` | `user_skills`, `skills`, `evidence_skills` | GET `skills`; POST 201 `skill, skills`; PATCH/DELETE `skills` |
| `target.php` | GET POST | G / WG `mahasiswa` | `student_profiles`, `careers`, `career_skill_requirements` | `target_career_id, career` |
| `evidence.php` | GET POST DELETE | G / WG `mahasiswa` | `evidence`, `evidence_skills`, `skills`, `system_settings` (batas `max_evidence`) | GET `evidence`; POST 201 `id, evidence`; DELETE `evidence` |
| `profile.php` | GET PATCH | G / WG `mahasiswa` | `users`, `student_profiles`, view | `name, email, institution, program, semester, location, bio, photo, member_since, is_public, visibility, public_token, target_career_id` (+`skills, evidence` di GET) |
| `profile-photo.php` | GET POST DELETE | G (GET, cek id milik sendiri 403) / WG `mahasiswa` | `student_profiles.photo_path` + file di `storage/` | POST 201 `photo`; DELETE `photo:null`; **GET mengembalikan byte gambar, bukan JSON** |
| `password.php` | POST | WG (role apa pun) | `users.password_hash` | `ok` |
| `account.php` | GET PATCH | G / WG (role apa pun) | `users` | `id, name, email, role, status, role_label, initials` |
| `search.php` | GET | G (role apa pun) | `v_public_profiles`, `user_skills`, `skills`, `evidence` | `ok, query, total, truncated, profiles[]` (maks 30) |
| `public-profile.php` | GET | tanpa guard | `v_public_profiles`, `v_user_skill_evidence`, `evidence` | `profile` (tanpa email/id/token/status) |
| `public-photo.php` | GET | tanpa guard | `v_public_profiles` + file `storage/` | byte gambar, bukan JSON |

### 4.2 Admin (`api_guard`/`api_write_guard` role `admin`)

| Endpoint | Method | Tabel | Key respons |
| --- | --- | --- | --- |
| `admin/stats.php` | GET | `users`, `skills`, `careers`, `career_skill_requirements`, `content_items` | 9 counter + `recent[]`, `accounts[]` |
| `admin/users.php` | GET PATCH DELETE | `users`, `student_profiles`, `careers`, `user_skills`, `evidence` | `users` / `id, status` / `id` |
| `admin/admins.php` | GET PATCH DELETE | `users` (`role='admin'`) | `admins` (role di-rewrite `skillbridge_admin`) |
| `admin/skills.php` | GET POST PATCH DELETE | `skills`, `user_skills`, `career_skill_requirements` | `skills, categories`; DELETE juga `users_unlinked, careers_unlinked` |
| `admin/careers.php` | GET POST PATCH DELETE | `careers`, `career_skill_requirements`, `student_profiles` | `careers`; DELETE juga `relations_removed, profiles_reset` |
| `admin/mapping.php` | GET POST PATCH DELETE | `career_skill_requirements`, `skills`, `careers` | `mapping` |
| `admin/content.php` | GET POST PATCH DELETE | `content_items` | `content, scoped_to_published` — **GET sengaja tanpa guard** (lihat §8) |
| `admin/settings.php` | GET PATCH | `system_settings` | `settings` / `saved, ignored` |

---

## 5. Kontrak `includes/api/bootstrap.php` (WAJIB diikuti saat menambah endpoint)

Empat invariant di header file (`:6-16`):

1. Data domain **selalu** difilter `user_id` dari **sesi**, tidak pernah dari body.
2. Semua operasi tulis wajib lolos `csrf_verify()`.
3. Respons **selalu JSON, tidak pernah redirect** — sesi hilang = 401 (bukan lemparan ke `login.php`).
4. Prepared statement mutlak; tidak ada interpolasi nilai ke SQL.

Helper yang tersedia:

| Helper | Perilaku penting |
| --- | --- |
| `api_out` / `api_error` | selalu menyertakan `Cache-Control: no-store` + `X-Content-Type-Options: nosniff`; error = `{ok:false, error}` |
| `api_pdo` | DB mati → 503 "Database tidak tersedia." |
| `api_method` | `X-HTTP-Method-Override` **hanya** dihormati pada request POST, hanya untuk PATCH/PUT/DELETE |
| `api_guard` / `api_write_guard` | 401 tanpa sesi, 403 role salah (+403 CSRF); role di luar `mahasiswa/admin` ditolak dan sesinya di-logout |
| `api_body` | JSON → array; body kosong di-fallback ke `$_POST` (dibutuhkan unggahan multipart) |
| `api_str` / `api_int` / `api_int_list` | validasi + pemangkasan; nilai terlalu panjang → **422**, bukan 400 |
| `api_photo_url` | memotong segmen `/api` (atau `/api/admin`) dari `BASE_URL` karena `BASE_URL` dihitung dari `SCRIPT_NAME` endpoint; mengembalikan URL, **bukan** path file |
| `api_photo_path` | path absolut `storage/avatar_<uid>.<png\|jpg\|webp>`; satu-satunya penentu path dari id sesi/token, tidak pernah dari input klien |
| `api_public_profile_url` | URL `/profile.php?token=<uuid>` |
| `api_public_photo_url` | URL `/api/public-photo.php?token=<uuid>` |
| `api_user_skills` | baca `v_user_skill_evidence`; menempelkan `label` = `Strong` bila evidence ≥ 2, selain itu `Growing` |
| `api_fetch_evidence` | JSONB kosong jadi `new stdClass()` supaya keluar `{}` bukan `[]`; daftar `IN (...)` memakai placeholder posisional (`bindValue($i+1, …, PARAM_INT)`) |
| `api_setting` | decode JSONB dari `system_settings` |

Konvensi lain yang harus ditiru: `switch (api_method())` dengan `default: api_error('Method tidak didukung.', 405)`; boolean di-`bindValue(..., PDO::PARAM_BOOL)` (lewat array `execute()` nilai `false` jadi string kosong dan ditolak Postgres); klausa `SET` dibangun dari **whitelist kolom tetap**, bukan dari key request; setiap endpoint membungkus `try/catch (PDOException)` → 500 generik + `error_log()` detail, dan rollback bila `inTransaction()`. Satu placeholder **tidak bisa dipakai ulang** di prepared native — bind nilai sama sebagai `:c1 … :c6`.

---

## 6. Kontrak Gateway Klien (`public/js/api.js`)

- **Satu pintu data.** Halaman tidak boleh `fetch()` sendiri; semuanya lewat `API.*`.
- `request(method, path, payload)` (`:56-94`): tulis selalu dikirim sebagai POST, method asli lewat header `X-HTTP-Method-Override`, dan **selalu** menyertakan `X-CSRF-Token` dari meta tag.
- **Snapshot sesi.** `ensureSession()` memuat `/api/session.php` sekali; setelah itu getter mahasiswa **sinkron**: `getSession()`, `getMySkills()`, `getEvidence()`, `getTargetId()`. Setiap respons tulis otomatis menempel ke snapshot lewat `applyWriteSnapshot()` (`:100-105`), jadi halaman tidak perlu reload.
- **Katalog.** `ensureCatalog()` memuat `/api/catalog.php` sekali per halaman (`careers`, `skills`, `categories`).
- `ApiError` dipakai untuk status non-2xx, `ok:false`, JSON tidak valid, dan kegagalan jaringan.
- `purgeLegacy()` menghapus kunci `sb.*` warisan mode dummy (termasuk `sb.rec.*`, yang kini hanya bertahan sebagai cleanup data lama). Sengaja: state klien boleh dibuang.
- `searchProfiles(query)` → GET `/api/search.php?q=`; `getPublicProfile(token)` → GET `/api/public-profile.php?token=`. Keduanya dipakai `pages/search.js` dan `pages/public-profile.js`.
- Diekspor tetapi belum dipakai halaman mana pun (tersedia untuk fitur berikutnya): `removeSkill`, `setSkillStatus`, `getSession`, `refreshSession`, `ensureCatalog`, `getAdminContent`, `addContent`, `updateContent`. `createEvidence` adalah alias eksplisit `addEvidence`.

---

## 7. Model Data

**Posisi penting:** skema dibangun dari `db/schema.sql`; yang menjadi kontrak stabil adalah **bentuk respons endpoint di §4**, bukan nama kolom hari ini.

### 7.1 `db/schema.sql` — 11 tabel

| Kelompok | Tabel |
| --- | --- |
| Master | `careers`, `skills`, `career_skill_requirements` |
| Akun | `users` (+`status`), `student_profiles` |
| Domain mahasiswa | `user_skills`, `evidence`, `evidence_skills` |
| Platform | `system_settings`, `content_items` |
| Meta | `schema_migrations` |

- `users.role` enum `('user','admin')`, default `'user'`. Nilai `hr` sudah dihapus lewat migrasi 007.
- `evidence_source_type` = `cv, github, project, portfolio, certificate, assessment`; `skill_source` = `manual, cv_extracted`; `skill_status` = `candidate, confirmed`.
- Status lain berupa `VARCHAR` + constraint CHECK bernama (`chk_users_status`, `chk_content_status`) — bukan enum.
- `student_profiles`: `user_id` PRIMARY KEY, `target_career_id`, kolom profil (`institution, program, semester, location, bio, photo_path`), `is_public` + `public_token` UUID dengan `chk_public_needs_token`.
- Fungsi `set_updated_at()` + trigger di beberapa tabel.
- Seed inline (idempoten, `ON CONFLICT DO NOTHING`): **29 skill**, **4 karier**, **27 relasi karier–skill**, 4 `system_settings` (`site_name`, `max_evidence`=12, `default_visibility`, `registration_open`), dan **9 baris** `schema_migrations` (`000_migrations`, `schema.sql`, `001`, `002`, `004`, `005`, `006`, `007_remove_hr_module`, `008_public_profile_directory`).

### 7.2 Dua view

| View | Kegunaan |
| --- | --- |
| `v_user_skill_evidence` | skill milik user + `evidence_count` yang **hanya menghitung evidence milik user itu** |
| `v_public_profiles` | mahasiswa `role='user'`, `status='active'`, `is_public=TRUE`; memuat `public_token`, nama, karier, profil, dan `member_since` — **tanpa email** |

### 7.3 `db/migrate.sql` & `db/seed_demo.sql`

- `migrate.sql` = jalur aditif & idempoten untuk 001–006 dan 008: domain mahasiswa, settings/content, katalog, nilai enum `github`/`assessment` (sengaja **di luar transaction** — Postgres melarang memakai nilai enum baru di transaction yang sama saat menambahnya), `created_at` di `skills`/`careers`, lalu view `v_public_profiles`.
- **Bagian 007 bersifat destruktif:** drop `v_public_candidates`, drop enam tabel HR (`activity_log`, `candidate_reviews`, `vacancy_skills`, `vacancies`, `company_recruiters`, `companies`), hapus `users` dengan `role='hr'`, lalu bangun ulang `user_role` menjadi `('user','admin')` di dalam blok `DO`. **Cadangkan DB dengan `pg_dump` ke luar repo sebelum menjalankannya.**
- **Jebakan:** `migrate.sql` memakai `set_updated_at()` tetapi tidak pernah membuatnya. Jalankan hanya di DB yang sudah punya skema lama.
- `seed_demo.sql` kini hanya menambal data non-HR: content, 4 user mahasiswa + profil (2 Public, 2 Private), `user_skills`, evidence + relasi. **Semua `password_hash` berisi literal `'!!demo-not-a-verifiable-hash!!'`** sehingga tidak ada kredensial lemah di repo — akun nyata dibuat lewat `tools/`.

---

## 8. Jebakan yang Diketahui (semuanya diverifikasi di kode)

| # | Jebakan | Lokasi | Sikap yang benar |
| --- | --- | --- | --- |
| 1 | `admin/content.php` GET **sengaja** terbuka: tanpa login hanya baris `status='published'`, admin melihat draf | `public/api/admin/content.php` | **Jangan** "memperbaiki" dengan menambah `api_guard('admin')` |
| 2 | `migrate.sql` butuh `set_updated_at()` sudah ada | `db/migrate.sql` | jalankan hanya di DB lama, atau buat fungsinya lebih dulu |
| 3 | `APP_DEMO=1` auto-login dengan id 1 (mahasiswa) dan 3 (admin) yang menabrak data seed nyata | `includes/auth/auth.php`, `includes/api/bootstrap.php` | selalu `APP_DEMO=0` saat menilai/deploy |
| 4 | `admin/mapping.php` membuat skill baru otomatis dengan kategori keras `'Programming'` | `public/api/admin/mapping.php` | efek samping server-side, bukan bug klien |
| 5 | Cookie `secure` boleh berasal dari `X-Forwarded-Proto` yang bisa dipalsukan | `includes/auth/session.php` | hanya aman di belakang HTTPS milik sendiri |
| 6 | Logout adalah tautan GET tanpa token | `public/logout.php`, dipakai semua layout | bisa dipakai memaksa logout; belum dianggap blocker |
| 7 | Label **Strong/Growing** (≥2 evidence) adalah penilaian tidak langsung, sedangkan laporan melarang penilaian tingkat penguasaan | `includes/api/bootstrap.php` | keputusan pengguna, bukan bug otomatis |
| 8 | `docs/PRD.md` §6 (`db_pbl.sql`, `users.target_career_id`, `required_level`, `skill_confidence`) **tidak sesuai kode hari ini**; `CLAUDE.md` masih menormakan `require_role()` dan `scripts.php` yang tidak dipakai | `docs/PRD.md`, `CLAUDE.md` | sumber akurat: `db/schema.sql`, `db/migrate.sql`, `README.md`, dan file ini |
| 9 | `Laporan/PEMETAAN_PROYEK.md` (23 Okt) menggambarkan arsitektur sebelum cutover dan menandai bug yang sudah diperbaiki sebagai terbuka | bagian 3.3, 6, 9 | baca sebagai sejarah; `Laporan/next.md` §12.3 sudah menandai yang basi |
| 10 | Tidak ada tes otomatis di repo. Satu-satunya regresi = skrip sementara di `/tmp` yang hilang saat reboot | — | Tawarkan memindahkan ke repo sebelum mengklaim "semua hijau" |
| 11 | Migrasi **007 bersifat destruktif**: menghapus permanen seluruh data HR (6 tabel + akun role `hr`) | `db/migrate.sql` bagian 007 | `pg_dump` ke lokasi di luar repo dan minta konfirmasi pengguna sebelum dijalankan di DB bersama |

**Bug lama yang sudah diperbaiki** (jangan "ditemukan ulang"): simpan evidence (`createEvidence`), navbar mahasiswa yang disuntikkan ke halaman admin (`renderNavbar()` dihapus, admin tak lagi men-set `data-nav`), angka dashboard admin yang hardcoded (kini dari `admin/stats.php`), dan `public/data/*.json` terbuka tanpa login (foldernya sudah dihapus).

---

## 9. Fitur vs Klaim Laporan

**Sudah nyata berjalan (server-side):** registrasi/login/logout, master data & CRUD admin (skills, careers, mapping, content, settings, users, admins), modul mahasiswa (skill, evidence + relasi skill, target karier, skill gap, profil, ganti password, unggah foto), pencarian profil publik untuk semua user login, dan halaman profil publik berbasis token (nama, karier, skill, evidence; email tidak pernah ditampilkan).

**Belum ada:** unggah CV + ekstraksi skill + review/konfirmasi (kriteria keberhasilan 2–5 di `Laporan/LaporanPBL.md`) — tidak ada `public/mahasiswa/cv.php`, tidak ada endpoint CV, satu-satunya `$_FILES`/`move_uploaded_file` adalah foto profil. Jalur data `skill_source='cv_extracted'` + `skill_status='candidate'` dan `PATCH /api/skills.php {skill_id, status}` **sudah siap** sebagai target implementasinya. Juga belum ada: suite tes + CI, rate limiting, security header, dan proteksi CSRF di luar endpoint API.

---

## 10. Cara Menjalankan dan Memverifikasi

**Langkah 0 — cadangkan dulu.** `pg_dump` database tujuan (khususnya Supabase) sebelum menyentuh skema. `db/schema.sql` destruktif: `DROP VIEW/TABLE/FUNCTION/TYPE ... CASCADE` (`:25-31`) dan header-nya melarang pemakaian di DB berisi data. Jangan pernah menjalankannya di Supabase tanpa backup.

Urutan satu jalur yang benar:

```bash
# A. Database kosong / rebuild total
createdb skillbridge
psql skillbridge -f db/schema.sql        # destruktif, sekalian seed inline
psql skillbridge -f db/seed_demo.sql     # opsional: data demo

# B. Database yang sudah dipakai (mis. Supabase yang terhubung lewat .env)
pg_dump "$TARGET_DB" > backup_sebelum_007.sql   # WAJIB: 007 menghapus data HR
psql "$TARGET_DB" -f db/migrate.sql              # 001–006 & 008 aditif; 007 destruktif
psql "$TARGET_DB" -c "SELECT version, applied_at FROM schema_migrations ORDER BY version;"

# C. Akun nyata (password lewat env ADMIN_PASSWORD atau prompt tersembunyi)
php tools/create_admin.php admin@contoh.id "Nama Admin"

# D. Jalankan
cp .env.example .env                      # jangan pernah commit .env
APP_DEMO=0 php -S localhost:8000 -t public

# E. Verifikasi struktur dua jalur install harus sama
php tools/check_schema_parity.php skillbridge_fresh skillbridge_migrated
```

- `check_schema_parity.php` membandingkan **struktur** (tabel, kolom, constraint, indeks, view), bukan isi; mengabaikan urutan kolom; exit `0` sama, `1` beda, `2` salah pakai. Mode tanpa argumen memakai `DB_NAME` vs `DB_NAME_COMPARE` — var terakhir belum tercatat di `.env.example`.
- Lokal terisolasi: container Postgres `skillbridge-pg` di `127.0.0.1:55432` (user `sbtest`) dengan override env per perintah. Container `swiperstore-postgres` di port 5433 **bukan milik project ini**.
- Supabase: session pooler port 5432/IPv4. Transaction pooler 6543 hanya jalan dengan `DB_EMULATE_PREPARES=1` (`includes/config/database.php:57`).
- Docker: `php:8.3-apache`, docroot `public/`, `docker/entrypoint.sh` membaca `PORT`.
- Cek cepat tanpa browser: `php -l` untuk sintaks; `curl -i localhost:8000/api/session.php` harus 401 tanpa sesi; buka `/api/skills.php` dan `/api/search.php?q=...` dengan cookie login lalu cocokkan dengan `SELECT` langsung. Verifikasi direktori: `psql "$TARGET_DB" -c "SELECT count(*) FROM v_public_profiles;"` dan buka `/profile.php?token=<uuid>` dari hasil itu tanpa login.
- Tidak ada `composer.json` maupun `package.json` — tidak ada dependensi yang bisa di-install.

---

## 11. Konvensi Layout yang Berlaku (bukan yang tertulis di `CLAUDE.md`)

Variabel yang benar-benar dibaca: `$page_title`, `$role` (memilih `admin.css` di `head.php`), `$body_class`, `$body_attrs`, `$nav_active` (mahasiswa/public), `$admin_page` (admin), `$page_script` (admin), `$extra_head`.

Variabel/komponen yang **mati**: `$page_js` dan `includes/layouts/scripts.php` (tidak pernah di-require), `$role_css` (tidak pernah dibaca), `$path_prefix` (diabaikan `redirect_to_dashboard()`), `includes/auth/middleware.php`. Struktur yang hidup: halaman mahasiswa menutup `</body></html>` sendiri, footer admin yang menutup dokumen; `public/profile.php` memakai footer publik dan menutup dokumen sendiri.

Cetakan halaman baru:

```php
<?php
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('mahasiswa');                 // guard nyata

$page_title = 'Judul · SkillBridge';
$role = 'mahasiswa';
$nav_active = 'skills';
$body_attrs = 'data-nav="skills"';       // admin: data-admin-page="…"

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/mahasiswa_header.php';
?>
  <main class="container"> … </main>
<?php require_once __DIR__ . '/../../includes/layouts/mahasiswa_footer.php'; ?>
  <script src="<?= e(asset('js/ui.js')) ?>"></script>
  <script src="<?= e(asset('js/api.js')) ?>"></script>
  <script src="<?= e(asset('js/pages/halaman.js')) ?>"></script>
</body></html>
```

Aturan tak tertulis yang harus diikuti: semua URL lewat `base_url()`/`asset()` (`includes/config/app.php`, memotong segmen `/mahasiswa|admin` dari `SCRIPT_NAME`); semua output PHP dibungkus `e()`; render data dari API hanya dengan `textContent`/`createElement`; **DILARANG** mengubah class CSS, `id`, atau atribut `data-*` yang menjadi target `public/js/pages/*.js`; jangan men-set `data-nav` di halaman admin (itu penyebab bug navbar lama).

Halaman pencarian memakai template mahasiswa/admin biasa dengan `$body_attrs` berisi `data-search-page="mahasiswa|admin"`. `public/profile.php` adalah pengecualian: tanpa guard, layout publik, akar `#public-profile-root`, dan `pages/public-profile.js`.

---

## 12. File Kunci

| Urusan | File |
| --- | --- |
| Env & koneksi DB | `includes/config/database.php` (`app_setting()` di `:76`), `.env.example` |
| URL & escaping | `includes/config/app.php` |
| Sesi, CSRF, guard, demo | `includes/auth/session.php`, `csrf.php`, `auth.php` |
| Fondasi endpoint | `includes/api/bootstrap.php` |
| Layout | `includes/layouts/head.php`, `scripts.php` (mati), header/sidebar/footer per role |
| Gateway data klien | `public/js/api.js` |
| Utilitas UI | `public/js/ui.js` (`UI.el`, `UI.clear`, `UI.modal`, `UI.toast`, `safeUrl`) |
| Skema | `db/schema.sql` (destruktif), `db/migrate.sql` (aditif), `db/seed_demo.sql` |
| Pencarian & profil publik | `public/mahasiswa/search.php`, `public/admin/search.php`, `public/profile.php`, `public/js/pages/search.js`, `public/js/pages/public-profile.js`, `public/api/search.php`, `public/api/public-profile.php`, `public/api/public-photo.php` |
| Tool CLI | `tools/create_admin.php`, `tools/check_schema_parity.php` |
| Unggahan | `storage/` (di luar docroot, isi di-`.gitignore`) |
| Roadmap & kritik | `Laporan/next.md` |
| Guardrail & aturan | `CLAUDE.md` (sebagian §3/§4 sudah tidak akurat — lihat §8 no. 12) |

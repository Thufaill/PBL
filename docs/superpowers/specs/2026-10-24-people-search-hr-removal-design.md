# People Search & Penghapusan HR — Design Spec

- **Tanggal:** 2026-10-24
- **Status:** Disetujui sebagai desain; menunggu review spec sebelum rencana implementasi
- **Repo:** SkillBridge — PHP 8 native + PostgreSQL, tanpa framework
- **Cakupan:** dua perubahan yang saling terkait: penghapusan total role/modul HR dan fitur pencarian profil publik untuk semua user yang login

## 1. Ringkasan

SkillBridge saat ini memiliki tiga role aplikasi: `mahasiswa`, `admin`, dan `hr`. Role HR memiliki konsol sendiri, enam tabel database, dan alur kandidat yang memakai view `v_public_candidates`. Pengguna memutuskan untuk **menghapus total role HR** dan menggantinya dengan fitur pencarian profil antar-user.

Fitur baru memungkinkan setiap user yang login (mahasiswa dan admin) mencari mahasiswa lain berdasarkan nama, institusi, program studi, lokasi, target karier, atau skill. Hasil hanya menampilkan profil yang sengaja dibuat **Public** oleh pemiliknya. Setiap hasil dapat dibuka di halaman profil publik berbasis `public_token`, yang juga bisa dibagikan dan dibuka tanpa login.

Email tidak pernah ditampilkan pada pencarian maupun profil publik. Admin tetap dapat melihat seluruh akun dan email melalui halaman **Manajemen Pengguna** yang sudah ada.

## 2. Keputusan yang sudah dikunci

| Topik | Keputusan |
| --- | --- |
| Role HR | Dihapus total: halaman, endpoint, layout, CSS/JS, role auth, tabel, seed, tool, dan dokumentasi operasional |
| Akun HR yang ada | Dihapus oleh migrasi database; backup wajib dilakukan lebih dulu |
| Audiens pencarian | Semua user yang login: `mahasiswa` dan `admin` |
| Hasil pencarian | Hanya mahasiswa `role='user'`, `status='active'`, dan `is_public=TRUE` |
| Email | Tidak pernah dikirim ke pencarian, profil publik, atau halaman publik mana pun |
| Identitas publik | `public_token` UUID; bukan `users.id` yang berurutan |
| Halaman profil | `public/profile.php?token=<uuid>`, dapat dibuka tanpa login |
| Token saat Private → Public | Dirotasi agar tautan lama tidak hidup kembali |
| Isi profil publik | Identitas, bio, detail akademik, target karier, skill confirmed, jumlah evidence; tanpa daftar/file evidence |
| Label skill | Tanpa label `Strong`/`Growing` di layar publik; hanya jumlah evidence faktual |
| Penempatan form | Navbar mahasiswa dan header admin; sidebar admin mendapat tautan “Cari Profil” |
| Navbar publik/landing | Tidak diubah |
| Admin melihat semua akun | Tetap melalui `admin/users.php`, bukan melalui direktori publik |
| Dokumentasi | Dokumen operasional diperbarui penuh; laporan akademik hanya disinkronkan pada klaim role/modul yang bertentangan |

## 3. Tujuan dan non-tujuan

### 3.1 Tujuan

1. Menghapus seluruh permukaan role HR tanpa meninggalkan query, route, menu, CSS, atau dokumentasi operasional yang menggantung.
2. Menyediakan pencarian server-side yang hanya mengembalikan profil publik.
3. Menyediakan halaman profil publik yang aman, dapat dibagikan, dan ramah privasi.
4. Menjaga semua alur yang tersisa (mahasiswa dan admin) tetap berjalan setelah tabel HR dihapus.
5. Menjaga instalasi baru dan instalasi lama menghasilkan struktur database yang sama.

### 3.2 Non-tujuan

- Tidak menambahkan pencarian berdasarkan email.
- Tidak menambahkan pagination di luar batas 30 hasil pertama.
- Tidak menambahkan filter skill, follow, pesan, atau fitur sosial lain.
- Tidak menampilkan atau mengunduh file evidence di halaman publik.
- Tidak membuat halaman edit profil publik; pemilik tetap mengedit lewat `mahasiswa/profile.php`.
- Tidak menulis ulang seluruh laporan akademik atau PRD historis.
- Tidak menambahkan framework tes, Composer, atau dependensi baru.

## 4. Arsitektur dan alur data

```
Browser (mahasiswa/admin)
  └─ navbar search form (GET ?q=...)
       └─ GET /mahasiswa/search.php atau /admin/search.php
            └─ API.searchProfiles(q)
                 └─ GET /api/search.php?q=...
                      └─ api_guard()  (semua role login)
                      └─ v_public_profiles + user_skills + skills + evidence
                      └─ hanya field publik, tanpa email
            └─ kartu hasil berisi profile_url
                 └─ GET /profile.php?token=<uuid>   (tanpa login)
                      └─ API.getPublicProfile(token)
                           └─ GET /api/public-profile.php?token=...
                                └─ v_public_profiles + skill confirmed
                      └─ foto: GET /api/public-photo.php?token=...
```

Prinsip yang dipertahankan dari arsitektur saat ini:

- Halaman PHP merender kerangka dan menjaga sesi; JavaScript memanggil endpoint JSON.
- `public/js/api.js` tetap menjadi satu-satunya gateway data klien.
- Endpoint memakai `includes/api/bootstrap.php`, prepared statement, `api_out`/`api_error`, serta `Cache-Control: no-store`.
- Render data pengguna memakai `textContent`/`UI.el`, bukan `innerHTML`.

## 5. Model data dan privasi

### 5.1 View baru `v_public_profiles`

View ini menjadi satu-satunya proyeksi lintas-user untuk fitur publik. Kolom email tidak ada di view.

```sql
CREATE VIEW v_public_profiles AS
SELECT
    u.id,
    u.name,
    sp.public_token,
    sp.target_career_id,
    c.name AS target_role,
    sp.institution,
    sp.program,
    sp.semester,
    sp.location,
    sp.bio,
    sp.photo_path,
    u.created_at AS member_since
FROM users u
JOIN student_profiles sp ON sp.user_id = u.id
LEFT JOIN careers c ON c.id = sp.target_career_id
WHERE u.role = 'user'
  AND u.status = 'active'
  AND sp.is_public = TRUE;
```

`u.id` dan `sp.photo_path` hanya dipakai server-side untuk join dan pengambilan file; API tidak boleh mengembalikannya.

### 5.2 Aturan visibilitas dan token

- `is_public=TRUE` adalah opt-in untuk muncul di direktori dan halaman publik.
- `status='active'` wajib; akun `suspended`/`inactive` tidak muncul.
- `role='user'` wajib; akun admin tidak memiliki profil mahasiswa dan tidak muncul.
- `public_token` adalah UUID unik. URL publik memakai token, bukan id berurutan.
- Private → Public: token **dirotasi** (`gen_random_uuid()`), sehingga tautan lama yang pernah dibagikan tidak otomatis hidup kembali. Implementasi `PATCH /api/profile.php` membaca nilai `is_public` lama dan melakukan update dalam satu transaksi agar rotasi hanya terjadi pada transisi `false → true`.
- Public → Private: token boleh dipertahankan di database, tetapi endpoint selalu memeriksa `is_public`; tautan lama menghasilkan 404.
- Token invalid, milik profil private, atau milik akun nonaktif selalu menghasilkan **404** yang sama, bukan 403, agar keberadaan akun tidak bocor.
- `profile.php` pemilik sendiri tetap memakai sesi dan tidak berubah alurnya.

Catatan kebijakan: `system_settings.default_visibility` saat ini bernilai `Public`, sehingga akun baru langsung dapat ditemukan. Scope ini **tidak mengubah** setelan tersebut. Jika kelak diinginkan opt-in murni, default dapat diubah menjadi `Private` sebagai keputusan produk terpisah.

### 5.3 Skill dan evidence

- Skill yang ditampilkan hanya yang berstatus `confirmed`.
- Skill diambil dari `user_skills` + `skills`; jumlah evidence per skill memakai `v_user_skill_evidence` (hanya evidence milik user yang sama).
- `evidence_count` pada kartu hasil adalah jumlah baris evidence milik user tersebut.
- Halaman publik **tidak** menampilkan daftar evidence, judul, deskripsi, metadata, atau `file_url`.
- Halaman publik **tidak** memakai label `Strong`/`Growing`; label tersebut adalah penilaian tidak langsung dan tidak diperlukan untuk direktori.

## 6. Perubahan database

### 6.1 `db/schema.sql` (instalasi baru)

- Ubah enum: `CREATE TYPE user_role AS ENUM ('user', 'admin');`.
- Hapus seluruh blok tabel HR: `companies`, `company_recruiters`, `vacancies`, `vacancy_skills`, `candidate_reviews`, `activity_log`.
- Hapus view `v_public_candidates`.
- Tambahkan view `v_public_profiles` setelah `v_user_skill_evidence`.
- Daftar `DROP` di awal file tetap boleh menyebut objek HR lama agar reset pada database lama benar-benar bersih; `v_public_profiles` juga harus ada di daftar `DROP VIEW IF EXISTS`.
- Perbarui komentar header yang menyebut role `hr` dan pernyataan bahwa `db/migrate.sql` sepenuhnya aditif.
- Seed inline tidak berubah selain yang berkaitan dengan HR; katalog skill, karier, dan mapping tetap.
- Daftar penanda di `schema_migrations` disesuaikan: hapus `003_hr_module`, tambahkan `007_remove_hr_module` dan `008_public_profile_directory`. Penanda lama lain dipertahankan.

### 6.2 `db/migrate.sql` bagian 007 — hapus modul HR

Bagian 003 (pembuat modul HR) dihapus dari file karena tidak ada lagi instalasi yang boleh membuat modul itu. Database lama yang sudah menerapkan 003 akan dibersihkan oleh 007.

Header file harus diubah: bagian 001–006 aditif, sedangkan **007 sengaja destruktif** dan wajib didahului backup.

Sketsa:

```sql
BEGIN;

DROP VIEW IF EXISTS v_public_candidates;

DROP TABLE IF EXISTS activity_log, candidate_reviews, vacancy_skills,
    vacancies, company_recruiters, companies CASCADE;

DELETE FROM users WHERE role::text = 'hr';

DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM pg_enum e
        JOIN pg_type t ON t.oid = e.enumtypid
        WHERE t.typname = 'user_role' AND e.enumlabel = 'hr'
    ) THEN
        ALTER TABLE users ALTER COLUMN role DROP DEFAULT;
        ALTER TYPE user_role RENAME TO user_role_old;
        CREATE TYPE user_role AS ENUM ('user', 'admin');
        ALTER TABLE users
            ALTER COLUMN role TYPE user_role USING role::text::user_role;
        ALTER TABLE users ALTER COLUMN role SET DEFAULT 'user';
        DROP TYPE user_role_old;
    END IF;
END $$;

INSERT INTO schema_migrations (version)
VALUES ('007_remove_hr_module')
ON CONFLICT (version) DO NOTHING;

COMMIT;
```

Catatan pelaksanaan:

- Drop view sebelum drop tabel.
- Drop tabel HR sebelum menghapus user HR agar tidak ada foreign key yang menghalangi.
- `DELETE FROM users WHERE role::text = 'hr'` menghindari literal enum `'hr'` setelah tipe diubah.
- Rebuild enum diperlukan karena PostgreSQL tidak mendukung `ALTER TYPE ... DROP VALUE`.
- Jika 007 dijalankan dua kali, `IF EXISTS` dan pengecekan `pg_enum` membuatnya aman.
- Bersihkan komentar lama di header, bagian 004, dan bagian 006 yang menyebut konsol HR, `vacancies`, atau `vacancy_skills`; isi migrasi 004 dan 006 tetap dipertahankan.
- Sebelum 007, jalankan `pg_dump`. Tidak ada rollback otomatis; rollback = restore backup.

### 6.3 `db/migrate.sql` bagian 008 — view direktori publik

```sql
BEGIN;

CREATE OR REPLACE VIEW v_public_profiles AS
SELECT
    u.id,
    u.name,
    sp.public_token,
    sp.target_career_id,
    c.name AS target_role,
    sp.institution,
    sp.program,
    sp.semester,
    sp.location,
    sp.bio,
    sp.photo_path,
    u.created_at AS member_since
FROM users u
JOIN student_profiles sp ON sp.user_id = u.id
LEFT JOIN careers c ON c.id = sp.target_career_id
WHERE u.role = 'user'
  AND u.status = 'active'
  AND sp.is_public = TRUE;

INSERT INTO schema_migrations (version)
VALUES ('008_public_profile_directory')
ON CONFLICT (version) DO NOTHING;

COMMIT;
```

### 6.4 `db/seed_demo.sql`

- Hapus blok `companies`, `vacancies`, `vacancy_skills`, `company_recruiters`, `candidate_reviews`, dan `activity_log`.
- Hapus komentar yang menyebut `create_hr.php`, recruiter JSON, atau konsol HR.
- Pertahankan `content_items` dan seluruh data mahasiswa: users, student_profiles, user_skills, evidence, evidence_skills.
- Data demo yang tersisa harus memadai untuk menguji pencarian:
  - Geraldi Rama: publik, aktif, skill PHP/SQL/Git/REST APIs.
  - Siti Aminah: publik, aktif, skill HTML/CSS/JavaScript/Responsive Design.
  - Dimas Saputra: private, aktif, skill Excel/SQL.
  - Lia Anggraini: private dan suspended.
- Jangan menambahkan kredensial lemah. `password_hash` tetap literal `'!!demo-not-a-verifiable-hash!!'`.

### 6.5 Penanda migrasi dan parity

- `schema_migrations` pada database lama tetap menyimpan `003_hr_module` sebagai sejarah; itu tidak masalah.
- Database baru tidak perlu mencatat 003 karena modul itu tidak pernah dibuat.
- Setelah 007/008, jalankan:

```bash
php tools/check_schema_parity.php <db_fresh> <db_migrated>
```

- Struktur yang dibandingkan harus sama: enum tanpa `hr`, tidak ada enam tabel HR, tidak ada `v_public_candidates`, dan ada `v_public_profiles`.

## 7. Penghapusan HR

### 7.1 File yang dihapus

Halaman:

- `public/hr/index.php`
- `public/hr/roles.php`
- `public/hr/candidates.php`
- `public/hr/activity.php`
- `public/hr/team.php`
- `public/hr/settings.php`
- `public/admin/vacancies.php`
- `public/js/pages/admin-vacancies.js`

Endpoint:

- `public/api/hr/company.php`
- `public/api/hr/team.php`
- `public/api/hr/roles.php`
- `public/api/hr/candidates.php`
- `public/api/hr/activity.php`
- `public/api/hr/stats.php`
- `public/api/admin/vacancies.php`

Layout dan aset:

- `includes/layouts/hr_header.php`
- `includes/layouts/hr_sidebar.php`
- `includes/layouts/hr_footer.php`
- `public/css/recruiter.css`
- `public/js/pages/recruiter.js`
- `public/js/pages/recruiter-roles.js`
- `public/js/pages/recruiter-candidates.js`
- `public/js/pages/recruiter-activity.js`
- `public/js/pages/recruiter-team.js`
- `public/js/pages/recruiter-settings.js`

Tool:

- `tools/create_hr.php`

### 7.2 Kode bersama yang dibersihkan

- `includes/auth/auth.php`
  - Hapus komentar tiga role dan referensi `hr`.
  - Hapus `case 'hr'` dari `role_label()`.
  - Hapus entri HR dari `login_demo_user()` dan kredensial demo.
  - Hapus `case 'hr'` dari `redirect_to_dashboard()`.
  - Tambahkan validasi role: sesi yang role-nya bukan `mahasiswa`/`admin` harus di-logout dan diarahkan ke login, bukan masuk ke redirect loop.
- `includes/api/bootstrap.php`
  - Hapus `api_log_activity()` dan `api_hr_company()`.
  - Hapus `hr` dari komentar role, termasuk contoh `'/api[/admin|hr]'` di `api_photo_url()`.
  - Perbarui invariant header yang menyebut `company_id` agar hanya menyebut `user_id`.
  - Tambahkan validasi role pada `api_guard()`: sesi dengan role tak dikenal menghasilkan 401, termasuk pada cabang `demo_mode()`.
  - Tambahkan helper path foto agar bisa dipakai endpoint privat dan publik.
  - Tambahkan helper URL profil publik dan foto publik.
- `includes/auth/middleware.php`
  - Perbarui komentar role `(mahasiswa, hr, admin)`; file ini tidak dipakai halaman mana pun, tetapi referensinya tetap harus bersih.
- `includes/config/app.php`
  - Hapus `hr` dari regex pemotong segmen path.
- `public/api/password.php` dan `public/api/account.php`
  - Perbarui komentar header yang masih menyebut akun/HR dan layar pengaturan HR.
- `includes/layouts/head.php`
  - Hapus cabang `$role === 'hr'` dan komentar HR.
- `includes/layouts/public_header.php`
  - Hapus cabang `get_user_role() === 'hr'` pada tautan Dashboard.
- `public/login.php`
  - Hapus komentar multi-role HR dan tombol demo HR.
- `public/js/api.js`
  - Hapus seluruh metode `getRecruiter*`, `addRecruiter*`, `saveRoleMapping`, `removeRecruiterRole`, `setCandidateStatus`, dan ekspornya.
  - Ganti `getJobAdmins()` dengan `getAdmins()` yang membaca collection `admins`.
  - Hapus `getVacancies()` dan `addVacancy()`.
  - Hapus `jobs` dan `vacancies` dari `ADMIN_ENDPOINT`.
  - Hapus komentar yang menyebut HR/recruiter.
  - `purgeLegacy()` boleh tetap ada untuk membersihkan kunci localStorage lama; kunci `sb.rec.*` di dalamnya sengaja dipertahankan hanya sebagai pembersih dan tidak boleh dipakai fitur baru.
- `public/js/ui.js`
  - Hapus `.rec-sidebar` dari selector sidebar/drawer.
- `public/css/style.css`
  - Hapus semua deklarasi yang hanya menarget `.rec-*`, termasuk yang berada di dalam blok campuran `.admin-*`/`.rec-*`; pertahankan deklarasi `.admin-*`.
  - Hapus komentar yang menyebut konsol HR (termasuk judul bagian 11b dan 11c).
- `public/js/pages/profile.js`
  - Ubah copy visibilitas dari “HR can find you...” menjadi penjelasan direktori publik, misalnya: “Public profiles can be found by other SkillBridge users and viewed through a shareable link.”
- `README.md`, `CLAUDE.md`, `AGENTS.md`, `DESIGN.md`
  - Hapus role HR, perintah `create_hr.php`, endpoint/halaman HR, dan tabel HR.
  - `AGENTS.md:94` yang melarang penghapusan HR harus dihapus/diganti karena keputusan sudah berubah.
  - Tambahkan deskripsi fitur pencarian dan halaman profil publik.
- `Laporan/LaporanPBL.md`, `Laporan/PEMETAAN_PROYEK.md`, `Laporan/next.md`
  - Hanya beri catatan sinkronisasi pada klaim role/modul yang bertentangan; narasi historis tidak ditulis ulang.

### 7.3 Penyederhanaan konsol admin

- `public/api/admin/stats.php`
  - Hapus `jobAdmins`, `openVacancies`, dan `vacancies`.
  - Hapus union “Lowongan” dari feed `recent`.
  - Query `accounts` hanya `role='admin'`.
  - Perbarui komentar kontrak respons.
- `public/js/pages/admin.js`
  - Kartu “Administrator” memakai `platformAdmins` saja, baik untuk nilai maupun teks konteksnya (hapus `jobAdmins` dan label “Admin Job”).
  - Kartu “Relasi mapping” memakai `mappings` dan `publishedContent` tanpa `vacancies`.
  - Tidak ada akses ke field yang sudah dihapus.
- `public/api/admin/admins.php`
  - GET hanya `role='admin'`; hapus join `company_recruiters`, `companies`, `vacancies`, dan `active_vacancies`.
  - PATCH/DELETE hanya mengizinkan `role='admin'`.
- `public/admin/admins.php`
  - Hapus filter role dan kolom Perusahaan/Lowongan.
  - Perbarui deskripsi halaman dan `colspan` loading.
- `public/js/pages/admin-admins.js`
  - Hapus `job_admin`, filter role, kolom perusahaan/lowongan.
  - Gunakan `API.getAdmins()` dan collection `admins`.
- `includes/layouts/admin_sidebar.php`
  - Hapus item Manajemen Lowongan dan `vacancies` dari kondisi group terbuka.
  - Tambahkan item **Cari Profil** dan masukkan `search` ke kondisi group “Manajemen Pengguna & Akses”.
- `public/admin/index.php`
  - Tidak ada perubahan struktural wajib; pastikan copy tetap sesuai setelah field lowongan dihapus.

## 8. Endpoint baru

Semua endpoint baru memakai `includes/api/bootstrap.php`, JSON, `no-store`, dan prepared statement.

### 8.1 `GET /api/search.php?q=<query>`

- **Guard:** `api_guard()` tanpa role; semua user login boleh mencari.
- **Validasi q:** wajib, minimal 2 karakter setelah trim, maksimal 100 karakter. q kosong/terlalu pendek → 422 dengan pesan yang jelas.
- **Pencarian:** `name`, `institution`, `program`, `location`, `target_role`, dan nama skill `confirmed`. Pencocokan skill memakai `EXISTS`, bukan join yang menghasilkan satu baris per skill cocok; daftar skill tiap profil diambil terpisah atau lewat agregasi.
- **Wildcard:** karakter `%`, `_`, dan `\` dari pengguna di-escape agar tidak menjadi wildcard SQL, dengan klausa `ESCAPE '\'` pada setiap `ILIKE`.
- **Batas:** `LIMIT 30`. `total` dihitung dengan `COUNT(*) OVER()`; bila hasil kosong, `total=0`. `truncated` bernilai `total > 30`.
- **Urutan:** nama A–Z.
- **Sumber:** `v_public_profiles`; skill dari `user_skills` + `skills` dengan `status='confirmed'`; evidence count dari `evidence`.
- **Respons:**

```json
{
  "ok": true,
  "query": "php",
  "total": 1,
  "truncated": false,
  "profiles": [
    {
      "token": "uuid",
      "name": "Geraldi Rama",
      "initials": "GR",
      "photo_url": "https://host/api/public-photo.php?token=uuid",
      "profile_url": "https://host/profile.php?token=uuid",
      "institution": "Universitas Demo",
      "program": "Informatika",
      "semester": "6",
      "location": "Jakarta",
      "target_role": "Backend Developer",
      "skills": ["Git", "PHP", "REST APIs", "SQL"],
      "evidence_count": 2
    }
  ]
}
```

`photo_url` bernilai `null` bila file foto tidak ada. Tidak ada `email`, `id`, `status`, `password_hash`, atau path file di respons.

### 8.2 `GET /api/public-profile.php?token=<uuid>`

- **Guard:** tidak ada; dapat diakses tanpa login.
- **Validasi:** token wajib berformat UUID; query ke `v_public_profiles` berdasarkan `public_token`.
- **Hasil tidak ditemukan/private/nonaktif:** 404 dengan pesan generik “Profil tidak ditemukan atau tidak lagi publik.”
- **Respons:**

```json
{
  "ok": true,
  "profile": {
    "name": "Geraldi Rama",
    "initials": "GR",
    "photo_url": null,
    "institution": "Universitas Demo",
    "program": "Informatika",
    "semester": "6",
    "location": "Jakarta",
    "bio": "Suka membangun API dan belajar PostgreSQL.",
    "member_since": "2026-10-23T12:00:00+00:00",
    "target_role": "Backend Developer",
    "skills": [
      { "name": "PHP", "category": "Programming", "evidence_count": 2 }
    ],
    "evidence_count": 2
  }
}
```

Tidak ada email, id, token, status akun, atau path file.

### 8.3 `GET /api/public-photo.php?token=<uuid>`

- **Guard:** tidak ada.
- **Validasi:** token UUID; hanya menyalurkan foto jika profil ada di `v_public_profiles`, `photo_path` tidak null, dan file benar-benar ada di `storage/`.
- **NotFound:** 404 JSON generik untuk token invalid/private/nonaktif maupun file yang hilang.
- **Sukses:** byte gambar dengan `Content-Type` dari `finfo`, `Content-Length`, `Cache-Control: no-store`, `X-Content-Type-Options: nosniff`.
- **Path file:** diturunkan dari `user_id` hasil validasi token, tidak pernah dari input klien.
- **Refactor:** pindahkan helper `api_photo_path()` dari `profile-photo.php` ke `bootstrap.php` agar dipakai endpoint privat dan publik; `profile-photo.php` tetap mempertahankan guard pemilik.

### 8.4 Helper URL

Tambahkan di `includes/api/bootstrap.php`:

- `api_public_profile_url(string $token): string` — membuang segmen `/api` dari `BASE_URL` lalu membentuk `/profile.php?token=...`.
- `api_public_photo_url(string $token): string` — membentuk `/api/public-photo.php?token=...`.

Keduanya meng-`rawurlencode` token.

## 9. Frontend

### 9.1 Navbar mahasiswa

`includes/layouts/mahasiswa_header.php`:

- Tambahkan form di dalam `.navbar-nav`, setelah item navigasi:

```html
<form class="navbar-search" role="search" action="<?= e(base_url('mahasiswa/search.php')) ?>" method="get">
  <label class="visually-hidden" for="navbar-search-input">Search public profiles</label>
  <input type="search" id="navbar-search-input" name="q" placeholder="Search people..." autocomplete="off" />
</form>
```

- Copy berbahasa Inggris, sesuai aturan konsol mahasiswa.
- Desktop: form berada di ujung kanan nav (mis. `margin-left: auto`), lebar 220px pada ≥1280px dan `clamp(96px, 10vw, 140px)` pada 1024–1279px. Navbar sudah memuat lima item dan blok profil, jadi lebar harus diverifikasi di browser pada 1024px dan 1280px agar tidak overflow.
- Mobile ≤1023px: `.navbar-nav` berubah menjadi drawer kolom; form otomatis menjadi item full-width.
- Tidak menambahkan item nav baru; form adalah pintu masuk halaman pencarian.

### 9.2 Header admin dan sidebar

`includes/layouts/admin_header.php`:

- Tambahkan form di antara `.admin-header-left` dan `.admin-header-right`:

```html
<form class="admin-header-search" role="search" action="<?= e(base_url('admin/search.php')) ?>" method="get">
  <label class="visually-hidden" for="admin-header-search-input">Cari profil publik</label>
  <input type="search" id="admin-header-search-input" name="q" placeholder="Cari profil publik..." autocomplete="off" />
</form>
```

- Copy berbahasa Indonesia.
- Desktop ≥1024px: tampil dengan `flex:1`, `max-width:380px`.
- Mobile <1024px: form disembunyikan agar header tidak penuh; akses melalui sidebar.

`includes/layouts/admin_sidebar.php`:

- Tambahkan tautan **Cari Profil** di grup “Manajemen Pengguna & Akses”.
- Masukkan `search` ke kondisi `in_array($admin_page, [...])` agar grup terbuka.

### 9.3 Halaman hasil pencarian

Dua halaman tipis dengan layout masing-masing:

| File | Guard | `$role` | Copy |
| --- | --- | --- | --- |
| `public/mahasiswa/search.php` | `check_auth('mahasiswa')` | `mahasiswa` | Inggris |
| `public/admin/search.php` | `check_auth('admin')` | `admin` | Indonesia |

Keduanya memuat:

- `head.php`
- layout role masing-masing
- input pencarian besar di dalam konten (`#people-search-input`)
- container hasil (`#people-search-results`)
- `ui.js`, `api.js`, dan `pages/search.js`
- `data-search-page="mahasiswa"` atau `data-search-page="admin"` pada `<body>`; halaman admin juga men-set `$admin_page = 'search'` dan `$page_script = 'search.js'`, sedangkan halaman mahasiswa memuat ketiga skrip secara manual seperti halaman mahasiswa lain.

Perilaku `public/js/pages/search.js`:

1. Baca `data-search-page` dari `<body>` untuk menentukan copy dan endpoint halaman.
2. Baca `?q=` dari URL, isi input, dan langsung cari jika panjang ≥2.
3. Debounce 250ms saat mengetik.
4. Sinkronkan query ke URL dengan `history.replaceState`, tanpa reload.
5. Tampilkan:
   - hint jika query <2 karakter;
   - loading state;
   - jumlah hasil;
   - pesan “30 hasil pertama” bila `truncated`;
   - empty state dengan query yang dicari;
   - error state dari `ApiError`.
6. Render kartu dengan `UI.el` dan `text` (textContent):
   - avatar/inisial;
   - nama;
   - institusi · program · semester;
   - lokasi;
   - target karier;
   - maksimal 4 skill + “+N”;
   - jumlah evidence;
   - tautan ke `profile_url`.
7. Tidak menampilkan email, id, atau status akun.
8. Semua tautan hasil memakai `profile_url` dari server, bukan menyusun URL sendiri.

### 9.4 Halaman profil publik

`public/profile.php`:

- Tanpa guard login; bisa dibuka siapa saja yang memiliki token.
- `$page_title = 'Profil Publik · SkillBridge'`, `$role = 'public'`.
- Memuat `head.php`, `public_header.php`, konten, `public_footer.php`, `ui.js`, `api.js`, dan `pages/public-profile.js`.
- Menambahkan `<meta name="robots" content="noindex, nofollow">` dan `<meta name="referrer" content="no-referrer">` untuk mengurangi penyebaran token lewat mesin pencari/referrer.
- Menampilkan:
  - foto atau inisial;
  - nama;
  - target karier;
  - institusi, program, semester, lokasi;
  - bio;
  - tanggal bergabung;
  - jumlah evidence;
  - skill confirmed dengan jumlah evidence per skill, dikelompokkan per kategori bila memungkinkan.
- Tautan kembali:
  - jika login sebagai admin → `admin/search.php`;
  - jika login sebagai mahasiswa → `mahasiswa/search.php`;
  - jika tamu → `index.php`.
- Tidak ada tombol edit, email, daftar evidence, atau tautan file.

### 9.5 Klien API

Tambahkan di `public/js/api.js`:

- `searchProfiles(query)` → `GET /api/search.php?q=...`
- `getPublicProfile(token)` → `GET /api/public-profile.php?token=...`

Hapus seluruh metode HR dan metode lowongan/admin job yang sudah tidak ada. Perbarui ekspor di akhir file.

### 9.6 CSS dan responsive

`public/css/style.css`:

- `.navbar-search` dan input-nya.
- `.people-search-*`, `.people-grid`, `.person-card`, `.person-card-*`.
- `.public-profile-*` untuk hero, detail, skill, dan state kosong.
- Media query:
  - ≥1280px: input navbar 220px.
  - 1024–1279px: input navbar `clamp(96px, 10vw, 140px)`.
  - ≤1023px: form navbar full-width di drawer.
- Hapus aturan `.rec-*` murni dan komentar HR; pertahankan aturan `.admin-*` yang berbagi blok.

`public/css/admin.css`:

- `.admin-header-search` dan input-nya.
- `@media (max-width: 1023px) { .admin-header-search { display: none; } }`.

### 9.7 Copy dan aksesibilitas

- Konsol mahasiswa: Inggris.
- Konsol admin: Indonesia.
- Halaman publik/profil: Indonesia.
- Form memakai `role="search"`, label `visually-hidden`, dan input `type="search"`.
- Hasil pencarian diumumkan lewat `aria-live="polite"` pada penghitung hasil.
- Fokus keyboard terlihat; kartu hasil adalah tautan nyata, bukan `div` yang dapat diklik.
- Data pengguna selalu dirender dengan `textContent`.

## 10. Keamanan dan penanganan error

1. **Tanpa email di jalur publik.** `v_public_profiles`, respons search, respons public-profile, dan halaman tidak boleh memuat email.
2. **Tanpa id berurutan.** URL publik memakai UUID; respons tidak mengembalikan `users.id`.
3. **404 seragam** untuk token invalid, private, nonaktif, atau tidak ada.
4. **Foto hanya untuk profil publik.** Endpoint foto publik memvalidasi token ke view; path file diturunkan dari user id, bukan input.
5. **Sesi HR lama.** `check_auth` dan `api_guard` menolak role yang tidak dikenal; sesi dihapus dan pengguna diarahkan ke login/401.
6. **Tidak ada query dari body untuk data sensitif.** Search hanya membaca `q`; tidak ada user id dari klien.
7. **Prepared statement** dan placeholder berbeda untuk setiap kemunculan `q` (native prepares tidak bisa memakai satu placeholder berulang).
8. **Escape wildcard** `%`, `_`, `\` pada input pencarian.
9. **Batas hasil 30** dan query minimal 2 karakter untuk mengurangi enumerasi masif. Rate limiting tidak tersedia di repo; ini batasan yang diketahui.
10. **CSRF** tidak diperlukan untuk endpoint GET; tidak ada endpoint tulis baru.
11. **Cache:** API `no-store`; foto publik `no-store`; halaman profil memakai meta noindex/referrer.
12. **Error database:** `try/catch (PDOException)` → 500 generik + `error_log`, sesuai pola endpoint lain.

## 11. Pengujian dan acceptance criteria

### 11.1 Pemeriksaan statis

- `php -l` untuk setiap file PHP yang diubah/ditambah.
- Grep memastikan tidak ada referensi `hr`, `recruiter`, `vacancy`, `company_recruiters`, `candidate_reviews`, `activity_log`, atau `v_public_candidates` yang tersisa di kode aktif. Pengecualian yang disengaja: daftar kunci legacy `sb.rec.*` di `purgeLegacy()` dan dokumen historis di `Laporan/`.
- Grep memastikan tidak ada `email` di `v_public_profiles`, respons search, atau halaman profil publik.

### 11.2 Database

- **Fresh install:** `createdb` → `psql -f db/schema.sql` → `psql -f db/seed_demo.sql`.
  - `SELECT enum_range(NULL::user_role);` → `{user,admin}`.
  - Enam tabel HR tidak ada.
  - `v_public_profiles` ada dan hanya berisi profil publik aktif.
- **Migrasi dari skema lama:**
  - Muat skema lama (sebelum perubahan) ke database uji, isi seed, tambahkan satu akun `role='hr'`.
  - Jalankan `db/migrate.sql`.
  - Akun HR terhapus; tabel HR dan `v_public_candidates` hilang; enum hanya `user,admin`; `v_public_profiles` ada.
  - Jalankan ulang `db/migrate.sql` untuk memastikan idempoten.
- **Parity:** `php tools/check_schema_parity.php <fresh> <migrated>` exit 0.

### 11.3 API

- Tanpa sesi, `GET /api/search.php?q=php` → 401.
- Login mahasiswa, `q=php` → hanya profil publik yang cocok; tidak ada email/id.
- `q=dimas` atau `q=data` → Dimas (private) tidak muncul.
- `q=lia` → Lia (suspended/private) tidak muncul.
- `q=` atau `q=a` → 422.
- Token publik Geraldi di `/api/public-profile.php` → 200 dan data benar.
- Token private Dimas, token acak, dan token non-UUID → 404.
- `/api/public-photo.php` dengan token publik yang punya foto → bytes; token private/acak → 404.
- Admin dapat memakai `/api/search.php` dan hanya melihat profil publik.
- `admin/users.php` tetap mengembalikan seluruh akun mahasiswa termasuk email untuk admin.

### 11.4 Browser

- Login mahasiswa:
  - form pencarian tampil di navbar desktop;
  - submit menuju `mahasiswa/search.php?q=...`;
  - hasil tampil, kartu membuka profil publik;
  - query <2 karakter menampilkan hint;
  - tidak ada error console.
- Mobile mahasiswa:
  - form tampil full-width di drawer;
  - pencarian dan hasil tetap berfungsi.
- Login admin:
  - form pencarian tampil di header desktop;
  - pada <1024px form header tersembunyi dan tautan sidebar “Cari Profil” berfungsi;
  - halaman Users, Admins, Dashboard, Skills, Careers, Mapping, Settings tetap berfungsi;
  - tidak ada field lowongan/admin job yang kosong atau error.
- Profil publik:
  - dibuka di jendela incognito tanpa login;
  - foto tampil bila ada;
  - email dan daftar evidence tidak ada di DOM maupun network;
  - profil private dengan URL token lama → halaman 404 yang ramah.
- HR:
  - login dengan akun HR lama gagal atau sesi lama ditolak;
  - `/hr/index.php` dan endpoint `/api/hr/*` tidak ada (404).
- Tidak ada error JavaScript di console pada semua halaman yang diuji.

### 11.5 Acceptance criteria

1. Role HR tidak lagi ada di enum, kode, menu, route, atau dokumen operasional.
2. Semua tabel/endpoint/halaman HR lama sudah dihapus dan tidak ada query yang menyentuhnya.
3. Pencarian hanya mengembalikan profil `Public`, aktif, role mahasiswa, tanpa email.
4. Halaman profil publik dapat dibuka tanpa login melalui token dan tidak membocorkan email/evidence/file.
5. Mahasiswa dan admin sama-sama dapat mengakses pencarian dari navbar/header.
6. Admin console yang tersisa berfungsi setelah field lowongan/admin job dihapus.
7. Fresh schema dan migrated schema identik menurut `check_schema_parity.php`.
8. Dokumentasi operasional tidak lagi menyuruh membuat atau memakai HR.

## 12. Rollout dan rollback

### 12.1 Urutan rollout yang disarankan

1. Umumkan maintenance window singkat.
2. Backup database: `pg_dump` ke file di luar repo.
3. Jalankan `db/migrate.sql` (007 dan 008) pada database tujuan.
4. Deploy kode baru.
5. Jalankan smoke test API dan browser.
6. Periksa `schema_migrations` berisi 007 dan 008.

Urutan alternatif yang juga valid: deploy kode baru lebih dulu (kode tidak lagi memakai tabel HR), lalu jalankan migrasi. Namun halaman HR lama akan gagal setelah migrasi, jadi maintenance window tetap disarankan.

### 12.2 Rollback

- Tidak ada rollback otomatis untuk 007 karena tabel dan akun HR dihapus.
- Rollback = restore `pg_dump` dan deploy ulang kode versi sebelumnya.
- Jangan menjalankan `db/schema.sql` pada database berisi data.

## 13. Risiko dan batasan yang diketahui

- **Kehilangan data HR permanen** setelah 007. Mitigasi: backup wajib dan konfirmasi sebelum eksekusi.
- **Default visibilitas Public** membuat akun baru langsung muncul di direktori. Tidak diubah dalam scope ini; keputusan produk terpisah jika ingin opt-in murni.
- **Token adalah bearer link.** Siapa pun yang memiliki tautan dapat melihat profil selama statusnya Public. Rotasi saat Private → Public mengurangi risiko tautan lama.
- **Tanpa rate limiting.** Batas 30 hasil dan minimal 2 karakter mengurangi enumerasi, tetapi bukan pengganti rate limiter.
- **Pencarian ILIKE tanpa indeks trigram.** Dataset saat ini kecil; jika tumbuh besar, pertimbangkan indeks trigram.
- **Admin tidak dapat mencari profil private dari direktori.** Admin memakai halaman Users yang sudah ada.
- **Laporan akademik historis** dapat tetap menyebut HR; hanya klaim role/modul yang disinkronkan, bukan seluruh narasi.
- **Tidak ada tes otomatis permanen.** Verifikasi dilakukan dengan `php -l`, psql, curl, dan browser; ini mengikuti kondisi repo saat ini.

## 14. Inventaris file

### 14.1 File baru

- `public/mahasiswa/search.php`
- `public/admin/search.php`
- `public/profile.php`
- `public/api/search.php`
- `public/api/public-profile.php`
- `public/api/public-photo.php`
- `public/js/pages/search.js`
- `public/js/pages/public-profile.js`
- `docs/superpowers/specs/2026-10-24-people-search-hr-removal-design.md`

### 14.2 File yang diubah

- `includes/auth/auth.php`
- `includes/auth/middleware.php`
- `includes/api/bootstrap.php`
- `includes/config/app.php`
- `public/api/account.php`
- `public/api/password.php`
- `includes/layouts/head.php`
- `includes/layouts/mahasiswa_header.php`
- `includes/layouts/admin_header.php`
- `includes/layouts/admin_sidebar.php`
- `includes/layouts/public_header.php`
- `public/login.php`
- `public/register.php` (hanya jika komentar/copy perlu disesuaikan)
- `public/api/profile.php` (rotasi token Private → Public)
- `public/api/profile-photo.php` (memakai helper path bersama)
- `public/api/admin/stats.php`
- `public/api/admin/admins.php`
- `public/admin/admins.php`
- `public/admin/index.php` (bila copy perlu)
- `public/js/api.js`
- `public/js/ui.js`
- `public/js/pages/admin.js`
- `public/js/pages/admin-admins.js`
- `public/js/pages/profile.js`
- `public/css/style.css`
- `public/css/admin.css`
- `db/schema.sql`
- `db/migrate.sql`
- `db/seed_demo.sql`
- `README.md`
- `CLAUDE.md`
- `AGENTS.md`
- `DESIGN.md`
- Dokumen akademik yang perlu catatan sinkronisasi: `docs/PRD.md`, `docs/ai_rules_responsive_dan_button.md`, `docs/prd_responsive_dan_button.md`, `Laporan/LaporanPBL.md`, `Laporan/PEMETAAN_PROYEK.md`, `Laporan/next.md` (catatan singkat, bukan rewrite)

### 14.3 File yang dihapus

Lihat bagian 7.1. Tidak ada file HR yang dipertahankan sebagai dead code.

## 15. Pertanyaan terbuka

Tidak ada. Empat asumsi desain sudah dikonfirmasi melalui instruksi “lanjutkan”:

1. Akun HR dihapus, bukan dikonversi.
2. Pencarian untuk semua user login dengan hasil hanya profil Public.
3. Profil publik menampilkan skill + jumlah evidence, bukan daftar/file evidence.
4. Form ada di navbar mahasiswa dan header admin; navbar publik tidak diubah.

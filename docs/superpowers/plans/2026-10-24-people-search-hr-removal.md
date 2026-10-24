# People Search & HR Removal Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remove the HR role/module completely from SkillBridge and add a privacy-preserving public-profile search plus a token-based public profile page for all logged-in users.

**Architecture:** PHP pages render the shell and session; JavaScript calls JSON endpoints under `public/api/`; endpoints talk to PostgreSQL through PDO. Search reads a new `v_public_profiles` view that excludes email and only exposes active, public student profiles. Public profile pages use an unguessable UUID token and a separate public-photo endpoint. The HR console, its six tables, and its endpoints are deleted; a destructive migration `007` removes HR data, and an additive migration `008` creates the public view.

**Tech Stack:** PHP 8 native, PostgreSQL, PDO prepared statements, vanilla JavaScript, CSS. No Composer, no package manager, no test framework.

**Spec:** `docs/superpowers/specs/2026-10-24-people-search-hr-removal-design.md`

## Global Constraints

- **No new dependencies.** Do not add Composer, npm, a test framework, or a CSS/JS library.
- **Database writes:** Do not run `db/migrate.sql` against the shared Supabase database in this workspace without (1) a fresh `pg_dump` backup stored outside the repo and (2) explicit user confirmation. Migration `007` permanently deletes HR data. Until the user runs it, search/public-profile endpoints will fail against the existing database because `v_public_profiles` does not exist yet; that is expected.
- **Never run `db/schema.sql` on a populated database.** It begins with `DROP ... CASCADE`.
- **Never commit.** The user has not asked for a commit. Leave the working tree dirty and report changed files. Do not run `git reset --hard`, `git checkout --`, `git clean`, or `git stash`.
- **Keep `APP_DEMO=0`** for evaluation and deployment. Demo mode bypasses auth and can read/write real rows.
- **Never print or commit `.env` values**, especially `DB_*`.
- **Security invariants for every endpoint:** prepared statements only; ownership from the session, never from the request body; CSRF on writes; JSON-only responses; `Cache-Control: no-store`; `X-Content-Type-Options: nosniff`; `try/catch (PDOException)` → generic 500 + `error_log()`.
- **Email must never appear** in `v_public_profiles`, `/api/search.php`, `/api/public-profile.php`, `/profile.php`, or their rendered DOM.
- **Uniform 404** for invalid, private, suspended, or missing public tokens. Never return 403 for those cases.
- **Token rotation:** rotate `public_token` only on the Private → Public transition. Public → Private keeps the token but the view excludes the profile.
- **Copy language:** mahasiswa console and navbar search copy in English; admin console, public profile page, and shared public copy in Indonesian.
- **Frontend rendering:** user/API data only with `textContent`/`UI.el`, never `innerHTML`.
- **CSS:** preserve every `.admin-*` rule while removing `.rec-*`. Do not rename existing classes/IDs used by page scripts.

---

### Task 1: Database — fresh schema, migration 007/008, demo seed

**Files:**
- Modify: `db/schema.sql`
- Modify: `db/migrate.sql`
- Modify: `db/seed_demo.sql`

**Interfaces:**
- Produces table/view contract: `v_public_profiles(id, name, public_token, target_career_id, target_role, institution, program, semester, location, bio, photo_path, member_since)`.
- Produces enum contract: `user_role = ('user', 'admin')`.
- Produces migration markers: `007_remove_hr_module`, `008_public_profile_directory`.

- [ ] **Step 1: Update `db/schema.sql` header and drop list**

Replace the header lines that mention HR and “migrate additive” with:

```sql
-- Role: user (mahasiswa) dan admin.
-- Termasuk view direktori publik v_public_profiles untuk fitur pencarian.
-- Hasil instalasi file ini HARUS identik (struktur) dengan database lama yang
-- diberi db/migrate.sql. Perbandingan strukturnya otomatis
-- lewat tools/check_schema_parity.php.
--
-- PERINGATAN: file ini menghapus objek lama (DROP ... CASCADE).
-- Seluruh data SkillBridge di database akan hilang. Hanya dijalankan
-- pada database kosong. Untuk database berisi data, gunakan
-- db/migrate.sql.
```

In the cleanup block, change the view drop to:

```sql
DROP VIEW IF EXISTS v_skill_gap, v_user_skill_evidence, v_public_candidates, v_public_profiles;
```

Keep the HR table names in the `DROP TABLE` list so resetting an old database is clean:

```sql
DROP TABLE IF EXISTS activity_log, candidate_reviews, vacancy_skills, vacancies,
    company_recruiters, companies, content_items, system_settings,
    evidence_skills, user_skills, evidence,
    student_profiles, career_skill_requirements, users, skills, careers CASCADE;
```

Change the enum:

```sql
CREATE TYPE user_role AS ENUM ('user', 'admin');
```

- [ ] **Step 2: Delete the HR table block from `db/schema.sql`**

Delete the whole block from `-- ---------- MODUL HR / PERUSAHAAN ----------` through the end of `activity_log` and its index. The surviving tables must be exactly: `careers`, `skills`, `career_skill_requirements`, `users`, `student_profiles`, `evidence`, `user_skills`, `evidence_skills`, `system_settings`, `content_items`.

- [ ] **Step 3: Replace `v_public_candidates` with `v_public_profiles`**

After `v_user_skill_evidence` (and before the seed block) insert:

```sql
-- Direktori profil publik: hanya mahasiswa aktif yang memilih Public.
-- View ini TIDAK memuat email dan tidak memakai users.id sebagai identitas
-- publik; endpoint memakai public_token.
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

- [ ] **Step 4: Update the migration markers in `db/schema.sql`**

Replace the marker insert with:

```sql
INSERT INTO schema_migrations (version)
VALUES ('000_migrations'), ('schema.sql'),
       ('001_mahasiswa_domain'), ('002_platform_settings_content'),
       ('004_catalog_expansion'), ('005_evidence_source_types'),
       ('006_master_timestamps'), ('007_remove_hr_module'),
       ('008_public_profile_directory')
ON CONFLICT (version) DO NOTHING;
```

- [ ] **Step 5: Rewrite the `db/migrate.sql` header**

Replace the “SIFAT” block with:

```sql
-- SIFAT:
--   - Bagian 001–006 dan 008 bersifat ADITIF dan IDEMPOTEN.
--   - Bagian 007 SENGAJA DESTRUKTIF: menghapus tabel HR, view lama, dan akun
--     ber-role 'hr'. WAJIB didahului pg_dump ke lokasi di luar repo.
--     Tidak ada rollback otomatis; rollback = restore backup.
--   - Setiap bagian punya BEGIN/COMMIT sendiri, kecuali 005.
```

Remove the line that says demo data is for “konsol admin/HR”; replace with “Data contoh konsol admin dan direktori publik”.

- [ ] **Step 6: Delete migration section 003 from `db/migrate.sql`**

Delete the entire block:

```sql
-- ============================================================
-- 003 — Modul HR / perusahaan
-- ...
COMMIT;
```

Continue the file with section 004.

- [ ] **Step 7: Clean the 004 and 006 comments**

In section 004, replace the paragraph that mentions “target role HR” and `vacancy_skills` with:

```sql
-- Skema lama hanya mengenal 18 skill. Katalog UI dan relasi karier
-- ternyata meminta skill yang tidak ada di database: Node.js,
-- Express.js, TypeScript, Vue, React, MySQL, Pandas, Tableau, Power BI,
-- Testing, Deployment. Tanpa baris ini, relasi career_skill_requirements
-- dan bukti mahasiswa tidak bisa menunjuk ke skill nyata. Categories
-- sengaja memakai enam nama yang sudah dikenal UI supaya ikon kategori
-- tetap konsisten.
```

In section 006, replace “digabung dari users, skills, careers, dan vacancies” with “digabung dari users, skills, dan careers”.

- [ ] **Step 8: Append section 007 (destructive HR removal)**

Append this exact block to `db/migrate.sql`:

```sql
-- ============================================================
-- 007 — Hapus total modul HR (DESTRUKTIF)
--
-- Menghapus enam tabel HR, view kandidat lama, dan seluruh akun role 'hr'.
-- Tidak ada rollback otomatis. Backup dulu:
--   pg_dump "$TARGET_DB" > backup_sebelum_007.sql
-- ============================================================

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

- [ ] **Step 9: Append section 008 (public directory view)**

```sql
-- ============================================================
-- 008 — View direktori profil publik
--
-- Hanya mahasiswa aktif yang memilih Public. Tidak ada email.
-- ============================================================

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

- [ ] **Step 10: Clean `db/seed_demo.sql`**

1. Replace the header description with:

```sql
-- Data contoh untuk konsol admin dan direktori profil publik. Opsional.
```

2. Remove the credential sentence that mentions `tools/create_hr.php`; keep:

```sql
-- Untuk akun yang bisa login, jalankan tools/create_admin.php.
```

3. Delete the blocks:
   - `-- ---------- ADMIN: LOWONGAN + HR: TARGET ROLE ----------` through the `company_recruiters` insert (all `companies`, `vacancies`, `vacancy_skills`, `company_recruiters`).
   - `-- ---------- HR: PIPELINE KANDIDAT ----------` through the `activity_log` insert.

4. Keep `content_items`, the four student `users`, `student_profiles`, `user_skills`, `evidence`, and `evidence_skills`.

5. Update the profile comment to:

```sql
-- Profil: dua mahasiswa opt-in publik, dua tidak. Hanya profil publik
-- aktif yang muncul di direktori; kolom public_token menjadi identitas URL.
```

- [ ] **Step 11: Static verification**

Run:

```bash
grep -nE 'CREATE TYPE user_role|CREATE (OR REPLACE )?VIEW v_public_profiles|007_remove_hr_module|008_public_profile_directory' db/schema.sql db/migrate.sql
grep -nE 'CREATE TABLE (companies|company_recruiters|vacancies|vacancy_skills|candidate_reviews|activity_log)' db/schema.sql db/migrate.sql
```

Expected: first command finds the enum and view/markers; second command returns nothing.

---

### Task 2: Auth, shared backend, and shared config

**Files:**
- Modify: `includes/auth/auth.php`
- Modify: `includes/auth/middleware.php`
- Modify: `includes/api/bootstrap.php`
- Modify: `includes/config/app.php`
- Modify: `public/api/account.php`
- Modify: `public/api/password.php`
- Modify: `includes/layouts/head.php`
- Modify: `includes/layouts/public_header.php`
- Modify: `public/login.php`

**Interfaces:**
- Produces `auth_role_is_valid($role): bool` (valid values: `mahasiswa`, `admin`).
- Produces `api_photo_path(int $userId): string`.
- Produces `api_public_profile_url(string $token): string`.
- Produces `api_public_photo_url(string $token): string`.
- Removes `api_log_activity()`, `api_hr_company()`, and every `hr` branch.

- [ ] **Step 1: Update `includes/auth/auth.php`**

Change the header comment to:

```php
// Autentikasi dan sesi untuk dua role aplikasi (mahasiswa, admin).
//
// Role di database (enum user_role): 'user' | 'admin'.
// Role di aplikasi (folder & sesi):  'mahasiswa' | 'admin'.
// 'user' (DB) <-> 'mahasiswa' (aplikasi) dipetakan oleh dua fungsi di bawah.
```

Add the validator after `app_role_to_db_role()`:

```php
/** Role aplikasi yang masih dikenal. Dipakai guard dan login. */
function auth_role_is_valid($role): bool {
    return in_array($role, ['mahasiswa', 'admin'], true);
}
```

Remove the `case 'hr'` line from `role_label()`.

Replace `check_auth()` with:

```php
function check_auth($required_role = null, $redirect_on_fail = true) {
    header('Cache-Control: no-store');

    if ($required_role !== null && !auth_role_is_valid($required_role)) {
        logout();
        header('Location: ' . base_url('login.php'));
        exit;
    }

    if (demo_mode()) {
        if (!is_logged_in() && $required_role) {
            login_demo_user($required_role);
        } elseif ($required_role && get_user_role() !== $required_role) {
            login_demo_user($required_role);
        }
        if (is_logged_in() && !auth_role_is_valid(get_user_role())) {
            logout();
            header('Location: ' . base_url('login.php'));
            exit;
        }
        return true;
    }

    if (!is_logged_in()) {
        header('Location: ' . base_url('login.php'));
        exit;
    }

    if (!auth_role_is_valid(get_user_role())) {
        logout();
        header('Location: ' . base_url('login.php'));
        exit;
    }

    if ($required_role && get_user_role() !== $required_role) {
        redirect_to_dashboard(get_user_role());
    }

    return true;
}
```

Replace `login_demo_user()`'s map with only two roles:

```php
function login_demo_user($role = 'mahasiswa') {
    if (!demo_mode()) {
        return null;
    }
    $demo_users = [
        'mahasiswa' => [
            'id' => 1, 'name' => 'Jhon Doe', 'email' => 'jhondoe@gmail.com',
            'role' => 'mahasiswa', 'role_label' => 'Student', 'initials' => 'JD',
        ],
        'admin' => [
            'id' => 3, 'name' => 'Aditya Pratama', 'email' => 'admin@skillbridge.id',
            'role' => 'admin', 'role_label' => 'Admin Utama · Platform Owner', 'initials' => 'AP',
        ],
    ];
    if (!isset($demo_users[$role])) {
        $role = 'mahasiswa';
    }
    $_SESSION['user'] = $demo_users[$role];
    return $_SESSION['user'];
}
```

In `attempt_login()`, reject deleted roles before creating the session:

```php
if ($user && $valid) {
    session_regenerate_id(true);
    $role = db_role_to_app_role($user['role']);
    if (!auth_role_is_valid($role)) {
        return ['success' => false, 'error' => 'invalid'];
    }
    $_SESSION['user'] = [
        // unchanged fields
    ];
    return ['success' => true, 'role' => $role];
}
```

Remove the `'hr@techindonesia.com' => 'hr'` entry from `$demo_creds`.

Replace `redirect_to_dashboard()` with:

```php
function redirect_to_dashboard($role, $path_prefix = '') {
    if (!auth_role_is_valid($role)) {
        header('Location: ' . base_url('login.php'));
        exit;
    }
    $target = $role === 'admin' ? 'admin/index.php' : 'mahasiswa/dashboard.php';
    header('Location: ' . base_url($target));
    exit;
}
```

- [ ] **Step 2: Update `includes/auth/middleware.php`**

Change line 3 to:

```php
// Middleware pelindung rute & otorisasi role (mahasiswa, admin)
```

- [ ] **Step 3: Update `includes/api/bootstrap.php`**

Header invariant 1 becomes:

```php
//   1. Data domain selalu difilter dengan user_id dari SESI, tidak pernah
//      dari body request.
```

Replace `api_guard()` with:

```php
function api_guard(?string $role = null): array {
    header('Cache-Control: no-store');

    if ($role !== null && !auth_role_is_valid($role)) {
        api_error('Akun ini tidak punya hak akses tersebut.', 403);
    }

    if (demo_mode()) {
        if (!is_logged_in() && $role) {
            login_demo_user($role);
        } elseif ($role && get_user_role() !== $role) {
            login_demo_user($role);
        }
        if (!is_logged_in()) {
            login_demo_user('mahasiswa');
        }
        if (!auth_role_is_valid(get_user_role())) {
            logout();
            api_error('Sesi tidak valid. Silakan login ulang.', 401);
        }
        return current_user();
    }

    if (!is_logged_in()) {
        api_error('Sesi tidak ada. Silakan login.', 401);
    }
    if (!auth_role_is_valid(get_user_role())) {
        logout();
        api_error('Sesi tidak valid. Silakan login ulang.', 401);
    }
    $user = current_user();
    if ($role && (get_user_role() !== $role)) {
        api_error('Akun ini tidak punya hak akses tersebut.', 403);
    }
    return $user;
}
```

Change the `api_photo_url()` comment from `<basis app>/api[/admin|hr]` to `<basis app>/api[/admin]`.

After `api_photo_url()`, add:

```php
/** Path absolut foto user. Nama file diturunkan dari id, bukan dari input. */
function api_photo_path(int $userId): string {
    $dir = dirname(__DIR__, 2) . '/storage';
    foreach (['png', 'jpg', 'webp'] as $ext) {
        $candidate = "{$dir}/avatar_{$userId}.{$ext}";
        if (is_file($candidate)) {
            return $candidate;
        }
    }
    return '';
}

/** URL halaman profil publik berbasis token. */
function api_public_profile_url(string $token): string {
    $base = rtrim(preg_replace('#/api(/.*)?$#', '', BASE_URL), '/');
    return $base . '/profile.php?token=' . rawurlencode($token);
}

/** URL foto profil publik berbasis token. */
function api_public_photo_url(string $token): string {
    $base = rtrim(preg_replace('#/api(/.*)?$#', '', BASE_URL), '/');
    return $base . '/api/public-photo.php?token=' . rawurlencode($token);
}
```

Delete the whole `api_log_activity()` and `api_hr_company()` functions (from the comment “Satu baris log aktivitas konsol HR” through the closing brace before EOF).

- [ ] **Step 4: Update `includes/config/app.php`**

Change the comment and regex:

```php
    // Normalisasi: jika dipanggil dari subfolder role (/mahasiswa, /admin), hapus akhiran tersebut
    $clean_base = preg_replace('#/(mahasiswa|admin)$#', '', $script_dir);
```

- [ ] **Step 5: Clean comments in `public/api/account.php` and `public/api/password.php`**

`account.php` header becomes:

```php
// Identitas akun milik pemanggil sendiri, untuk semua role.
//
// Dipakai layar Pengaturan admin yang sebelumnya menulis ke
// localStorage 'sb.admin.profile' — perubahan di situ tidak pernah sampai
// ke server, jadi nama di header tetap nama lama setelah "disimpan".
```

`password.php` line 3 becomes:

```php
// Ganti password akun yang sedang login (mahasiswa maupun admin).
```

- [ ] **Step 6: Update layouts and login**

`includes/layouts/head.php` line 3 becomes `// Head HTML bersama untuk seluruh role (Public, Mahasiswa, Admin)`. Replace the stylesheet branch with:

```php
    <?php if ($role === 'admin'): ?>
      <link rel="stylesheet" href="<?= asset('css/admin.css') ?>" />
    <?php endif; ?>
```

`includes/layouts/public_header.php` line 33 becomes:

```php
      <a class="btn btn-accent btn-44" href="<?= e(base_url(get_user_role() === 'admin' ? 'admin/index.php' : 'mahasiswa/dashboard.php')) ?>">Dashboard</a>
```

`public/login.php` header comment becomes `// Halaman Login (Mahasiswa, Admin)`. Replace the “already logged in” block with:

```php
// Sudah login -> langsung ke dashboard role-nya
if (is_logged_in()) {
    if (!auth_role_is_valid(get_user_role())) {
        logout();
    } else {
        redirect_to_dashboard(get_user_role());
    }
}
```

Remove the HR demo button line:

```php
<button type="button" class="btn btn-secondary btn-sm" onclick="setDemo('hr@techindonesia.com', 'password123')">HR / Recruiter</button>
```

- [ ] **Step 7: Static verification**

Run:

```bash
php -l includes/auth/auth.php
php -l includes/auth/middleware.php
php -l includes/api/bootstrap.php
php -l includes/config/app.php
php -l public/api/account.php
php -l public/api/password.php
php -l includes/layouts/head.php
php -l includes/layouts/public_header.php
php -l public/login.php
grep -nE "case 'hr'|'hr'|api_log_activity|api_hr_company|recruiter\.css|\|hr\)" includes/auth includes/api includes/config includes/layouts/head.php includes/layouts/public_header.php public/login.php public/api/account.php public/api/password.php
```

Expected: all `php -l` pass; grep returns nothing.

---

### Task 3: Delete the HR module files

**Files (delete):**
- `public/hr/index.php`, `roles.php`, `candidates.php`, `activity.php`, `team.php`, `settings.php`
- `public/admin/vacancies.php`
- `public/js/pages/admin-vacancies.js`
- `public/api/hr/company.php`, `team.php`, `roles.php`, `candidates.php`, `activity.php`, `stats.php`
- `public/api/admin/vacancies.php`
- `includes/layouts/hr_header.php`, `hr_sidebar.php`, `hr_footer.php`
- `public/css/recruiter.css`
- `public/js/pages/recruiter.js`, `recruiter-roles.js`, `recruiter-candidates.js`, `recruiter-activity.js`, `recruiter-team.js`, `recruiter-settings.js`
- `tools/create_hr.php`

- [ ] **Step 1: Delete the files**

Use `rm` on exactly the paths above. Do not delete any other file. After deletion, remove now-empty directories `public/hr` and `public/api/hr` if they are empty.

- [ ] **Step 2: Verify**

```bash
for f in public/hr public/api/hr includes/layouts/hr_header.php public/css/recruiter.css tools/create_hr.php public/api/admin/vacancies.php; do test -e "$f" && echo "STILL EXISTS: $f"; done
```

Expected: no output.

---

### Task 4: Admin console simplification and client gateway cleanup

**Files:**
- Modify: `public/api/admin/stats.php`
- Modify: `public/api/admin/admins.php`
- Modify: `public/admin/admins.php`
- Modify: `public/js/pages/admin-admins.js`
- Modify: `public/js/pages/admin.js`
- Modify: `includes/layouts/admin_sidebar.php`
- Modify: `public/js/ui.js`
- Modify: `public/js/api.js`

**Interfaces:**
- Produces `API.getAdmins()` reading `{ admins: [...] }` from `/api/admin/admins.php`.
- Admin API response shapes remove `jobAdmins`, `openVacancies`, `vacancies`, `job_admin`, `company`, and `active_vacancies`.

- [ ] **Step 1: Simplify `public/api/admin/stats.php`**

Change the contract comment to:

```php
//   GET -> { users, activeUsers, suspendedUsers, platformAdmins,
//           skills, careers, mappings, publishedContent, content,
//           recent[], accounts[] }
```

Remove these stats:

```php
'jobAdmins'     => $scalar("SELECT COUNT(*) FROM users WHERE role = 'hr'"),
'openVacancies' => $scalar("SELECT COUNT(*) FROM vacancies WHERE status = 'open'"),
'vacancies'     => $scalar('SELECT COUNT(*) FROM vacancies'),
```

Remove the `Lowongan` union branch:

```sql
UNION ALL
(SELECT 'Lowongan', title || ' dibuka', created_at FROM vacancies ORDER BY created_at DESC LIMIT 6)
```

Change the `accounts` query and mapping to:

```php
$accounts = $pdo->query(
    "SELECT name, status, updated_at
     FROM users
     WHERE role = 'admin'
     ORDER BY updated_at DESC
     LIMIT 5"
)->fetchAll();

$accountRows = [];
foreach ($accounts as $a) {
    $accountRows[] = [
        'actor'  => $a['name'] . ' · Admin',
        'detail' => $a['status'] === 'active' ? 'Akun aktif' : 'Status akun: ' . $a['status'],
        'time'   => $a['updated_at'],
    ];
}
```

- [ ] **Step 2: Simplify `public/api/admin/admins.php`**

Replace the header with:

```php
// Daftar administrator SkillBridge (akun role 'admin').
//
//   GET    -> [{ id, name, email, role:'skillbridge_admin', status, created_at }]
//   PATCH  -> { id, status }
//   DELETE -> { id }
```

Replace the GET query with:

```php
$rows = $pdo->query(
    "SELECT id, name, email, status, created_at
     FROM users
     WHERE role = 'admin'
     ORDER BY id"
)->fetchAll();

foreach ($rows as &$r) {
    $r['id']   = (int) $r['id'];
    $r['role'] = 'skillbridge_admin';
}
unset($r);
```

Change the PATCH statement to:

```php
$stmt = $pdo->prepare("UPDATE users SET status = :s WHERE id = :id AND role = 'admin'");
```

Replace the DELETE body with:

```php
$pdo->beginTransaction();
$stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'admin'");
$stmt->execute([':id' => $id]);
if ($stmt->rowCount() === 0) {
    $pdo->rollBack();
    api_error('Akun administrator itu tidak ada.', 404);
}
$pdo->commit();
api_out(['ok' => true, 'id' => $id]);
```

- [ ] **Step 3: Simplify `public/admin/admins.php`**

Change the description to:

```php
<p class="admin-page-desc">Kelola akun administrator SkillBridge yang memiliki akses penuh ke konsol platform.</p>
```

Remove the entire `<select id="admin-role-filter">` block.

Replace the table head with:

```html
<tr>
  <th>Administrator</th>
  <th>Peran</th>
  <th>Status</th>
  <th>Dibuat</th>
  <th class="admin-text-end">Aksi</th>
</tr>
```

Change the loading row to `data-loading-colspan="5"`.

- [ ] **Step 4: Rewrite `public/js/pages/admin-admins.js`**

Replace the file with:

```js
// admin-admins.js - manajemen akun administrator SkillBridge

(async () => {
  const { el, clear } = UI;

  const ROLE_LABEL = {
    skillbridge_admin: "Admin SkillBridge",
  };

  function initials(name) {
    return name.split(" ").map((w) => w[0]).slice(0, 2).join("").toUpperCase();
  }

  let allAdmins = [];

  async function load() {
    try {
      allAdmins = await API.getAdmins();
    } catch {
      allAdmins = [];
    }
    applyFilter();
  }

  function applyFilter() {
    const q = (document.getElementById("admin-search").value || "").toLowerCase();
    const list = allAdmins.filter(
      (a) => !q || a.name.toLowerCase().includes(q) || a.email.toLowerCase().includes(q)
    );
    render(list);
  }

  function render(list) {
    const body = document.getElementById("admins-table-body");
    clear(body);

    if (list.length === 0) {
      body.append(el("tr", {}, [el("td", { colspan: "5", class: "admin-empty", text: "Tidak ada administrator yang cocok." })]));
      return;
    }

    for (const a of list) {
      const active = a.status === "active";
      body.append(
        el("tr", {}, [
          el("td", {}, [
            el("div", { class: "admin-user-cell" }, [
              el("div", { class: "admin-user-avatar-sm", text: initials(a.name) }),
              el("div", {}, [
                el("p", { class: "admin-user-name", text: a.name }),
                el("p", { class: "admin-user-email", text: a.email }),
              ]),
            ]),
          ]),
          el("td", {}, [el("span", { class: "admin-badge-pill", text: ROLE_LABEL[a.role] || a.role })]),
          el("td", {}, [
            el("span", { class: "admin-badge-pill", text: active ? "Aktif" : "Non-aktif" }),
          ]),
          el("td", { text: UI.formatDate(a.created_at) }),
          el("td", { class: "admin-text-end" }, [
            el("div", { class: "table-actions admin-actions" }, [
              el("button", {
                type: "button",
                class: "admin-icon-btn",
                "data-action": "status", "data-id": String(a.id), "aria-label": `Ubah status ${a.name}`,
                title: active ? "Nonaktifkan" : "Aktifkan",
              }, [
                el("svg", { width: "14", height: "14", viewBox: "0 0 16 16", fill: "none", stroke: "currentColor", "stroke-width": "1.5", "stroke-linecap": "round", "stroke-linejoin": "round" }, [
                  el("path", { d: "M2 8a6 6 0 0111.2-3M14 8a6 6 0 01-11.2 3" }),
                  el("path", { d: "M13.5 2v3h-3M2.5 14v-3h3" }),
                ]),
              ]),
              el("button", {
                type: "button",
                class: "admin-icon-btn admin-icon-btn-danger",
                "data-action": "delete", "data-id": String(a.id), "aria-label": `Hapus ${a.name}`,
                title: "Hapus",
              }, [
                el("svg", { width: "14", height: "14", viewBox: "0 0 16 16", fill: "none", stroke: "currentColor", "stroke-width": "1.5", "stroke-linecap": "round", "stroke-linejoin": "round" }, [
                  el("path", { d: "M3 5h10M6 5V3.5a1 1 0 011-1h2a1 1 0 011 1V5M5.5 5l.5 9h4l.5-9" }),
                ]),
              ]),
            ]),
          ]),
        ])
      );
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    load();

    document.getElementById("admins-table-body")?.addEventListener("click", async (e) => {
      const btn = e.target.closest("button[data-action]");
      if (!btn) return;
      const a = allAdmins.find((x) => x.id === Number(btn.dataset.id));
      if (!a) return;
      try {
        if (btn.dataset.action === "status") {
          const next = a.status === "active" ? "inactive" : "active";
          await API.setAdminRecordStatus("admins", a.id, next);
          UI.toast(`Status ${a.name} diubah`);
        } else if (btn.dataset.action === "delete") {
          if (!confirm(`Hapus administrator ${a.name}?`)) return;
          await API.removeAdminRecord("admins", a.id);
          UI.toast("Administrator dihapus");
        }
        await load();
      } catch (err) {
        UI.toast(err.message, "error");
      }
    });

    document.getElementById("admin-search").addEventListener("input", applyFilter);
  });
})();
```

- [ ] **Step 5: Update `public/js/pages/admin.js`**

Replace the Administrator and mapping cards with:

```js
    {
      label: "Administrator",
      value: (s) => s.platformAdmins,
      context: () => "Admin SkillBridge",
    },
    {
      label: "Relasi mapping",
      value: (s) => s.mappings,
      context: (s) => `${s.publishedContent} konten terbit`,
    },
```

- [ ] **Step 6: Update `includes/layouts/admin_sidebar.php`**

Group 2 condition:

```php
<?= in_array($admin_page, ['users', 'admins', 'search']) ? 'open' : '' ?>
```

Add after the Manajemen Administrator link:

```php
<a href="<?= e(base_url('admin/search.php')) ?>" class="admin-nav-item <?= ($admin_page === 'search') ? 'active' : '' ?>" data-nav="search">Cari Profil</a>
```

Group 3 condition becomes:

```php
<?= in_array($admin_page, ['skills', 'careers', 'mapping']) ? 'open' : '' ?>
```

Delete the Manajemen Lowongan link.

- [ ] **Step 7: Remove `.rec-sidebar` from `public/js/ui.js`**

Change both occurrences:

```js
const sidebar = document.querySelector(".rec-sidebar, .admin-sidebar");
```

to:

```js
const sidebar = document.querySelector(".admin-sidebar");
```

and:

```js
const openSidebar = document.querySelector(".rec-sidebar.is-open, .admin-sidebar.is-open");
```

to:

```js
const openSidebar = document.querySelector(".admin-sidebar.is-open");
```

- [ ] **Step 8: Update `public/js/api.js`**

1. Change the `LEGACY_KEYS` comment to:

```js
  // Kunci localStorage peninggalan mode dummy. Domain TIDAK dibaca dari sini
  // lagi; daftar ini hanya untuk membersihkan sisa data lama satu kali.
  // Kunci sb.rec.* dipertahankan khusus sebagai pembersih konsol HR yang
  // sudah dihapus dan tidak boleh dipakai fitur baru.
```

2. Rename `getJobAdmins()` to:

```js
  async function getAdmins() {
    return (await request("GET", "/api/admin/admins.php")).admins;
  }
```

3. Delete `getVacancies()` and `addVacancy()`.

4. Remove `jobs` and `vacancies` from `ADMIN_ENDPOINT`; keep:

```js
  const ADMIN_ENDPOINT = {
    users: "/api/admin/users.php",
    admins: "/api/admin/admins.php",
    skills: "/api/admin/skills.php",
    careers: "/api/admin/careers.php",
    content: "/api/admin/content.php",
    mapping: "/api/admin/mapping.php",
  };
```

5. Change the `getAccount()` comment to `// Identitas akun untuk layar pengaturan admin.`

6. Delete the entire `// ---- Recruiter / Perusahaan ----` block from `getRecruiterCompany()` through `getRecruiterStats()`.

7. Update the export object: replace `getJobAdmins` with `getAdmins`; remove `getVacancies` and `addVacancy`; remove all `recruiter` exports.

- [ ] **Step 9: Static verification**

```bash
php -l public/api/admin/stats.php
php -l public/api/admin/admins.php
php -l public/admin/admins.php
node --check public/js/pages/admin-admins.js
node --check public/js/pages/admin.js
node --check public/js/ui.js
node --check public/js/api.js
grep -nE 'jobAdmins|getJobAdmins|getVacancies|addVacancy|job_admin|active_vacancies|company_recruiters|vacancies|recruiter|Recruiter' public/api/admin/stats.php public/api/admin/admins.php public/admin/admins.php public/js/pages/admin-admins.js public/js/pages/admin.js public/js/ui.js public/js/api.js includes/layouts/admin_sidebar.php
```

Expected: all syntax checks pass. Grep may only match the intentional legacy-cleanup comment/keys in `public/js/api.js` (`sb.rec.*`) and nothing else.

---

### Task 5: Search, public profile, and public photo endpoints

**Files:**
- Create: `public/api/search.php`
- Create: `public/api/public-profile.php`
- Create: `public/api/public-photo.php`
- Modify: `public/api/profile.php`
- Modify: `public/api/profile-photo.php`

**Interfaces:**
- `GET /api/search.php?q=` → `{ ok, query, total, truncated, profiles[] }`; each profile has `token, name, initials, photo_url, profile_url, institution, program, semester, location, target_role, skills[], evidence_count`.
- `GET /api/public-profile.php?token=` → `{ ok, profile: { name, initials, photo_url, institution, program, semester, location, bio, member_since, target_role, skills[], evidence_count } }`.
- `GET /api/public-photo.php?token=` → image bytes or 404 JSON.
- `api_photo_path()` is now shared from `bootstrap.php`.

- [ ] **Step 1: Create `public/api/search.php`**

```php
<?php
// public/api/search.php
// Pencarian profil publik untuk semua user yang login.
//
//   GET ?q=<query> -> { query, total, truncated, profiles[] }
//
// Hanya membaca v_public_profiles (aktif + Public). Respons tidak pernah
// memuat email, users.id, atau path file.

require_once __DIR__ . '/../../includes/api/bootstrap.php';

if (api_method() !== 'GET') {
    api_error('Method tidak didukung.', 405);
}

api_guard();
$pdo = api_pdo();

$query = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($query) < 2) {
    api_error('Kata kunci minimal 2 karakter.', 422);
}
if (mb_strlen($query) > 100) {
    api_error('Kata kunci maksimal 100 karakter.', 422);
}

try {
    // Escape wildcard agar input pengguna diperlakukan literal.
    $needle = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query);
    $like   = '%' . $needle . '%';

    $sql = "SELECT p.id, p.public_token, p.name, p.institution, p.program,
                   p.semester, p.location, p.target_role, p.photo_path,
                   COUNT(*) OVER() AS total_count
            FROM v_public_profiles p
            WHERE p.name ILIKE :q1 ESCAPE '\\'
               OR p.institution ILIKE :q2 ESCAPE '\\'
               OR p.program ILIKE :q3 ESCAPE '\\'
               OR p.location ILIKE :q4 ESCAPE '\\'
               OR p.target_role ILIKE :q5 ESCAPE '\\'
               OR EXISTS (
                    SELECT 1
                    FROM user_skills us
                    JOIN skills s ON s.id = us.skill_id
                    WHERE us.user_id = p.id
                      AND us.status = 'confirmed'
                      AND s.name ILIKE :q6 ESCAPE '\\'
               )
            ORDER BY p.name
            LIMIT 30";
    $stmt = $pdo->prepare($sql);
    for ($i = 1; $i <= 6; $i++) {
        $stmt->bindValue(':q' . $i, $like, PDO::PARAM_STR);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $total = $rows ? (int) $rows[0]['total_count'] : 0;
    $ids = [];
    $profiles = [];
    foreach ($rows as $row) {
        $token = (string) $row['public_token'];
        $uid   = (int) $row['id'];
        $ids[] = $uid;
        $profiles[] = [
            'token'          => $token,
            'name'           => $row['name'],
            'initials'       => make_initials($row['name']),
            'photo_url'      => $row['photo_path'] !== null && api_photo_path($uid) !== ''
                ? api_public_photo_url($token) : null,
            'profile_url'    => api_public_profile_url($token),
            'institution'    => $row['institution'],
            'program'        => $row['program'],
            'semester'       => $row['semester'],
            'location'       => $row['location'],
            'target_role'    => $row['target_role'],
            'skills'         => [],
            'evidence_count' => 0,
        ];
    }

    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));

        $skillStmt = $pdo->prepare(
            "SELECT us.user_id, s.name
             FROM user_skills us
             JOIN skills s ON s.id = us.skill_id
             WHERE us.user_id IN ({$in})
               AND us.status = 'confirmed'
             ORDER BY s.name"
        );
        foreach ($ids as $i => $uid) {
            $skillStmt->bindValue($i + 1, $uid, PDO::PARAM_INT);
        }
        $skillStmt->execute();
        $skillsByUser = [];
        foreach ($skillStmt as $skill) {
            $skillsByUser[(int) $skill['user_id']][] = $skill['name'];
        }

        $evidenceStmt = $pdo->prepare(
            "SELECT user_id, COUNT(*) AS total
             FROM evidence
             WHERE user_id IN ({$in})
             GROUP BY user_id"
        );
        foreach ($ids as $i => $uid) {
            $evidenceStmt->bindValue($i + 1, $uid, PDO::PARAM_INT);
        }
        $evidenceStmt->execute();
        $evidenceByUser = [];
        foreach ($evidenceStmt as $item) {
            $evidenceByUser[(int) $item['user_id']] = (int) $item['total'];
        }

        foreach ($profiles as $i => $profile) {
            $uid = $ids[$i];
            $profiles[$i]['skills'] = $skillsByUser[$uid] ?? [];
            $profiles[$i]['evidence_count'] = $evidenceByUser[$uid] ?? 0;
        }
    }

    api_out([
        'ok'        => true,
        'query'     => $query,
        'total'     => $total,
        'truncated' => $total > 30,
        'profiles'  => $profiles,
    ]);
} catch (PDOException $e) {
    error_log('api/search.php: ' . $e->getMessage());
    api_error('Gagal mencari profil.', 500);
}
```

- [ ] **Step 2: Create `public/api/public-profile.php`**

```php
<?php
// public/api/public-profile.php
// Profil publik berbasis token. Tidak perlu login.
//
//   GET ?token=<uuid> -> { profile }
//
// Token invalid, profil private, akun nonaktif, dan profil yang tidak ada
// semuanya menghasilkan 404 yang sama. Respons tidak memuat email, id,
// token, status akun, atau path file.

require_once __DIR__ . '/../../includes/api/bootstrap.php';

if (api_method() !== 'GET') {
    api_error('Method tidak didukung.', 405);
}

$token = trim((string) ($_GET['token'] ?? ''));
if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $token)) {
    api_error('Profil tidak ditemukan atau tidak lagi publik.', 404);
}

$pdo = api_pdo();

try {
    $stmt = $pdo->prepare(
        'SELECT id, name, public_token, institution, program, semester,
                location, bio, photo_path, member_since, target_role
         FROM v_public_profiles
         WHERE public_token = :t
         LIMIT 1'
    );
    $stmt->execute([':t' => $token]);
    $row = $stmt->fetch();
    if (!$row) {
        api_error('Profil tidak ditemukan atau tidak lagi publik.', 404);
    }

    $uid = (int) $row['id'];

    $skillStmt = $pdo->prepare(
        "SELECT skill_name, skill_category, evidence_count
         FROM v_user_skill_evidence
         WHERE user_id = :u AND status = 'confirmed'
         ORDER BY skill_category NULLS LAST, skill_name"
    );
    $skillStmt->execute([':u' => $uid]);
    $skills = [];
    foreach ($skillStmt as $skill) {
        $skills[] = [
            'name'           => $skill['skill_name'],
            'category'       => $skill['skill_category'],
            'evidence_count' => (int) $skill['evidence_count'],
        ];
    }

    $evidenceStmt = $pdo->prepare('SELECT COUNT(*) FROM evidence WHERE user_id = :u');
    $evidenceStmt->execute([':u' => $uid]);

    $tokenValue = (string) $row['public_token'];
    api_out([
        'ok' => true,
        'profile' => [
            'name'           => $row['name'],
            'initials'       => make_initials($row['name']),
            'photo_url'      => $row['photo_path'] !== null && api_photo_path($uid) !== ''
                ? api_public_photo_url($tokenValue) : null,
            'institution'    => $row['institution'],
            'program'        => $row['program'],
            'semester'       => $row['semester'],
            'location'       => $row['location'],
            'bio'            => $row['bio'],
            'member_since'   => $row['member_since'],
            'target_role'    => $row['target_role'],
            'skills'         => $skills,
            'evidence_count' => (int) $evidenceStmt->fetchColumn(),
        ],
    ]);
} catch (PDOException $e) {
    error_log('api/public-profile.php: ' . $e->getMessage());
    api_error('Gagal memuat profil publik.', 500);
}
```

- [ ] **Step 3: Create `public/api/public-photo.php`**

```php
<?php
// public/api/public-photo.php
// Foto profil publik berbasis token. Tidak perlu login.
//
//   GET ?token=<uuid> -> bytes gambar
//
// Hanya menyalurkan foto bila token ada di v_public_profiles dan file
// benar-benar ada di storage/. Path file diturunkan dari user id hasil
// validasi token, tidak pernah dari input klien.

require_once __DIR__ . '/../../includes/api/bootstrap.php';

if (api_method() !== 'GET') {
    api_error('Method tidak didukung.', 405);
}

$token = trim((string) ($_GET['token'] ?? ''));
if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $token)) {
    api_error('Foto tidak ditemukan.', 404);
}

$pdo = api_pdo();

try {
    $stmt = $pdo->prepare('SELECT id FROM v_public_profiles WHERE public_token = :t LIMIT 1');
    $stmt->execute([':t' => $token]);
    $uid = $stmt->fetchColumn();
    if ($uid === false) {
        api_error('Foto tidak ditemukan.', 404);
    }

    $path = api_photo_path((int) $uid);
    if ($path === '') {
        api_error('Foto tidak ditemukan.', 404);
    }

    $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($path);
    if (!in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
        api_error('Foto tidak ditemukan.', 404);
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
} catch (PDOException $e) {
    error_log('api/public-photo.php: ' . $e->getMessage());
    api_error('Gagal memuat foto.', 500);
}
```

- [ ] **Step 4: Rotate the token in `public/api/profile.php`**

Replace the whole `if ($visibility !== null)` block with:

```php
            if ($visibility !== null) {
                $isPublic = $visibility === 'Public';

                $currentStmt = $pdo->prepare('SELECT is_public FROM student_profiles WHERE user_id = :u');
                $currentStmt->execute([':u' => $uid]);
                $wasPublic = (bool) $currentStmt->fetchColumn();

                if ($isPublic && !$wasPublic) {
                    // Private -> Public: rotasi token agar tautan lama mati.
                    $upd = $pdo->prepare(
                        'UPDATE student_profiles
                            SET is_public = TRUE,
                                public_token = gen_random_uuid()
                          WHERE user_id = :u'
                    );
                } elseif ($isPublic) {
                    $upd = $pdo->prepare(
                        'UPDATE student_profiles
                            SET is_public = TRUE,
                                public_token = COALESCE(public_token, gen_random_uuid())
                          WHERE user_id = :u'
                    );
                } else {
                    $upd = $pdo->prepare('UPDATE student_profiles SET is_public = FALSE WHERE user_id = :u');
                }
                $upd->execute([':u' => $uid]);
            }
```

- [ ] **Step 5: Refactor `public/api/profile-photo.php` to use the shared helper**

Delete the local function:

```php
/** Path absolut foto user. Nama file diturunkan dari id, bukan dari input. */
function api_photo_path(int $uid): string {
    $dir = dirname(__DIR__, 2) . '/storage';
    foreach (['png', 'jpg', 'webp'] as $ext) {
        $candidate = "{$dir}/avatar_{$uid}.{$ext}";
        if (is_file($candidate)) {
            return $candidate;
        }
    }
    return '';
}
```

No other change; `api_photo_path()` now comes from `bootstrap.php`.

- [ ] **Step 6: Static verification**

```bash
php -l public/api/search.php
php -l public/api/public-profile.php
php -l public/api/public-photo.php
php -l public/api/profile.php
php -l public/api/profile-photo.php
grep -nE "'email'|password_hash|users\.id" public/api/search.php public/api/public-profile.php public/api/public-photo.php
```

Expected: syntax checks pass; grep returns nothing.

---

### Task 6: Frontend search pages, public profile page, and API gateway methods

**Files:**
- Create: `public/mahasiswa/search.php`
- Create: `public/admin/search.php`
- Create: `public/profile.php`
- Create: `public/js/pages/search.js`
- Create: `public/js/pages/public-profile.js`
- Modify: `public/js/api.js`
- Modify: `public/js/pages/profile.js`

**Interfaces:**
- Produces `API.searchProfiles(query)` and `API.getPublicProfile(token)`.
- Produces page contracts: `<body data-search-page="mahasiswa|admin">`, `#people-search-form`, `#people-search-input`, `#people-search-status`, `#people-search-results`, `#public-profile-root`.

- [ ] **Step 1: Create `public/mahasiswa/search.php`**

```php
<?php
// public/mahasiswa/search.php
// Pencarian profil publik · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('mahasiswa');

$page_title = 'Find People · SkillBridge';
$role = 'mahasiswa';
$path_prefix = '../';
$nav_active = '';
$body_attrs = 'data-nav="" data-search-page="mahasiswa"';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/mahasiswa_header.php';
?>

<main class="container">
  <div class="page-head">
    <div class="page-head-left" style="max-width: 760px">
      <p class="eyebrow">Public directory</p>
      <h1>Find people</h1>
      <p style="margin-top: 8px; font-size: 14px; line-height: 1.45; color: var(--text-muted)">
        Search students who chose to share their public profile. Only public, active profiles appear here.
      </p>
    </div>
  </div>

  <section class="people-search-panel" aria-labelledby="people-search-title">
    <h2 class="visually-hidden" id="people-search-title">Search public profiles</h2>
    <form class="people-search-form" role="search" id="people-search-form">
      <label class="visually-hidden" for="people-search-input">Search by name, school, program, location, career target, or skill</label>
      <input type="search" id="people-search-input" name="q" maxlength="100" autocomplete="off" placeholder="Search by name, school, program, location, career, or skill..." />
      <button type="submit" class="btn btn-accent btn-44">Search</button>
    </form>
    <p class="people-search-status" id="people-search-status" aria-live="polite"></p>
    <div class="people-grid" id="people-search-results"></div>
  </section>
</main>

<?php require_once __DIR__ . '/../../includes/layouts/mahasiswa_footer.php'; ?>

<script src="<?= e(asset('js/ui.js')) ?>"></script>
<script src="<?= e(asset('js/api.js')) ?>"></script>
<script src="<?= e(asset('js/pages/search.js')) ?>"></script>
</body></html>
```

- [ ] **Step 2: Create `public/admin/search.php`**

```php
<?php
// public/admin/search.php
// Pencarian profil publik · Konsol Admin
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('admin');

$page_title = 'Cari Profil · SkillBridge';
$role = 'admin';
$path_prefix = '../';
$admin_page = 'search';
$body_class = 'admin-body';
$body_attrs = 'data-admin-page="search" data-search-page="admin"';
$page_script = 'search.js';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/admin_sidebar.php';
?>

    <div class="admin-workspace">
      <?php require_once __DIR__ . '/../../includes/layouts/admin_header.php'; ?>

      <main class="admin-page-content">
        <div class="admin-page-heading">
          <div>
            <p class="admin-eyebrow">Direktori publik</p>
            <h1 class="admin-page-title">Cari Profil</h1>
            <p class="admin-page-desc">Temukan mahasiswa yang membuka profilnya untuk publik. Hanya profil aktif dan Public yang ditampilkan.</p>
          </div>
        </div>

        <section class="admin-card people-search-panel" aria-labelledby="people-search-title">
          <h2 class="visually-hidden" id="people-search-title">Pencarian profil publik</h2>
          <form class="people-search-form" role="search" id="people-search-form">
            <label class="visually-hidden" for="people-search-input">Cari berdasarkan nama, kampus, program, lokasi, target karier, atau skill</label>
            <input type="search" id="people-search-input" name="q" maxlength="100" autocomplete="off" placeholder="Cari nama, kampus, program, lokasi, karier, atau skill..." />
            <button type="submit" class="btn btn-accent btn-44">Cari</button>
          </form>
          <p class="people-search-status" id="people-search-status" aria-live="polite"></p>
          <div class="people-grid" id="people-search-results"></div>
        </section>
      </main>

<?php require_once __DIR__ . '/../../includes/layouts/admin_footer.php'; ?>
```

- [ ] **Step 3: Create `public/profile.php`**

```php
<?php
// public/profile.php
// Profil publik berbasis token. Tidak memerlukan login.
require_once __DIR__ . '/../includes/auth/auth.php';

$page_title = 'Profil Publik · SkillBridge';
$role = 'public';
$path_prefix = '';
$body_class = 'public-profile-body';
$extra_head = '<meta name="robots" content="noindex, nofollow">' . "\n"
    . '<meta name="referrer" content="no-referrer">';

$viewer_role = get_user_role();
if ($viewer_role === 'admin') {
    $back_url = base_url('admin/search.php');
    $back_label = 'Kembali ke pencarian';
} elseif ($viewer_role === 'mahasiswa') {
    $back_url = base_url('mahasiswa/search.php');
    $back_label = 'Kembali ke pencarian';
} else {
    $back_url = base_url('index.php');
    $back_label = 'Kembali ke beranda';
}

require_once __DIR__ . '/../includes/layouts/head.php';
require_once __DIR__ . '/../includes/layouts/public_header.php';
?>

<main class="container public-profile-main">
  <div class="public-profile">
    <a class="public-profile-back" href="<?= e($back_url) ?>">← <?= e($back_label) ?></a>
    <div id="public-profile-root" data-loading="text">
      <p class="public-profile-empty">Memuat profil...</p>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/layouts/public_footer.php'; ?>

<script src="<?= e(asset('js/ui.js')) ?>"></script>
<script src="<?= e(asset('js/api.js')) ?>"></script>
<script src="<?= e(asset('js/pages/public-profile.js')) ?>"></script>
</body></html>
```

- [ ] **Step 4: Create `public/js/pages/search.js`**

```js
// search.js - direktori profil publik untuk mahasiswa dan admin.
(function () {
  const page = document.body.dataset.searchPage || "mahasiswa";
  const isAdmin = page === "admin";
  const copy = isAdmin
    ? {
        hint: "Ketik minimal 2 karakter untuk mencari profil publik.",
        loading: "Mencari profil...",
        empty: (q) => `Tidak ada profil publik yang cocok dengan “${q}”.`,
        results: (n) => `${n} profil publik ditemukan.`,
        truncated: "Menampilkan 30 hasil pertama. Persempit kata kunci untuk hasil yang lebih spesifik.",
        error: "Pencarian gagal. Coba lagi.",
        target: "Target karier",
        evidence: (n) => `${n} evidence`,
      }
    : {
        hint: "Type at least 2 characters to search public profiles.",
        loading: "Searching profiles...",
        empty: (q) => `No public profiles match “${q}”.`,
        results: (n) => `${n} public profile${n === 1 ? "" : "s"} found.`,
        truncated: "Showing the first 30 results. Narrow your search for more specific matches.",
        error: "Search failed. Please try again.",
        target: "Career target",
        evidence: (n) => `${n} evidence item${n === 1 ? "" : "s"}`,
      };

  const form = document.getElementById("people-search-form");
  const input = document.getElementById("people-search-input");
  const results = document.getElementById("people-search-results");
  const status = document.getElementById("people-search-status");
  if (!form || !input || !results || !status) return;

  const { el, clear, safeUrl } = UI;
  let debounceTimer = null;
  let requestId = 0;

  function showState(message, kind) {
    clear(results);
    results.append(el("div", { class: `people-state people-state--${kind || "hint"}`, text: message }));
  }

  function renderCard(profile) {
    const link = safeUrl(profile.profile_url);
    if (!link) return null;

    const photo = profile.photo_url ? safeUrl(profile.photo_url) : null;
    const avatar = photo
      ? el("img", { class: "person-card-avatar", src: photo, alt: "", loading: "lazy" })
      : el("span", { class: "person-card-avatar person-card-avatar--initials", text: profile.initials || "SB", "aria-hidden": "true" });

    const meta = [profile.institution, profile.program, profile.semester ? `Semester ${profile.semester}` : null]
      .filter(Boolean)
      .join(" · ");

    const skillNames = profile.skills || [];
    const skillNodes = skillNames.slice(0, 4).map((name) => el("span", { class: "person-skill", text: name }));
    if (skillNames.length > 4) {
      skillNodes.push(el("span", { class: "person-skill person-skill--more", text: `+${skillNames.length - 4}` }));
    }

    return el("a", { class: "person-card", href: link }, [
      el("div", { class: "person-card-head" }, [
        avatar,
        el("div", { class: "person-card-identity" }, [
          el("h3", { class: "person-card-name", text: profile.name }),
          el("p", { class: "person-card-meta", text: meta || "—" }),
        ]),
      ]),
      profile.location ? el("p", { class: "person-card-location", text: profile.location }) : null,
      profile.target_role ? el("p", { class: "person-card-target", text: `${copy.target}: ${profile.target_role}` }) : null,
      skillNodes.length ? el("div", { class: "person-card-skills" }, skillNodes) : null,
      el("p", { class: "person-card-evidence", text: copy.evidence(profile.evidence_count || 0) }),
    ]);
  }

  async function runSearch(value, options = {}) {
    const q = value.trim();

    if (options.updateUrl !== false) {
      const url = new URL(window.location.href);
      if (q) url.searchParams.set("q", q);
      else url.searchParams.delete("q");
      window.history.replaceState({}, "", url);
    }

    if (q.length < 2) {
      status.textContent = "";
      showState(copy.hint);
      return;
    }

    const id = ++requestId;
    status.textContent = copy.loading;
    showState(copy.loading, "loading");

    try {
      const data = await API.searchProfiles(q);
      if (id !== requestId) return;

      clear(results);
      if (!data.profiles.length) {
        status.textContent = "";
        showState(copy.empty(q));
        return;
      }

      status.textContent = copy.results(data.total);
      for (const profile of data.profiles) {
        const card = renderCard(profile);
        if (card) results.append(card);
      }
      if (data.truncated) {
        results.append(el("p", { class: "people-search-note", text: copy.truncated }));
      }
    } catch (err) {
      if (id !== requestId) return;
      status.textContent = "";
      showState(err instanceof API.ApiError ? err.message : copy.error, "error");
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    const initial = new URLSearchParams(window.location.search).get("q") || "";
    input.value = initial;
    runSearch(initial, { updateUrl: false });

    form.addEventListener("submit", (event) => {
      event.preventDefault();
      clearTimeout(debounceTimer);
      runSearch(input.value);
    });

    input.addEventListener("input", () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => runSearch(input.value), 250);
    });
  });
})();
```

- [ ] **Step 5: Create `public/js/pages/public-profile.js`**

```js
// public-profile.js - render halaman profil publik berbasis token.

(async () => {
  const { el, clear, safeUrl, formatDate } = UI;
  const root = document.getElementById("public-profile-root");
  if (!root) return;

  const token = new URLSearchParams(window.location.search).get("token") || "";

  function showError(message) {
    clear(root);
    root.classList.remove("sb-is-loading");
    root.removeAttribute("aria-busy");
    root.append(
      el("div", { class: "public-profile-error", role: "status" }, [
        el("h1", { text: "Profil tidak tersedia" }),
        el("p", { text: message }),
      ])
    );
  }

  function detail(label, value) {
    if (!value) return null;
    return el("div", { class: "public-profile-detail" }, [
      el("dt", { text: label }),
      el("dd", { text: value }),
    ]);
  }

  function render(profile) {
    clear(root);
    root.classList.remove("sb-is-loading");
    root.removeAttribute("aria-busy");

    const photo = profile.photo_url ? safeUrl(profile.photo_url) : null;
    const avatar = photo
      ? el("img", { class: "public-profile-avatar", src: photo, alt: "", loading: "lazy" })
      : el("div", { class: "public-profile-avatar public-profile-avatar--initials", text: profile.initials || "SB", "aria-hidden": "true" });

    const groups = new Map();
    for (const skill of profile.skills || []) {
      const key = skill.category || "Lainnya";
      if (!groups.has(key)) groups.set(key, []);
      groups.get(key).push(skill);
    }

    const details = el("dl", { class: "public-profile-details" }, [
      detail("Institusi", profile.institution),
      detail("Program studi", profile.program),
      detail("Semester", profile.semester),
      detail("Lokasi", profile.location),
      detail("Bergabung", formatDate(profile.member_since)),
      detail("Total evidence", String(profile.evidence_count || 0)),
    ]);

    const skillSections = [...groups.entries()].map(([category, skills]) =>
      el("div", { class: "public-profile-skill-group" }, [
        el("h3", { text: category }),
        el("div", { class: "public-profile-skill-list" }, skills.map((skill) =>
          el("span", { class: "public-profile-skill" }, [
            el("span", { text: skill.name }),
            el("span", { class: "public-profile-skill-count", text: `${skill.evidence_count} evidence` }),
          ])
        )),
      ])
    );

    root.append(
      el("section", { class: "public-profile-hero" }, [
        avatar,
        el("div", {}, [
          el("h1", { class: "public-profile-name", text: profile.name }),
          profile.target_role ? el("p", { class: "public-profile-role", text: profile.target_role }) : null,
          el("span", { class: "public-profile-badge", text: "Profil Publik" }),
        ]),
      ]),
      profile.bio ? el("section", { class: "public-profile-card" }, [
        el("h2", { text: "Tentang" }),
        el("p", { class: "public-profile-bio", text: profile.bio }),
      ]) : null,
      el("section", { class: "public-profile-card" }, [
        el("h2", { text: "Detail" }),
        details,
      ]),
      el("section", { class: "public-profile-card" }, [
        el("h2", { text: "Keterampilan" }),
        skillSections.length
          ? el("div", {}, skillSections)
          : el("p", { class: "public-profile-empty", text: "Belum ada keterampilan yang dikonfirmasi." }),
      ])
    );
  }

  if (!token) {
    showError("Tautan profil tidak lengkap.");
    return;
  }

  try {
    const data = await API.getPublicProfile(token);
    render(data.profile);
  } catch (err) {
    const notFound = err instanceof API.ApiError && err.status === 404;
    showError(notFound
      ? "Profil tidak ditemukan atau tidak lagi publik."
      : "Profil gagal dimuat. Coba lagi beberapa saat lagi.");
  }
})();
```

- [ ] **Step 6: Add search methods to `public/js/api.js`**

Add before `purgeLegacy();`:

```js
  // ---- Direktori profil publik ----
  async function searchProfiles(query) {
    return request("GET", `/api/search.php?q=${encodeURIComponent(query)}`);
  }

  async function getPublicProfile(token) {
    return request("GET", `/api/public-profile.php?token=${encodeURIComponent(token)}`);
  }
```

Add to the export object:

```js
    searchProfiles,
    getPublicProfile,
```

- [ ] **Step 7: Update visibility copy in `public/js/pages/profile.js`**

Change:

```js
["Public", "HR can find you in the candidate list and view your portfolio."],
```

to:

```js
["Public", "Public profiles can be found by other SkillBridge users and viewed through a shareable link."],
```

Also change the Private description if it still implies HR:

```js
["Private", "Only you can view your portfolio."],
```

- [ ] **Step 8: Static verification**

```bash
php -l public/mahasiswa/search.php
php -l public/admin/search.php
php -l public/profile.php
node --check public/js/pages/search.js
node --check public/js/pages/public-profile.js
node --check public/js/api.js
node --check public/js/pages/profile.js
```

Expected: all pass.

---

### Task 7: Navbar/header forms and CSS

**Files:**
- Modify: `includes/layouts/mahasiswa_header.php`
- Modify: `includes/layouts/admin_header.php`
- Modify: `public/css/style.css`
- Modify: `public/css/admin.css`

**Interfaces:**
- Produces `.navbar-search` in the mahasiswa drawer and `.admin-header-search` in the admin header.
- Produces `.people-search-*`, `.person-card-*`, `.public-profile-*`.
- Removes every `.rec-*` selector and HR-only comment from `style.css`.

- [ ] **Step 1: Add the search form to `includes/layouts/mahasiswa_header.php`**

Inside `.navbar-nav`, after the `foreach` and before `</div>`:

```php
    <form class="navbar-search" role="search" action="<?= e(base_url('mahasiswa/search.php')) ?>" method="get">
      <label class="visually-hidden" for="navbar-search-input">Search public profiles</label>
      <input type="search" id="navbar-search-input" name="q" maxlength="100" autocomplete="off" placeholder="Search people..." />
    </form>
```

- [ ] **Step 2: Add the search form to `includes/layouts/admin_header.php`**

Between `.admin-header-left` and `.admin-header-right`:

```php
  <form class="admin-header-search" role="search" action="<?= e(base_url('admin/search.php')) ?>" method="get">
    <label class="visually-hidden" for="admin-header-search-input">Cari profil publik</label>
    <input type="search" id="admin-header-search-input" name="q" maxlength="100" autocomplete="off" placeholder="Cari profil publik..." />
  </form>
```

- [ ] **Step 3: Add navbar search CSS to `public/css/style.css`**

After `.navbar-nav`:

```css
.navbar-search {
  display: flex;
  align-items: center;
  flex: 0 1 220px;
  min-width: 96px;
  margin-left: auto;
}

.navbar-search input {
  width: 100%;
  height: 34px;
  padding: 0 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--surface);
  color: var(--text-primary);
  font-family: var(--font-family);
  font-size: 13px;
}

.navbar-search input::placeholder {
  color: var(--text-light);
}

.navbar-search input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: var(--shadow-focus);
}
```

In the `@media (min-width: 1024px) and (max-width: 1279px)` block add:

```css
  .navbar-search {
    flex-basis: clamp(96px, 10vw, 140px);
  }
```

In the `@media (max-width: 1023px)` block add:

```css
  .navbar .navbar-search {
    width: 100%;
    flex: 0 0 auto;
    min-width: 0;
    margin: 4px 0 0;
  }

  .navbar .navbar-search input {
    height: 44px;
    font-size: 16px;
  }
```

- [ ] **Step 4: Add people-directory and public-profile CSS to `public/css/style.css`**

Append before the skeleton section:

```css
/* ============ Public people directory ============ */
.people-search-panel {
  display: grid;
  gap: 18px;
  margin-top: 18px;
}

.people-search-form {
  display: flex;
  gap: 10px;
}

.people-search-form input {
  flex: 1;
  min-width: 0;
  height: 44px;
  padding: 0 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--surface);
  color: var(--text-primary);
  font-family: var(--font-family);
  font-size: 14px;
}

.people-search-form input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: var(--shadow-focus);
}

.people-search-status {
  min-height: 20px;
  color: var(--text-muted);
  font-size: 13px;
}

.people-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 16px;
}

.person-card {
  display: grid;
  gap: 10px;
  padding: 18px;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  background: var(--surface);
  color: inherit;
  text-decoration: none;
  box-shadow: var(--shadow-xs);
  transition: border-color var(--duration-fast) var(--ease-spring),
              box-shadow var(--duration-fast) var(--ease-spring),
              transform var(--duration-fast) var(--ease-spring);
}

.person-card:hover {
  border-color: var(--primary);
  box-shadow: var(--shadow-sm);
  transform: translateY(-1px);
}

.person-card:focus-visible {
  outline: none;
  box-shadow: var(--shadow-focus);
}

.person-card-head {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.person-card-avatar {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-round);
  object-fit: cover;
  flex-shrink: 0;
  border: 1px solid var(--border);
  background: var(--surface-subtle);
}

.person-card-avatar--initials {
  display: grid;
  place-items: center;
  color: var(--primary);
  font-size: 15px;
  font-weight: 700;
}

.person-card-identity {
  min-width: 0;
}

.person-card-name {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: var(--text-primary);
  overflow-wrap: anywhere;
}

.person-card-meta,
.person-card-location,
.person-card-target,
.person-card-evidence {
  margin: 0;
  font-size: 12.5px;
  line-height: 1.45;
  color: var(--text-muted);
}

.person-card-target {
  color: var(--primary);
  font-weight: 600;
}

.person-card-skills {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.person-skill {
  display: inline-flex;
  align-items: center;
  height: 24px;
  padding: 0 9px;
  border-radius: var(--radius-round);
  background: var(--surface-subtle);
  border: 1px solid var(--border-subtle);
  color: var(--text-muted);
  font-size: 11.5px;
  font-weight: 600;
}

.person-skill--more {
  color: var(--text-light);
}

.people-state {
  grid-column: 1 / -1;
  padding: 28px 20px;
  border: 1px dashed var(--border);
  border-radius: var(--radius-lg);
  background: var(--surface-subtle);
  color: var(--text-muted);
  font-size: 13.5px;
  text-align: center;
}

.people-state--error {
  border-color: var(--pink-border);
  background: var(--tint-pink);
  color: var(--accent);
}

.people-search-note {
  grid-column: 1 / -1;
  margin: 0;
  color: var(--text-light);
  font-size: 12.5px;
}

/* ============ Public profile (token link) ============ */
.public-profile-main {
  padding-top: 28px;
  padding-bottom: 48px;
}

.public-profile {
  max-width: 860px;
  margin: 0 auto;
  display: grid;
  gap: 18px;
}

.public-profile-back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  width: fit-content;
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
}

.public-profile-back:hover {
  color: var(--primary);
}

.public-profile-hero {
  display: flex;
  align-items: center;
  gap: 18px;
  padding: 24px;
  border: 1px solid var(--border);
  border-radius: var(--radius-xl);
  background: var(--surface);
  box-shadow: var(--shadow-xs);
}

.public-profile-avatar {
  width: 84px;
  height: 84px;
  border-radius: var(--radius-round);
  object-fit: cover;
  flex-shrink: 0;
  border: 1px solid var(--border);
  background: var(--surface-subtle);
}

.public-profile-avatar--initials {
  display: grid;
  place-items: center;
  color: var(--primary);
  font-size: 26px;
  font-weight: 800;
}

.public-profile-name {
  margin: 0;
  font-size: 26px;
  line-height: 1.2;
  color: var(--text-primary);
}

.public-profile-role {
  margin: 6px 0 0;
  color: var(--primary);
  font-size: 14px;
  font-weight: 600;
}

.public-profile-badge {
  display: inline-flex;
  align-items: center;
  height: 24px;
  margin-top: 10px;
  padding: 0 10px;
  border-radius: var(--radius-round);
  background: var(--primary-subtle);
  color: var(--primary);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.02em;
}

.public-profile-card {
  padding: 22px 24px;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  background: var(--surface);
  box-shadow: var(--shadow-xs);
}

.public-profile-card h2 {
  margin: 0 0 14px;
  font-size: 17px;
  color: var(--text-primary);
}

.public-profile-bio {
  margin: 0;
  color: var(--text-muted);
  font-size: 14px;
  line-height: 1.6;
  white-space: pre-line;
}

.public-profile-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 14px 20px;
  margin: 0;
}

.public-profile-detail dt {
  margin: 0 0 3px;
  color: var(--text-light);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.public-profile-detail dd {
  margin: 0;
  color: var(--text-primary);
  font-size: 14px;
  overflow-wrap: anywhere;
}

.public-profile-skill-group + .public-profile-skill-group {
  margin-top: 16px;
}

.public-profile-skill-group h3 {
  margin: 0 0 8px;
  color: var(--text-muted);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.public-profile-skill-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.public-profile-skill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 32px;
  padding: 0 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius-round);
  background: var(--surface-subtle);
  color: var(--text-primary);
  font-size: 13px;
  font-weight: 600;
}

.public-profile-skill-count {
  color: var(--text-light);
  font-size: 11px;
  font-weight: 600;
}

.public-profile-empty {
  margin: 0;
  color: var(--text-muted);
  font-size: 13.5px;
}

.public-profile-error {
  padding: 32px 24px;
  border: 1px dashed var(--border);
  border-radius: var(--radius-lg);
  background: var(--surface);
  text-align: center;
}

.public-profile-error h1 {
  margin: 0 0 8px;
  font-size: 20px;
  color: var(--text-primary);
}

.public-profile-error p {
  margin: 0;
  color: var(--text-muted);
  font-size: 14px;
}

@media (max-width: 639px) {
  .people-search-form {
    flex-direction: column;
  }

  .people-search-form .btn {
    width: 100%;
  }

  .people-grid {
    grid-template-columns: 1fr;
  }

  .public-profile-hero {
    flex-direction: column;
    align-items: flex-start;
    padding: 20px;
  }

  .public-profile-name {
    font-size: 22px;
  }

  .public-profile-card {
    padding: 18px;
  }
}
```

- [ ] **Step 5: Remove `.rec-*` CSS from `public/css/style.css`**

Delete every rule whose selector contains `.rec-`. The affected regions are:

1. The `@media (max-width: 1023px)` block at the top of the responsive section: keep `.admin-header .sidebar-toggle`; delete `.rec-header .sidebar-toggle`, the `.rec-page-content .rec-pager .rec-pager-link` rule, and the `.rec-page-content .rec-pager .rec-pager-page` rule.
2. Section `11b` (`@media (max-width: 1399px)`): keep every `.admin-*` selector and declaration; remove `.rec-*` selectors from comma lists and delete rules that only target `.rec-*`. Change the section title to `/* ============ 11b. Tabel data admin sebagai kartu di bawah 1400px ============ */` and update its comment to describe only admin tables.
3. Section `11c`: change the title to `/* ============ 11c. Kerangka konsol admin di ponsel ============ */`. Keep the `.admin-workspace .admin-header`, `.admin-header-left`, `.admin-activity-grid`, `.btn`, `.btn-sm`, `.btn-icon`, and `#badge-strong` rules. Delete every `.rec-*`-only rule, including the entire “Tabel div dasbor HR”, “konteks halaman HR”, “pager HR”, and “strip profil perusahaan HR” rules.
4. Skeleton rules: delete `.rec-badge-verified` from the two `:has()` selector lists, delete `.rec-stats-grid .sb-skeleton-card, .rec-role-cards .sb-skeleton-card`, and delete `.rec-mapping-grid .sb-skeleton-card`.

After editing, `grep -n '\.rec-' public/css/style.css` must return nothing.

- [ ] **Step 6: Add admin header search CSS to `public/css/admin.css`**

After `.admin-header-right`:

```css
.admin-header-search {
  flex: 1;
  max-width: 380px;
  margin: 0 24px;
}

.admin-header-search input {
  width: 100%;
  height: 38px;
  padding: 0 14px;
  border: 1px solid var(--admin-card-border);
  border-radius: 8px;
  background: var(--surface-subtle, #f8fafc);
  color: var(--text-primary);
  font-family: var(--font-family);
  font-size: 13px;
}

.admin-header-search input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: var(--shadow-focus);
}
```

In `@media (max-width: 1023px)` add:

```css
  .admin-header-search {
    display: none;
  }
```

- [ ] **Step 7: Verification**

```bash
grep -n '\.rec-\|konsol HR\|Recruiter\|recruiter' public/css/style.css public/css/admin.css public/js/ui.js
```

Expected: no output. Then load the CSS through the browser in Task 9.

---

### Task 8: Documentation and operational map sync

**Files:**
- Modify: `README.md`
- Modify: `CLAUDE.md`
- Modify: `AGENTS.md`
- Modify: `DESIGN.md`
- Modify: `Laporan/LaporanPBL.md`
- Modify: `Laporan/PEMETAAN_PROYEK.md`
- Modify: `Laporan/next.md`
- Modify: `docs/PRD.md`
- Modify: `docs/ai_rules_responsive_dan_button.md`
- Modify: `docs/prd_responsive_dan_button.md`

**Interfaces:**
- Documentation must describe two roles (`mahasiswa`, `admin`), the people-search feature, and the token-based public profile.
- Historical academic narratives are not rewritten; add a dated sync note where a claim contradicts the new reality.

- [ ] **Step 1: Update `README.md`**

- Change the tree line to `public/ document root: halaman per role (mahasiswa/, admin/), api/ (endpoint JSON), css/, js/, assets/`.
- Change tools line to `tools/ create_admin.php, check_schema_parity.php`.
- Remove the `php tools/create_hr.php ...` command from the setup steps.
- Remove the `| hr | hr | public/hr/ |` table row.
- Change the admin feature line to remove “lowongan”.
- Delete the HR/Recruiter feature bullet.
- Change the sentence “dashboard mahasiswa, admin, dan HR” to “dashboard mahasiswa dan admin”.
- Add a short “People search” bullet: “Semua user login dapat mencari profil mahasiswa yang Public dan membuka profil publik lewat token UUID; email tidak pernah ditampilkan.”

- [ ] **Step 2: Update `CLAUDE.md`**

- Change the purpose line to remove HR and add the public directory.
- Change the role list to `mahasiswa`, `admin`.
- Remove `hr_header.php`, `hr_sidebar.php`, `hr_footer.php` from the layout tree.
- Remove `public/hr/` and `public/api/hr/` from the tree.
- Remove `vacancies` from the admin page and API lists.
- Remove `recruiter.css` from the CSS list.
- Remove `recruiter.css` and `.rec-sidebar` / `.rec-header` from the forbidden-markup list.
- Change `require_role('mahasiswa' | 'hr' | 'admin')` to `require_role('mahasiswa' | 'admin')`.
- Change the layout extraction examples to remove HR.
- Change paths `public/hr/` in the relative-path warning.
- Change the migration phase text to remove HR.
- Add a short feature note: “Pencarian profil publik ada di navbar mahasiswa dan header admin; halaman publik memakai `/profile.php?token=<uuid>` dan endpoint `/api/search.php`, `/api/public-profile.php`, `/api/public-photo.php`.”

- [ ] **Step 3: Update `AGENTS.md`**

Rewrite the operational map so it describes the current code:

- §1: architecture still accurate; replace role list with `user`/`admin`.
- §2: remove the HR row, HR redirect, `company_name`, HR demo id, and the “HR masuk ruang lingkup” paragraph. Add: “HR sudah dihapus total (keputusan 2026-10-24); jangan menambahkan kembali.”
- §3: remove the HR page table. Add `public/mahasiswa/search.php`, `public/admin/search.php`, and `public/profile.php` rows. Note that search pages use `pages/search.js` and the public profile uses `pages/public-profile.js`.
- §4: remove §4.3 HR endpoints and the `hr/stats.php` trap. Keep the endpoint inventory at 25 minus deleted + 3 new. Add the new endpoints:
  - `search.php` GET, guard `api_guard()` any role, `v_public_profiles` + `user_skills` + `evidence`.
  - `public-profile.php` GET, no guard, `v_public_profiles` + `v_user_skill_evidence`.
  - `public-photo.php` GET, no guard, `v_public_profiles` + `storage/`.
- §5: remove `api_log_activity` and `api_hr_company` from the helper table; add `api_photo_path`, `api_public_profile_url`, `api_public_photo_url`; add role validation to `api_guard`.
- §6: remove HR gateway methods; add `searchProfiles`/`getPublicProfile`; keep `purgeLegacy` `sb.rec.*` as cleanup-only.
- §7: remove HR tables; list `v_public_profiles`; update `schema_migrations` markers; update migrate description (007 destructive, 008 view); update `seed_demo.sql` description.
- §8: remove traps 1, 7, 8 (HR-specific); renumber remaining traps; keep `admin/content.php` open GET and `APP_DEMO` warnings; add a trap: “007 destroys HR data; backup first.”
- §9: remove HR from “sudah nyata berjalan”; add people search + public profile to “sudah nyata berjalan” once implemented; keep CV as not yet implemented.
- §10: remove `create_hr.php` from commands; update verification commands; add `v_public_profiles` check.
- §11: remove HR layout variables (`$rec_page`, `recruiter.css`); add search page template.
- §12: remove HR files and `tools/create_hr.php`; add new search/public profile files.

- [ ] **Step 4: Update `DESIGN.md`**

- Remove “Header admin/HR” → “Header admin”.
- Remove the HR candidate-table paragraph and the HR recruiter-table examples; keep the admin `<div>` dashboard pattern.
- Change “Grid statistik admin/HR” → “Grid statistik admin”.
- Change the language rule to “konsol mahasiswa berbahasa Inggris; halaman publik dan admin berbahasa Indonesia”.
- Add a short section “People search & public profile” describing the navbar/header forms, the 30-result limit, the card structure, and the token-based profile page.

- [ ] **Step 5: Add sync notes to `Laporan/`**

At the top of each of `Laporan/LaporanPBL.md`, `Laporan/PEMETAAN_PROYEK.md`, and `Laporan/next.md`, insert:

```markdown
> **Catatan sinkronisasi 2026-10-24:** Role dan modul HR telah dihapus total dari aplikasi dan database. Fitur pengganti adalah pencarian profil publik (navbar mahasiswa/header admin) dan halaman profil publik berbasis token. Narasi historis di bawah dipertahankan sebagai catatan proses, bukan keadaan kode saat ini.
```

Do not rewrite the rest of those documents.

- [ ] **Step 6: Add sync notes to `docs/`**

Immediately after the first H1 in `docs/PRD.md`, `docs/ai_rules_responsive_dan_button.md`, and `docs/prd_responsive_dan_button.md`, insert:

```markdown
> **Catatan 2026-10-24:** Dokumen ini historis. Role/modul HR sudah dihapus; sumber kebenaran role, endpoint, dan skema ada di `AGENTS.md`, `db/schema.sql`, dan `db/migrate.sql`.
```

- [ ] **Step 7: Verification**

```bash
grep -RInE 'create_hr|public/hr/|/api/hr/|recruiter\.css|Manajemen Lowongan' README.md CLAUDE.md AGENTS.md DESIGN.md
```

Expected: no output. Then:

```bash
grep -RIn 'HR' AGENTS.md
```

Expected: only the intentional “HR sudah dihapus” note and migration-history references.

---

### Task 9: Verification and handoff

**Files:** none (verification only).

- [ ] **Step 1: Run PHP lint on every changed/new PHP file**

```bash
find includes public tools -name '*.php' -print0 | xargs -0 -n1 php -l
```

Expected: every line says `No syntax errors detected`.

- [ ] **Step 2: Run JS syntax checks**

```bash
for f in public/js/api.js public/js/ui.js public/js/pages/search.js public/js/pages/public-profile.js public/js/pages/admin.js public/js/pages/admin-admins.js public/js/pages/profile.js; do node --check "$f" || exit 1; done
```

- [ ] **Step 3: Static grep audit (active code)**

```bash
grep -RInE '\b(recruiter|Recruiter|vacancy|vacancies|company_recruiters|candidate_reviews|activity_log|v_public_candidates|create_hr)\b' includes public tools --include='*.php' --include='*.js' --include='*.css' | grep -v 'sb\.rec\.' || true
```

Expected: no output except the intentional `sb.rec.*` legacy-cleanup keys in `public/js/api.js` (filtered out by the command).

```bash
grep -RInE '\bhr\b' includes public tools --include='*.php' --include='*.js' --include='*.css' || true
```

Expected: no output.

- [ ] **Step 4: Verify the public projections never select email**

```bash
grep -RInE 'email' public/api/search.php public/api/public-profile.php public/api/public-photo.php public/profile.php public/js/pages/search.js public/js/pages/public-profile.js || true
```

Expected: no output.

- [ ] **Step 5: Database verification (after user applies migrations on a staging/test DB)**

Do not run this on the shared Supabase database without a backup and explicit confirmation. On a test DB:

```bash
createdb skillbridge_fresh
psql skillbridge_fresh -f db/schema.sql
psql skillbridge_fresh -f db/seed_demo.sql
psql skillbridge_fresh -c "SELECT enum_range(NULL::user_role);"
psql skillbridge_fresh -c "SELECT to_regclass('public.v_public_profiles'), to_regclass('public.companies'), to_regclass('public.v_public_candidates');"
psql skillbridge_fresh -c "SELECT name, institution FROM v_public_profiles ORDER BY name;"
```

Expected:
- `{user,admin}`
- `v_public_profiles` exists; `companies` and `v_public_candidates` are null.
- Only Geraldi Rama and Siti Aminah appear (Dimas is private, Lia is private/suspended).

For the migration path, use a copy of an old database:

```bash
pg_dump "$OLD_DB" > /tmp/skillbridge_old.sql
createdb skillbridge_migrated
psql skillbridge_migrated -f /tmp/skillbridge_old.sql
psql skillbridge_migrated -f db/migrate.sql
psql skillbridge_migrated -f db/migrate.sql   # idempotency
php tools/check_schema_parity.php skillbridge_fresh skillbridge_migrated
```

Expected: parity exit code `0`.

- [ ] **Step 6: API smoke tests (staging, after migration)**

Start the server with `APP_DEMO=0 php -S localhost:8000 -t public`, then:

```bash
curl -i 'http://localhost:8000/api/search.php?q=php'                       # 401 without session
curl -i 'http://localhost:8000/api/public-profile.php?token=00000000-0000-0000-0000-000000000000'  # 404
curl -i 'http://localhost:8000/api/search.php?q=a'                        # 401 first; 422 after login
```

After logging in as a mahasiswa (use a staging account created with `tools/create_admin.php` or a test DB):

```bash
curl -i -b /tmp/sb.cookies 'http://localhost:8000/api/search.php?q=php'
curl -i -b /tmp/sb.cookies 'http://localhost:8000/api/search.php?q=data'    # Dimas must NOT appear
curl -i -b /tmp/sb.cookies 'http://localhost:8000/api/search.php?q=lia'     # Lia must NOT appear
curl -i 'http://localhost:8000/api/public-profile.php?token=<Geraldi-token>'
curl -i 'http://localhost:8000/api/public-profile.php?token=<Dimas-token>'  # 404
curl -i 'http://localhost:8000/api/public-photo.php?token=<Geraldi-token>'  # image bytes or 404 if no photo
```

Also verify no response contains `email`.

- [ ] **Step 7: Browser verification**

Use the browser-use MCP tools if a staging DB with test accounts is available. Otherwise report this step as not executed and state why.

- Mahasiswa desktop (≥1280px): navbar search visible, submit goes to `mahasiswa/search.php?q=...`, results render, cards open the public profile, no console errors.
- Mahasiswa 1024px and 1280px: navbar does not overflow.
- Mahasiswa mobile (≤1023px): search form is full-width inside the drawer; search works.
- Admin desktop: header search visible; `admin/search.php` works.
- Admin <1024px: header search hidden; sidebar “Cari Profil” works.
- Admin pages Users, Admins, Dashboard, Skills, Careers, Mapping, Settings: no empty vacancy/admin-job fields and no console errors.
- Public profile in incognito: photo/initials, details, skills render; no email or evidence list in DOM/network; invalid/private token shows the friendly 404 state.
- Old HR URL `/hr/index.php` returns 404; `/api/hr/stats.php` returns 404.

- [ ] **Step 8: Final report**

Report:
- Files changed/created/deleted.
- Which checks passed.
- Which checks could not run because no local PostgreSQL/psql is available and the remote database must not be migrated without a backup/confirmation.
- The exact command the user must run after backup: `psql "$TARGET_DB" -f db/migrate.sql`.
- Reminder that no commit was made.

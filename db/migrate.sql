-- ============================================================
-- SkillBridge — SATU FILE MIGRASI (db/migrate.sql)
--
-- Menggabungkan migrasi 000..006 yang lama menjadi satu berkas, tanpa
-- mengubah isi maupun urutan statementnya. Setiap bagian tetap punya
-- BEGIN/COMMIT sendiri supaya kegagalan di satu bagian tidak menyeret
-- bagian yang sudah berhasil.
--
-- Pemakaian:
--   psql <database> -f db/migrate.sql
--   atau tempel seluruh isinya di SQL Editor Supabase, sekali jalan.
--
-- SIFAT: ADITIF dan IDEMPOTEN.
--   - Tidak ada DROP TABLE, tidak ada TRUNCATE, tidak ada DELETE FROM.
--   - DROP yang muncul hanyalah DROP ... IF EXISTS pada constraint/trigger
--     yang memang didefinisikan ulang di baris berikutnya, dan ON DELETE
--     CASCADE hanyalah perilaku relasi di tabel yang baru dibuat.
--   - Aman dijalankan ulang, dan aman pada database yang sudah berisi data.
--
-- KAPAN PAKAI FILE INI, KAPAN PAKAI db/schema.sql:
--   - Database SUDAH dipakai (ada akun, ada bukti, ada profil) -> db/migrate.sql.
--   - Database KOSONG total dan tidak ada yang perlu dipertahankan
--     -> db/schema.sql. File itu destruktif: membuka dengan DROP ... CASCADE.
--
-- KECUALI YANG SENGAJA TIDAK DI-BEGIN:
--   Bagian 005 (ALTER TYPE ... ADD VALUE). PostgreSQL melarang nilai enum
--   baru dipakai di dalam transaksi yang sama saat nilainya ditambahkan,
--   jadi statement-nya dibiarkan autocommit.
--
-- Di akhir setiap bagian, nomornya dicatat di schema_migrations supaya
-- bisa diperiksa database mana sudah sampai bagian mana:
--   SELECT version, applied_at FROM schema_migrations ORDER BY version;
--
-- Data contoh konsol admin/HR TIDAK ada di sini. Itu pilihan, bukan
-- kebutuhan skema, jadi dipisah di db/seed_demo.sql.
-- ============================================================


-- ============================================================
-- 001 — Lapisan domain mahasiswa
-- Menambah kolom dan view yang dibutuhkan endpoint public/api/*
-- agar data keterampilan, bukti, target karier, dan profil
-- tersimpan di PostgreSQL, bukan di localStorage browser.
-- ============================================================

BEGIN;

CREATE TABLE IF NOT EXISTS schema_migrations (
    version    VARCHAR(60) PRIMARY KEY,
    applied_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Status akun dipakai halaman Manajemen Pengguna admin.
-- Sebelum migrasi ini, status hanya hidup di localStorage browser admin.
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'active';

ALTER TABLE users
    DROP CONSTRAINT IF EXISTS chk_users_status;
ALTER TABLE users
    ADD CONSTRAINT chk_users_status
    CHECK (status IN ('active', 'suspended', 'inactive'));

CREATE INDEX IF NOT EXISTS idx_users_status ON users (status);

-- Deskripsi bukti selama ini hanya ada di JSONB metadata sehingga tidak
-- bisa di-query dan tidak konsisten antar halaman; diangkat jadi kolom.
ALTER TABLE evidence
    ADD COLUMN IF NOT EXISTS description TEXT;

ALTER TABLE evidence
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP;

DROP TRIGGER IF EXISTS trg_evidence_updated_at ON evidence;
CREATE TRIGGER trg_evidence_updated_at
    BEFORE UPDATE ON evidence
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Kolom identitas yang ditampilkan halaman Profil mahasiswa.
ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS institution VARCHAR(120);
ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS program     VARCHAR(120);
ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS semester    VARCHAR(20);
ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS location    VARCHAR(120);
ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS bio         TEXT;
-- Foto disimpan sebagai path di luar docroot, bukan data URL di browser.
ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS photo_path  VARCHAR(500);
-- Baris yang sudah ada mendapat waktu migrasi, karena waktu aslinya
-- tidak pernah dicatat skema lama.
ALTER TABLE student_profiles ADD COLUMN IF NOT EXISTS created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Satu baris per skill yang dimiliki user, dengan jumlah bukti MILIK
-- USER ITU SENDIRI. Join lewat bukti milik pengguna, bukan sekadar
-- relasi skill, agar agregat tidak menampung bukti akun lain.
CREATE OR REPLACE VIEW v_user_skill_evidence AS
SELECT
    us.user_id,
    us.skill_id,
    s.name        AS skill_name,
    s.category    AS skill_category,
    us.source,
    us.status,
    (
        SELECT COUNT(*)
        FROM evidence_skills es
        JOIN evidence ev ON ev.id = es.evidence_id
        WHERE es.skill_id = us.skill_id
          AND ev.user_id  = us.user_id
    ) AS evidence_count
FROM user_skills us
JOIN skills s ON s.id = us.skill_id;

INSERT INTO schema_migrations (version) VALUES ('001_mahasiswa_domain')
ON CONFLICT (version) DO NOTHING;

COMMIT;


-- ============================================================
-- 002 — Pengaturan platform dan konten admin
-- Menggantikan localStorage kunci sb.admin.config dan sb.admin.profile
-- serta berkas public/data/admin-content.json.
-- ============================================================

BEGIN;

-- Parameter global yang diedit di halaman Pengaturan Sistem admin.
-- Nilai disimpan sebagai JSONB supaya tipenya (number, boolean, string)
-- tidak hilang saat dibaca kembali oleh endpoint.
CREATE TABLE IF NOT EXISTS system_settings (
    key        VARCHAR(80) PRIMARY KEY,
    value      JSONB NOT NULL,
    updated_by INT REFERENCES users(id) ON DELETE SET NULL,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Konten editorial yang saat ini dibaca dari admin-content.json.
CREATE TABLE IF NOT EXISTS content_items (
    id         SERIAL PRIMARY KEY,
    slug       VARCHAR(160) NOT NULL UNIQUE,
    title      VARCHAR(150) NOT NULL,
    type       VARCHAR(30)  NOT NULL DEFAULT 'article',
    category   VARCHAR(50),
    status     VARCHAR(20)  NOT NULL DEFAULT 'draft',
    author     VARCHAR(100),
    excerpt    TEXT,
    body       TEXT,
    views      INTEGER NOT NULL DEFAULT 0 CHECK (views >= 0),
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE content_items
    DROP CONSTRAINT IF EXISTS chk_content_status;
ALTER TABLE content_items
    ADD CONSTRAINT chk_content_status
    CHECK (status IN ('published', 'draft', 'archived'));

CREATE INDEX IF NOT EXISTS idx_content_status ON content_items (status);

DROP TRIGGER IF EXISTS trg_content_updated_at ON content_items;
CREATE TRIGGER trg_content_updated_at
    BEFORE UPDATE ON content_items
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

INSERT INTO schema_migrations (version) VALUES ('002_platform_settings_content')
ON CONFLICT (version) DO NOTHING;

COMMIT;


-- ============================================================
-- 003 — Modul HR / perusahaan
-- Menggantikan seluruh localStorage recruiter:
--   sb.rec.company, sb.rec.team, sb.rec.roles, sb.rec.activity,
--   sb.rec.candidates, sb.rec.mapping.{roleId}
-- dan berkas public/data/recruiter-*.json.
-- ============================================================

BEGIN;

-- ---------- PERUSAHAAN MITRA ----------
CREATE TABLE IF NOT EXISTS companies (
    id           SERIAL PRIMARY KEY,
    name         VARCHAR(150) NOT NULL UNIQUE,
    tagline      VARCHAR(150),
    email_domain VARCHAR(120),
    location     VARCHAR(120),
    description  TEXT,
    logo_initial VARCHAR(4),
    status       VARCHAR(20) NOT NULL DEFAULT 'pending',
    created_at   TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE companies DROP CONSTRAINT IF EXISTS chk_company_status;
ALTER TABLE companies ADD CONSTRAINT chk_company_status
    CHECK (status IN ('verified', 'pending', 'suspended'));

-- ---------- ANGGOTA TIM REKRUTER ----------
-- user_id diisi untuk anggota yang punya akun login (role 'hr');
-- NULL untuk anggota catatan saja yang belum punya akun.
CREATE TABLE IF NOT EXISTS company_recruiters (
    id         SERIAL PRIMARY KEY,
    company_id INT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    user_id    INT UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    role       VARCHAR(20)  NOT NULL DEFAULT 'recruiter',
    role_label VARCHAR(60),
    status     VARCHAR(20)  NOT NULL DEFAULT 'active',
    created_at TIMESTAMPTZ  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (company_id, email)
);

ALTER TABLE company_recruiters DROP CONSTRAINT IF EXISTS chk_recruiter_status;
ALTER TABLE company_recruiters ADD CONSTRAINT chk_recruiter_status
    CHECK (status IN ('active', 'inactive', 'invited'));

CREATE INDEX IF NOT EXISTS idx_recruiters_company ON company_recruiters (company_id);

-- ---------- LOWONGAN / TARGET ROLE PERUSAHAAN ----------
-- Satu tabel melayani dua layar yang sebelumnya punya JSON sendiri:
-- daftar target role di konsol HR dan moderasi lowongan di konsol admin.
CREATE TABLE IF NOT EXISTS vacancies (
    id         SERIAL PRIMARY KEY,
    company_id INT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    title      VARCHAR(120) NOT NULL,
    category   VARCHAR(60),
    location   VARCHAR(120),
    type       VARCHAR(40)  NOT NULL DEFAULT 'Full-time',
    status     VARCHAR(20)  NOT NULL DEFAULT 'open',
    positions  INTEGER NOT NULL DEFAULT 1 CHECK (positions >= 1),
    created_by INT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE vacancies DROP CONSTRAINT IF EXISTS chk_vacancy_status;
ALTER TABLE vacancies ADD CONSTRAINT chk_vacancy_status
    CHECK (status IN ('open', 'closed', 'draft', 'pending', 'rejected'));

CREATE INDEX IF NOT EXISTS idx_vacancies_company ON vacancies (company_id);
CREATE INDEX IF NOT EXISTS idx_vacancies_status  ON vacancies (status);

-- Penghubung lowongan <-> skill yang dibutuhkan.
-- Ini pengganti localStorage sb.rec.mapping.{roleId}.
CREATE TABLE IF NOT EXISTS vacancy_skills (
    vacancy_id   INT NOT NULL REFERENCES vacancies(id) ON DELETE CASCADE,
    skill_id     INT NOT NULL REFERENCES skills(id)    ON DELETE CASCADE,
    is_mandatory BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (vacancy_id, skill_id)
);

CREATE INDEX IF NOT EXISTS idx_vacancy_skills_skill ON vacancy_skills (skill_id);

-- ---------- PIPELINE KANDIDAT ----------
-- Status seleksi per kandidat per lowongan. Kandidatnya sendiri adalah
-- baris users (role 'user'); yang dihitung hanyalah keputusan rekruter.
CREATE TABLE IF NOT EXISTS candidate_reviews (
    id         SERIAL PRIMARY KEY,
    company_id INT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    user_id    INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    vacancy_id INT REFERENCES vacancies(id) ON DELETE CASCADE,
    status     VARCHAR(20) NOT NULL DEFAULT 'new',
    note       TEXT,
    updated_by INT REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE candidate_reviews DROP CONSTRAINT IF EXISTS chk_candidate_status;
ALTER TABLE candidate_reviews ADD CONSTRAINT chk_candidate_status
    CHECK (status IN ('new', 'reviewing', 'interview', 'offered', 'rejected', 'hired'));

-- vacancy_id boleh NULL (tinjauan umum tanpa lowongan), sehingga UNIQUE
-- biasa tidak cukup; indeks ekspresi ini menutup celah duplikat.
CREATE UNIQUE INDEX IF NOT EXISTS uq_candidate_review
    ON candidate_reviews (company_id, user_id, COALESCE(vacancy_id, 0));

CREATE INDEX IF NOT EXISTS idx_candidate_reviews_company ON candidate_reviews (company_id);

-- ---------- LOG AKTIVITAS ----------
CREATE TABLE IF NOT EXISTS activity_log (
    id            SERIAL PRIMARY KEY,
    company_id    INT REFERENCES companies(id) ON DELETE CASCADE,
    actor_user_id INT REFERENCES users(id) ON DELETE SET NULL,
    actor         VARCHAR(100) NOT NULL,
    action        VARCHAR(30)  NOT NULL,
    text          TEXT NOT NULL,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_activity_company ON activity_log (company_id, created_at DESC);

-- ---------- PROFIL MAHASISWA YANG RELAI DILIHAT REKRUTER ----------
-- Kandidat hanya datang dari profil yang sengaja dibuat publik oleh
-- pemiliknya. Ini memakai kolom is_public dan public_token yang selama
-- ini tidak pernah dibaca kode, sekaligus menjawab temuan PII di laporan:
-- tanpa opt-in, tidak ada satu pun nama mahasiswa yang muncul di konsol HR.
CREATE OR REPLACE VIEW v_public_candidates AS
SELECT
    u.id,
    u.name,
    u.email,
    sp.target_career_id,
    c.name AS target_role,
    sp.institution,
    sp.location
FROM users u
JOIN student_profiles sp ON sp.user_id = u.id
LEFT JOIN careers c ON c.id = sp.target_career_id
WHERE u.role = 'user'
  AND u.status = 'active'
  AND sp.is_public = TRUE;

DROP TRIGGER IF EXISTS trg_companies_updated_at ON companies;
CREATE TRIGGER trg_companies_updated_at
    BEFORE UPDATE ON companies
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

DROP TRIGGER IF EXISTS trg_vacancies_updated_at ON vacancies;
CREATE TRIGGER trg_vacancies_updated_at
    BEFORE UPDATE ON vacancies
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

INSERT INTO schema_migrations (version) VALUES ('003_hr_module')
ON CONFLICT (version) DO NOTHING;

COMMIT;


-- ============================================================
-- 004 — Perluasan katalog skill (DATA, bukan struktur)
--
-- Skema lama hanya mengenal 18 skill. Katalog UI dan target role HR
-- ternyata meminta skill yang tidak ada di database: Node.js,
-- Express.js, TypeScript, Vue, React, MySQL, Pandas, Tableau, Power BI,
-- Testing, Deployment. Tanpa baris ini, relasi vacancy_skills dan bukti
-- mahasiswa tidak bisa menunjuk ke skill nyata. Categories sengaja
-- memakai enam nama yang sudah dikenal UI supaya ikon kategori tetap
-- konsisten.
-- ============================================================

BEGIN;

INSERT INTO skills (name, category) VALUES
  ('TypeScript',        'Programming'),
  ('Node.js',           'Programming'),
  ('Express.js',        'Web'),
  ('Vue',               'Web'),
  ('React',             'Web'),
  ('MySQL',             'Database'),
  ('Testing',           'Tools'),
  ('Deployment',        'Tools'),
  ('Pandas',            'Data'),
  ('Tableau',           'Data'),
  ('Power BI',          'Data')
ON CONFLICT (name) DO NOTHING;

-- Relasi baru untuk empat target karier yang sudah ada.
INSERT INTO career_skill_requirements (career_id, skill_id)
SELECT c.id, s.id
FROM (VALUES
  ('Backend Developer', 'Node.js'),
  ('Backend Developer', 'Express.js'),
  ('Frontend Developer', 'React'),
  ('Frontend Developer', 'TypeScript'),
  ('Data Analyst', 'Pandas'),
  ('Data Analyst', 'Tableau'),
  ('Data Analyst', 'Power BI')
) AS m(career_name, skill_name)
JOIN careers c ON c.name = m.career_name
JOIN skills  s ON s.name = m.skill_name
ON CONFLICT (career_id, skill_id) DO NOTHING;

INSERT INTO schema_migrations (version)
VALUES ('004_catalog_expansion')
ON CONFLICT (version) DO NOTHING;

COMMIT;


-- ============================================================
-- 005 — Nilai enum evidence_source_type tambahan
--
-- Form "Tambah Bukti" menawarkan enam jenis sumber: cv, github,
-- portfolio, project, certificate, assessment. Enum lama hanya mengenal
-- empat, sehingga 'github' dan 'assessment' akan ditolak PostgreSQL
-- begitu bukti disimpan ke server.
--
-- SENGAJA tanpa BEGIN/COMMIT — lihat catatan di kepala file.
-- Idempoten lewat IF NOT EXISTS.
-- ============================================================

ALTER TYPE evidence_source_type ADD VALUE IF NOT EXISTS 'github';
ALTER TYPE evidence_source_type ADD VALUE IF NOT EXISTS 'assessment';

INSERT INTO schema_migrations (version)
VALUES ('005_evidence_source_types')
ON CONFLICT (version) DO NOTHING;


-- ============================================================
-- 006 — Stempel waktu pada master data
--
-- Dashboard admin menampilkan "aktivitas terbaru" yang digabung dari
-- users, skills, careers, dan vacancies. Tanpa created_at, baris
-- keterampilan dan target karier tidak punya waktu sama sekali sehingga
-- mustahil diurutkan (lihat public/api/admin/stats.php).
--
-- Baris lama mendapat waktu migrasi, bukan waktu pembuatan sesungguhnya,
-- karena informasi itu memang tidak pernah dicatat.
-- ============================================================

BEGIN;

ALTER TABLE skills  ADD COLUMN IF NOT EXISTS created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE careers ADD COLUMN IF NOT EXISTS created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP;

INSERT INTO schema_migrations (version)
VALUES ('006_master_timestamps')
ON CONFLICT (version) DO NOTHING;

COMMIT;

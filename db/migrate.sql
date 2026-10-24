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
-- SIFAT:
--   - Bagian 001–006 dan 008 bersifat ADITIF dan IDEMPOTEN.
--   - Bagian 007 SENGAJA DESTRUKTIF: menghapus tabel HR, view lama, dan akun
--     ber-role 'hr'. WAJIB didahului pg_dump ke lokasi di luar repo.
--     Tidak ada rollback otomatis; rollback = restore backup.
--   - Setiap bagian punya BEGIN/COMMIT sendiri, kecuali 005.
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
-- Data contoh konsol admin dan direktori publik TIDAK ada di sini. Itu pilihan,
-- bukan kebutuhan skema, jadi dipisah di db/seed_demo.sql.
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
-- 004 — Perluasan katalog skill (DATA, bukan struktur)
--
-- Skema lama hanya mengenal 18 skill. Katalog UI dan relasi karier
-- ternyata meminta skill yang tidak ada di database: Node.js,
-- Express.js, TypeScript, Vue, React, MySQL, Pandas, Tableau, Power BI,
-- Testing, Deployment. Tanpa baris ini, relasi career_skill_requirements
-- dan bukti mahasiswa tidak bisa menunjuk ke skill nyata. Categories
-- sengaja memakai enam nama yang sudah dikenal UI supaya ikon kategori
-- tetap konsisten.
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
-- users, skills, dan careers. Tanpa created_at, baris
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

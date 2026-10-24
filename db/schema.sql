-- ============================================================
-- SkillBridge Database Schema + Seed (PostgreSQL)
-- File tunggal untuk INSTALASI BARU: enum, tabel, index, trigger,
-- view, dan data master awal.
--
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
--
-- Pemakaian:
--   createdb skillbridge
--   psql skillbridge -f db/schema.sql
--   psql skillbridge -f db/seed_demo.sql      (opsional, data contoh)
-- ============================================================

BEGIN;

-- ---------- BERSIHKAN OBJEK LAMA ----------
DROP VIEW IF EXISTS v_skill_gap, v_user_skill_evidence, v_public_candidates, v_public_profiles;
DROP TABLE IF EXISTS activity_log, candidate_reviews, vacancy_skills, vacancies,
    company_recruiters, companies, content_items, system_settings,
    evidence_skills, user_skills, evidence,
    student_profiles, career_skill_requirements, users, skills, careers CASCADE;
DROP FUNCTION IF EXISTS set_updated_at() CASCADE;
DROP TYPE IF EXISTS user_role, evidence_source_type, skill_source, skill_status CASCADE;

-- ---------- ENUM ----------
CREATE TYPE user_role AS ENUM ('user', 'admin');
CREATE TYPE evidence_source_type AS ENUM ('cv', 'github', 'project', 'portfolio', 'certificate', 'assessment');
CREATE TYPE skill_source AS ENUM ('manual', 'cv_extracted');
CREATE TYPE skill_status AS ENUM ('candidate', 'confirmed');

-- ---------- FUNGSI GENERIK updated_at ----------
CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ---------- MASTER DATA (dikelola admin) ----------
CREATE TABLE careers (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE skills (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    category    VARCHAR(50),
    description TEXT,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Skill yang dibutuhkan tiap target karier
CREATE TABLE career_skill_requirements (
    id           SERIAL PRIMARY KEY,
    career_id    INT NOT NULL REFERENCES careers(id) ON DELETE CASCADE,
    skill_id     INT NOT NULL REFERENCES skills(id)  ON DELETE CASCADE,
    is_mandatory BOOLEAN NOT NULL DEFAULT TRUE,
    UNIQUE (career_id, skill_id)
);

CREATE INDEX idx_csr_skill ON career_skill_requirements (skill_id);

-- ---------- PENGGUNA ----------
CREATE TABLE users (
    id            SERIAL PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          user_role NOT NULL DEFAULT 'user',
    status        VARCHAR(20) NOT NULL DEFAULT 'active'
                  CONSTRAINT chk_users_status
                  CHECK (status IN ('active', 'suspended', 'inactive')),
    created_at    TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_users_status ON users (status);

CREATE TRIGGER trg_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Profil mahasiswa: identitas, satu target karier aktif, pengaturan profil publik
CREATE TABLE student_profiles (
    user_id          INT PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    target_career_id INT REFERENCES careers(id) ON DELETE SET NULL,
    institution      VARCHAR(120),
    program          VARCHAR(120),
    semester         VARCHAR(20),
    location         VARCHAR(120),
    bio              TEXT,
    photo_path       VARCHAR(500),
    is_public        BOOLEAN NOT NULL DEFAULT FALSE,
    public_token     UUID UNIQUE,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- profil publik wajib punya token
    CONSTRAINT chk_public_needs_token CHECK (NOT is_public OR public_token IS NOT NULL)
);

CREATE INDEX idx_student_profiles_target ON student_profiles (target_career_id);

CREATE TRIGGER trg_student_profiles_updated_at
    BEFORE UPDATE ON student_profiles
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ---------- EVIDENCE ----------
CREATE TABLE evidence (
    id          SERIAL PRIMARY KEY,
    user_id     INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    source_type evidence_source_type NOT NULL,
    title       VARCHAR(150) NOT NULL,
    description TEXT,
    file_url    VARCHAR(500),
    metadata    JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_evidence_user     ON evidence (user_id);
CREATE INDEX idx_evidence_metadata ON evidence USING GIN (metadata);

CREATE TRIGGER trg_evidence_updated_at
    BEFORE UPDATE ON evidence
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ---------- SKILL MILIK USER ----------
-- 'manual'       : diinput user, langsung boleh 'confirmed'
-- 'cv_extracted' : hasil ekstraksi, mulai dari 'candidate' sampai user konfirmasi
CREATE TABLE user_skills (
    id         SERIAL PRIMARY KEY,
    user_id    INT NOT NULL REFERENCES users(id)  ON DELETE CASCADE,
    skill_id   INT NOT NULL REFERENCES skills(id) ON DELETE CASCADE,
    source     skill_source NOT NULL DEFAULT 'manual',
    status     skill_status NOT NULL DEFAULT 'confirmed',
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, skill_id)
);

CREATE INDEX idx_user_skills_skill ON user_skills (skill_id);

CREATE TRIGGER trg_user_skills_updated_at
    BEFORE UPDATE ON user_skills
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Penghubung: satu evidence bisa membuktikan banyak skill
CREATE TABLE evidence_skills (
    evidence_id INT NOT NULL REFERENCES evidence(id) ON DELETE CASCADE,
    skill_id    INT NOT NULL REFERENCES skills(id)   ON DELETE CASCADE,
    PRIMARY KEY (evidence_id, skill_id)
);

CREATE INDEX idx_evidence_skills_skill ON evidence_skills (skill_id);

-- ---------- PENGATURAN PLATFORM & KONTEN (admin) ----------
CREATE TABLE system_settings (
    key        VARCHAR(80) PRIMARY KEY,
    value      JSONB NOT NULL,
    updated_by INT REFERENCES users(id) ON DELETE SET NULL,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE content_items (
    id         SERIAL PRIMARY KEY,
    slug       VARCHAR(160) NOT NULL UNIQUE,
    title      VARCHAR(150) NOT NULL,
    type       VARCHAR(30)  NOT NULL DEFAULT 'article',
    category   VARCHAR(50),
    status     VARCHAR(20)  NOT NULL DEFAULT 'draft'
               CONSTRAINT chk_content_status
               CHECK (status IN ('published', 'draft', 'archived')),
    author     VARCHAR(100),
    excerpt    TEXT,
    body       TEXT,
    views      INTEGER NOT NULL DEFAULT 0 CHECK (views >= 0),
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_content_status ON content_items (status);

CREATE TRIGGER trg_content_updated_at
    BEFORE UPDATE ON content_items
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- ---------- VIEW ----------
-- Jumlah bukti MILIK USER ITU SENDIRI per skill yang dimiliki.
-- Tidak mengembalikan skor atau persentase.
CREATE VIEW v_user_skill_evidence AS
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

-- ---------- DATA MASTER AWAL ----------
-- Hanya katalog skills dan careers. Data contoh ada di db/seed_demo.sql.
-- ON CONFLICT DO NOTHING membuat blok ini aman dijalankan ulang.

INSERT INTO skills (name, category) VALUES
  ('PHP', 'Programming'),
  ('Python', 'Programming'),
  ('JavaScript', 'Programming'),
  ('TypeScript', 'Programming'),
  ('SQL', 'Database'),
  ('PostgreSQL', 'Database'),
  ('MySQL', 'Database'),
  ('Node.js', 'Programming'),
  ('Express.js', 'Web'),
  ('REST APIs', 'Web'),
  ('Vue', 'Web'),
  ('React', 'Web'),
  ('HTML', 'Web'),
  ('CSS', 'Web'),
  ('Responsive Design', 'Web'),
  ('Testing', 'Tools'),
  ('Deployment', 'Tools'),
  ('Git', 'Tools'),
  ('Figma', 'Design'),
  ('Wireframing', 'Design'),
  ('Prototyping', 'Design'),
  ('User Research', 'Design'),
  ('Usability Testing', 'Design'),
  ('Excel', 'Data'),
  ('Pandas', 'Data'),
  ('Tableau', 'Data'),
  ('Power BI', 'Data'),
  ('Data Visualisation', 'Data'),
  ('Statistics', 'Data')
ON CONFLICT (name) DO NOTHING;

INSERT INTO careers (name, description) VALUES
  ('Backend Developer', 'Build APIs, databases, and server-side services for web products.'),
  ('Frontend Developer', 'Create accessible, responsive interfaces for websites and web apps.'),
  ('Data Analyst', 'Prepare data, explore patterns, and explain useful findings clearly.'),
  ('UI/UX Designer', 'Plan simple digital experiences and create clear interface designs.')
ON CONFLICT (name) DO NOTHING;

INSERT INTO career_skill_requirements (career_id, skill_id)
SELECT c.id, s.id
FROM (VALUES
  ('Backend Developer', 'PHP'),
  ('Backend Developer', 'Node.js'),
  ('Backend Developer', 'Express.js'),
  ('Backend Developer', 'SQL'),
  ('Backend Developer', 'PostgreSQL'),
  ('Backend Developer', 'REST APIs'),
  ('Backend Developer', 'Git'),
  ('Frontend Developer', 'HTML'),
  ('Frontend Developer', 'CSS'),
  ('Frontend Developer', 'JavaScript'),
  ('Frontend Developer', 'React'),
  ('Frontend Developer', 'TypeScript'),
  ('Frontend Developer', 'Responsive Design'),
  ('Frontend Developer', 'Git'),
  ('Data Analyst', 'SQL'),
  ('Data Analyst', 'Python'),
  ('Data Analyst', 'Pandas'),
  ('Data Analyst', 'Excel'),
  ('Data Analyst', 'Tableau'),
  ('Data Analyst', 'Power BI'),
  ('Data Analyst', 'Data Visualisation'),
  ('Data Analyst', 'Statistics'),
  ('UI/UX Designer', 'Figma'),
  ('UI/UX Designer', 'Wireframing'),
  ('UI/UX Designer', 'Prototyping'),
  ('UI/UX Designer', 'User Research'),
  ('UI/UX Designer', 'Usability Testing')
) AS m(career_name, skill_name)
JOIN careers c ON c.name = m.career_name
JOIN skills  s ON s.name = m.skill_name
ON CONFLICT (career_id, skill_id) DO NOTHING;

-- Pengaturan platform bawaan.
INSERT INTO system_settings (key, value) VALUES
  ('site_name',          '"SkillBridge"'::jsonb),
  ('max_evidence',       '12'::jsonb),
  ('default_visibility', '"Public"'::jsonb),
  ('registration_open',  'true'::jsonb)
ON CONFLICT (key) DO NOTHING;

CREATE TABLE IF NOT EXISTS schema_migrations (
    version    VARCHAR(60) PRIMARY KEY,
    applied_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO schema_migrations (version)
VALUES ('000_migrations'), ('schema.sql'),
       ('001_mahasiswa_domain'), ('002_platform_settings_content'),
       ('004_catalog_expansion'), ('005_evidence_source_types'),
       ('006_master_timestamps'), ('007_remove_hr_module'),
       ('008_public_profile_directory')
ON CONFLICT (version) DO NOTHING;

COMMIT;

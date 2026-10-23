-- ============================================================
-- SkillBridge Database Schema (PostgreSQL) - Final (Opsi A)
-- Role: user & admin. Profil publik read-only lewat token.
-- Evidence: kolom JSONB metadata (Keputusan G03).
-- Hasil ekstraksi CV berstatus 'candidate' sampai dikonfirmasi (G02).
-- Tanpa skor/persentase kesiapan (Keputusan G01).
-- ============================================================

-- ---------- ENUM ----------
CREATE TYPE user_role AS ENUM ('user', 'admin');
CREATE TYPE evidence_source_type AS ENUM ('cv', 'project', 'portfolio', 'certificate');
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
    description TEXT
);

CREATE TABLE skills (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    category    VARCHAR(50),
    description TEXT
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
    created_at    TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER trg_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Profil mahasiswa: satu target karier aktif + pengaturan profil publik
CREATE TABLE student_profiles (
    user_id          INT PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    target_career_id INT REFERENCES careers(id) ON DELETE SET NULL,
    is_public        BOOLEAN NOT NULL DEFAULT FALSE,
    public_token     UUID UNIQUE,
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
    file_url    VARCHAR(500),
    metadata    JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_evidence_user     ON evidence (user_id);
CREATE INDEX idx_evidence_metadata ON evidence USING GIN (metadata);

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

-- ---------- VIEW SKILL GAP (deskriptif, tanpa skor) ----------
-- Satu baris per skill yang dibutuhkan target karier user.
-- has_skill = TRUE  -> skill sudah dimiliki (confirmed)
-- has_skill = FALSE -> skill gap
CREATE VIEW v_skill_gap AS
SELECT
    sp.user_id,
    sp.target_career_id AS career_id,
    csr.skill_id,
    s.name              AS skill_name,
    csr.is_mandatory,
    (us.id IS NOT NULL) AS has_skill
FROM student_profiles sp
JOIN career_skill_requirements csr ON csr.career_id = sp.target_career_id
JOIN skills s                      ON s.id = csr.skill_id
LEFT JOIN user_skills us
       ON us.user_id  = sp.user_id
      AND us.skill_id = csr.skill_id
      AND us.status   = 'confirmed';

-- Contoh pemakaian:
--   Skill gap user 1      : SELECT * FROM v_skill_gap WHERE user_id = 1 AND NOT has_skill;
--   Skill yang sudah ada  : SELECT * FROM v_skill_gap WHERE user_id = 1 AND has_skill;
--   Profil publik         : SELECT ... FROM student_profiles WHERE public_token = $1 AND is_public;
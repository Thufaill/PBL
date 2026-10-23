-- ============================================================
-- SkillBridge Demo Seed (PostgreSQL)
-- Data contoh untuk konsol admin dan HR. Bersifat OPSIONAL.
--
-- Sifat file: aditif dan idempoten. Tidak ada DROP, tidak ada
-- DELETE. Aman dijalankan berulang kali pada database yang sama.
--
-- Pemakaian:
--   psql skillbridge -f db/schema.sql
--   psql skillbridge -f db/seed_demo.sql
--
-- Sumber data: public/data/admin-content.json, admin-vacancies.json,
-- recruiter-company.json, recruiter-roles.json, recruiter-team.json,
-- recruiter-activity.json. Bentuk JSON itu dipertahankan oleh endpoint
-- di public/api/ supaya halaman tidak perlu ditulis ulang.
--
-- PENTANG TENTANG KREDENSIAL:
-- Baris users di bawah memakai password_hash yang SENGAJA tidak valid,
-- yaitu string '!!demo-not-a-verifiable-hash!!'. password_verify()
-- terhadap string itu selalu false, jadi akun demo tidak bisa dipakai
-- login. Untuk akun yang bisa login, jalankan tools/create_admin.php
-- atau tools/create_hr.php -- keduanya memakai password_hash() asli.
-- Keputusan ini diambil supaya tidak ada kredensial lemah yang ikut
-- ter-commit ke repository.
-- ============================================================

BEGIN;

-- ---------- ADMIN: KONTEN ----------
INSERT INTO content_items (slug, title, type, category, status, author, excerpt, views) VALUES
  ('panduan-memilih-target-karier', 'Panduan Memilih Target Karier',   'article', 'Career',    'published', 'Admin SkillBridge', 'Cara memilih satu target karier yang realistis.', 1240),
  ('cara-menulis-evidence-yang-kuat', 'Cara Menulis Evidence yang Kuat', 'article', 'Portfolio', 'published', 'Admin SkillBridge', 'Bukti yang jelas membuat skill mudah diverifikasi.',    890),
  ('roadmap-belajar-backend-developer', 'Roadmap Belajar Backend Developer', 'guide', 'Learning',  'published', 'Admin SkillBridge', 'Urutan belajar dari database sampai API.',            2105),
  ('tips-interview-teknis',           'Tips Interview Teknis',          'article', 'Career',    'draft',     'Admin SkillBridge', 'Persiapan wawancara teknis untuk mahasiswa.',            0),
  ('daftar-skill-paling-dicari-2026', 'Daftar Skill Paling Dicari 2026','guide',   'Learning',  'published', 'Admin SkillBridge', 'Katalog skill yang diminta perusahaan mitra.',           3320)
ON CONFLICT (slug) DO NOTHING;

-- ---------- ADMIN: LOWONGAN + HR: TARGET ROLE ----------
-- Satu tabel vacancies dipakai dua layar: "Vacancies" di admin untuk
-- moderasi, dan "Target Roles" di HR untuk kebutuhan perusahaan.
INSERT INTO companies (name, tagline, email_domain, location, description, logo_initial, status) VALUES
  ('PT Tech Indonesia', 'Mitra Resmi SkillBridge', 'techindonesia.example', 'Jakarta',
   'Perusahaan mitra untuk uji coba konsol HR SkillBridge.', 'T', 'verified'),
  ('TechCorp',   'Bangun layanan backend untuk produk web.', NULL, 'Jakarta',
   'Perusahaan contoh untuk data lowongan admin.',            'T', 'verified'),
  ('StartupB',   'Produk web cepat tumbuh.',                 NULL, 'Bandung',
   'Perusahaan contoh untuk data lowongan admin.',            'S', 'verified'),
  ('Corp C',     'Analitik data untuk ritel.',               NULL, 'Surabaya',
   'Perusahaan contoh untuk data lowongan admin.',            'C', 'pending')
ON CONFLICT (name) DO NOTHING;

-- Target role milik perusahaan demo utama (sumber: recruiter-roles.json).
-- vacancies tidak punya UNIQUE, jadi idempotensinya lewat WHERE NOT EXISTS.
INSERT INTO vacancies (company_id, title, category, location, type, status, positions)
SELECT c.id, m.title, m.category, m.location, m.type, 'open', m.positions
FROM (VALUES
  ('PT Tech Indonesia', 'Backend Developer',  'Engineering', 'Jakarta', 'Full-time',  3),
  ('PT Tech Indonesia', 'Frontend Developer', 'Engineering', 'Jakarta', 'Full-time',  2),
  ('PT Tech Indonesia', 'UI/UX Designer',     'Design',      'Jakarta', 'Full-time',  2),
  ('PT Tech Indonesia', 'Data Analyst',       'Data',        'Remote',  'Internship', 1)
) AS m(company_name, title, category, location, type, positions)
JOIN companies c ON c.name = m.company_name
WHERE NOT EXISTS (
    SELECT 1 FROM vacancies v
    WHERE v.company_id = c.id AND v.title = m.title AND v.status = 'open'
);

-- Lowongan tambahan untuk layar moderasi admin (sumber: admin-vacancies.json).
INSERT INTO vacancies (company_id, title, category, location, type, status, positions)
SELECT c.id, m.title, m.category, m.location, m.type, m.status, 1
FROM (VALUES
  ('TechCorp', 'Backend Developer',  'Engineering', 'Jakarta',  'Full-time',  'open'),
  ('StartupB', 'Frontend Developer', 'Engineering', 'Bandung',  'Full-time',  'open'),
  ('Corp C',   'Data Analyst Intern','Data',        'Remote',   'Internship', 'open'),
  ('TechCorp', 'UI/UX Designer',     'Design',      'Jakarta',  'Full-time',  'closed'),
  ('StartupB', 'DevOps Engineer',    'Operations',  'Remote',   'Contract',   'open'),
  ('Corp C',   'QA Engineer',        'Quality',     'Surabaya', 'Full-time',  'draft')
) AS m(company_name, title, category, location, type, status)
JOIN companies c ON c.name = m.company_name
WHERE NOT EXISTS (
    SELECT 1 FROM vacancies v
    WHERE v.company_id = c.id AND v.title = m.title AND v.status = m.status
);

-- Skill yang diminta tiap lowongan. 'HTML/CSS' pada JSON lama dipecah
-- menjadi dua skill nyata karena kolom skills tidak mengenal istilah itu.
INSERT INTO vacancy_skills (vacancy_id, skill_id, is_mandatory)
SELECT v.id, s.id, m.mandatory
FROM (VALUES
  ('PT Tech Indonesia', 'Backend Developer',  'Node.js',    TRUE),
  ('PT Tech Indonesia', 'Backend Developer',  'SQL',        TRUE),
  ('PT Tech Indonesia', 'Backend Developer',  'REST APIs',  TRUE),
  ('PT Tech Indonesia', 'Backend Developer',  'Git',        FALSE),
  ('PT Tech Indonesia', 'Frontend Developer', 'React',      TRUE),
  ('PT Tech Indonesia', 'Frontend Developer', 'JavaScript', TRUE),
  ('PT Tech Indonesia', 'Frontend Developer', 'HTML',       TRUE),
  ('PT Tech Indonesia', 'Frontend Developer', 'CSS',        TRUE),
  ('PT Tech Indonesia', 'UI/UX Designer',     'Figma',           TRUE),
  ('PT Tech Indonesia', 'UI/UX Designer',     'User Research',   TRUE),
  ('PT Tech Indonesia', 'UI/UX Designer',     'Wireframing',     FALSE),
  ('PT Tech Indonesia', 'Data Analyst',       'Excel',  TRUE),
  ('PT Tech Indonesia', 'Data Analyst',       'Python', TRUE),
  ('PT Tech Indonesia', 'Data Analyst',       'SQL',    FALSE),
  ('TechCorp',  'Backend Developer',  'PHP',         TRUE),
  ('TechCorp',  'Backend Developer',  'PostgreSQL',  TRUE),
  ('TechCorp',  'Backend Developer',  'REST APIs',   TRUE),
  ('TechCorp',  'UI/UX Designer',     'Figma',       TRUE),
  ('TechCorp',  'UI/UX Designer',     'Prototyping', TRUE),
  ('StartupB',  'Frontend Developer', 'Vue',         TRUE),
  ('StartupB',  'Frontend Developer', 'JavaScript',  TRUE),
  ('StartupB',  'DevOps Engineer',    'Deployment',  TRUE),
  ('StartupB',  'DevOps Engineer',    'Git',         TRUE),
  ('Corp C',    'Data Analyst Intern','SQL',         TRUE),
  ('Corp C',    'Data Analyst Intern','Data Visualisation', TRUE),
  ('Corp C',    'QA Engineer',        'Testing',     TRUE)
) AS m(company_name, vacancy_title, skill_name, mandatory)
JOIN companies c ON c.name = m.company_name
JOIN vacancies  v ON v.company_id = c.id AND v.title = m.vacancy_title
JOIN skills     s ON s.name = m.skill_name
ON CONFLICT (vacancy_id, skill_id) DO NOTHING;

-- ---------- HR: TIM ----------
-- user_id dibiarkan NULL: anggota ini catatan tim, bukan akun login.
-- Untuk membuat anggota yang bisa login, jalankan tools/create_hr.php.
INSERT INTO company_recruiters (company_id, name, email, role, role_label, status)
SELECT c.id, m.name, m.email, m.role, m.role_label, m.status
FROM (VALUES
  ('PT Tech Indonesia', 'Aditya Pratama', 'aditya@techindonesia.example', 'lead',       'Hiring Lead',      'active'),
  ('PT Tech Indonesia', 'Nadia Putri',    'nadia@techindonesia.example',  'recruiter',  'Senior Recruiter', 'active'),
  ('PT Tech Indonesia', 'Rani Wulandari', 'rani@techindonesia.example',   'recruiter',  'Recruiter',        'active'),
  ('PT Tech Indonesia', 'Bima Santoso',   'bima@techindonesia.example',   'recruiter',  'Recruiter',        'inactive'),
  ('PT Tech Indonesia', 'Dimas Saputra',  'dimas@techindonesia.example',  'recruiter',  'Recruiter',        'invited')
) AS m(company_name, name, email, role, role_label, status)
JOIN companies c ON c.name = m.company_name
ON CONFLICT (company_id, email) DO NOTHING;

-- ---------- MAHASISWA CONTOH ----------
-- profile.password_hash SENGAJA tidak valid (lihat catatan di header).
INSERT INTO users (name, email, password_hash, role, status) VALUES
  ('Geraldi Rama',  'geraldi@demo.skillbridge.test',  '!!demo-not-a-verifiable-hash!!', 'user', 'active'),
  ('Siti Aminah',   'siti@demo.skillbridge.test',     '!!demo-not-a-verifiable-hash!!', 'user', 'active'),
  ('Dimas Saputra', 'dimas.student@demo.skillbridge.test', '!!demo-not-a-verifiable-hash!!', 'user', 'active'),
  ('Lia Anggraini', 'lia@demo.skillbridge.test',      '!!demo-not-a-verifiable-hash!!', 'user', 'suspended')
ON CONFLICT (email) DO NOTHING;

-- Profil: dua mahasiswa opt-in publik, dua tidak.
-- Kolom is_public dan public_token sebelumnya tidak pernah dipakai kode;
-- konsol HR menjadikannya sumber data kandidat.
INSERT INTO student_profiles (user_id, target_career_id, institution, program, semester, location, bio, is_public, public_token)
SELECT u.id, c.id, m.institution, m.program, m.semester, m.location, m.bio, m.is_public,
       CASE WHEN m.is_public THEN gen_random_uuid() ELSE NULL END
FROM (VALUES
  ('Geraldi Rama',  'Backend Developer',  'Universitas Demo', 'Informatika', '6', 'Jakarta',
   'Suka membangun API dan belajar PostgreSQL.',         TRUE),
  ('Siti Aminah',   'Frontend Developer', 'Universitas Demo', 'Informatika', '7', 'Bandung',
   'Fokus pada antarmuka yang aksesibel.',               TRUE),
  ('Dimas Saputra', 'Data Analyst',       'Institut Data',    'Statistika',  '5', 'Surabaya',
   'Mengolah data dengan Python dan SQL.',               FALSE),
  ('Lia Anggraini', 'UI/UX Designer',     'Universitas Demo', 'Multimedia',  '4', 'Yogyakarta',
   'Meneliti pengguna dan membuat prototipe.',           FALSE)
) AS m(user_name, career_name, institution, program, semester, location, bio, is_public)
JOIN users u   ON u.name = m.user_name
LEFT JOIN careers c ON c.name = m.career_name
ON CONFLICT (user_id) DO NOTHING;

-- Skill yang sudah dimiliki (semua manual/confirmed; sumber cv_extracted
-- baru dipakai setelah fitur CV selesai).
INSERT INTO user_skills (user_id, skill_id, source, status)
SELECT u.id, s.id, 'manual', 'confirmed'
FROM (VALUES
  ('Geraldi Rama',  'PHP'),
  ('Geraldi Rama',  'SQL'),
  ('Geraldi Rama',  'Git'),
  ('Geraldi Rama',  'REST APIs'),
  ('Siti Aminah',   'HTML'),
  ('Siti Aminah',   'CSS'),
  ('Siti Aminah',   'JavaScript'),
  ('Siti Aminah',   'Responsive Design'),
  ('Dimas Saputra', 'Excel'),
  ('Dimas Saputra', 'SQL'),
  ('Lia Anggraini', 'Figma'),
  ('Lia Anggraini', 'Wireframing')
) AS m(user_name, skill_name)
JOIN users u ON u.name = m.user_name
JOIN skills s ON s.name = m.skill_name
ON CONFLICT (user_id, skill_id) DO NOTHING;

-- Evidence + relasinya ke skill.
INSERT INTO evidence (user_id, source_type, title, description, metadata)
SELECT u.id, m.source_type::evidence_source_type, m.title, m.description,
       jsonb_build_object('date', m.date, 'level', m.level)
FROM (VALUES
  ('Geraldi Rama',  'project',     'Sistem Inventaris Gudang',   'REST API PHP dengan PostgreSQL.',          '2026-02', 'Intermediate'),
  ('Geraldi Rama',  'certificate', 'Dicoding: Fundamental API',  'Menyelesaikan jalur belajar REST API.',    '2026-01', 'Beginner'),
  ('Siti Aminah',   'portfolio',   'Landing Page Responsif',     'Layout mobile-first tanpa framework.',     '2026-03', 'Intermediate'),
  ('Siti Aminah',   'project',     'Dashboard Chart Accesible',  'Grafik dapat dinavigasi keyboard.',        '2026-03', 'Advanced'),
  ('Dimas Saputra', 'certificate', 'Google Data Analytics',     'Studi kasus pembersihan dan visualisasi.',  '2026-02', 'Intermediate'),
  ('Lia Anggraini', 'project',     'Riset UX Aplikasi Kuliah',   'Wawancara delapan mahasiswa.',             '2026-04', 'Intermediate')
) AS m(user_name, source_type, title, description, date, level)
JOIN users u ON u.name = m.user_name
WHERE NOT EXISTS (SELECT 1 FROM evidence ev WHERE ev.title = m.title);

-- Relasi evidence -> skill (satu evidence boleh membuktikan banyak skill).
INSERT INTO evidence_skills (evidence_id, skill_id)
SELECT ev.id, s.id
FROM (VALUES
  ('Sistem Inventaris Gudang',   'PHP'),
  ('Sistem Inventaris Gudang',   'SQL'),
  ('Sistem Inventaris Gudang',   'REST APIs'),
  ('Dicoding: Fundamental API',  'REST APIs'),
  ('Landing Page Responsif',     'HTML'),
  ('Landing Page Responsif',     'CSS'),
  ('Landing Page Responsif',     'Responsive Design'),
  ('Dashboard Chart Accesible',  'JavaScript'),
  ('Google Data Analytics',      'Excel'),
  ('Google Data Analytics',      'SQL'),
  ('Riset UX Aplikasi Kuliah',   'Figma'),
  ('Riset UX Aplikasi Kuliah',   'Wireframing')
) AS m(evidence_title, skill_name)
JOIN evidence ev ON ev.title = m.evidence_title
JOIN skills    s ON s.name   = m.skill_name
ON CONFLICT (evidence_id, skill_id) DO NOTHING;

-- ---------- HR: PIPELINE KANDIDAT ----------
-- Kandidat hanya boleh berasal dari profil publik. Karena itu INSERT ini
-- membaca v_public_candidates, bukan daftar nama yang ditulis keras.
INSERT INTO candidate_reviews (company_id, user_id, vacancy_id, status, note)
SELECT c.id, pc.id, v.id, m.status, m.note
FROM (VALUES
  ('PT Tech Indonesia', 'Backend Developer',  'Geraldi Rama', 'reviewing', 'Portfolio relevan, lanjut ke review skill.'),
  ('PT Tech Indonesia', 'Frontend Developer', 'Siti Aminah',  'interview', 'Jadwalkan wawancara teknis.'),
  ('PT Tech Indonesia', 'Backend Developer',  'Siti Aminah',  'new',       NULL)
) AS m(company_name, vacancy_title, user_name, status, note)
JOIN companies c            ON c.name = m.company_name
JOIN vacancies v            ON v.company_id = c.id AND v.title = m.vacancy_title
JOIN v_public_candidates pc ON pc.name = m.user_name
ON CONFLICT (company_id, user_id, COALESCE(vacancy_id, 0)) DO NOTHING;

-- ---------- HR: AKTIVITAS ----------
-- Sumber: recruiter-activity.json. created_at dipatok relatif supaya
-- label waktu di UI tetap masuk akal.
INSERT INTO activity_log (company_id, actor, action, text, created_at)
SELECT c.id, m.actor, m.action, m.text,
       CURRENT_TIMESTAMP - (m.age_days || ' days')::interval
FROM (VALUES
  ('PT Tech Indonesia', 'Nadia Putri',    'interview', 'mengundang Geraldi Rama ke wawancara',                 0),
  ('PT Tech Indonesia', 'Aditya Pratama', 'role',      'menambahkan target role "DevOps Engineer"',            0),
  ('PT Tech Indonesia', 'Rani Wulandari', 'review',    'meninjau portofolio 5 kandidat Backend',               1),
  ('PT Tech Indonesia', 'Bima Santoso',   'role',      'memperbarui kebutuhan skill Frontend Developer',       1),
  ('PT Tech Indonesia', 'Nadia Putri',    'review',    'meninjau evidence 3 kandidat UI/UX Designer',          2),
  ('PT Tech Indonesia', 'Aditya Pratama', 'team',      'mengundang Dimas Saputra bergabung sebagai Recruiter', 3),
  ('PT Tech Indonesia', 'Rani Wulandari', 'review',    'menutup pipeline kandidat Data Analyst',               4)
) AS m(company_name, actor, action, text, age_days)
JOIN companies c ON c.name = m.company_name
WHERE NOT EXISTS (
    SELECT 1 FROM activity_log a
    WHERE a.company_id = c.id AND a.text = m.text
);

COMMIT;

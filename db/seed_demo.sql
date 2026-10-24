-- ============================================================
-- SkillBridge Demo Seed (PostgreSQL)
-- Data contoh untuk konsol admin dan direktori profil publik. Opsional.
-- Sifat file: aditif dan idempoten. Tidak ada DROP, tidak ada
-- DELETE. Aman dijalankan berulang kali pada database yang sama.
--
-- Pemakaian:
--   psql skillbridge -f db/schema.sql
--   psql skillbridge -f db/seed_demo.sql
--
-- Sumber data: konten editorial contoh dan profil mahasiswa contoh.
--
-- PENTANG TENTANG KREDENSIAL:
-- Baris users di bawah memakai password_hash yang SENGAJA tidak valid,
-- yaitu string '!!demo-not-a-verifiable-hash!!'. password_verify()
-- terhadap string itu selalu false, jadi akun demo tidak bisa dipakai
-- login. Untuk akun yang bisa login, jalankan tools/create_admin.php.
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

-- ---------- MAHASISWA CONTOH ----------
-- profile.password_hash SENGAJA tidak valid (lihat catatan di header).
INSERT INTO users (name, email, password_hash, role, status) VALUES
  ('Geraldi Rama',  'geraldi@demo.skillbridge.test',  '!!demo-not-a-verifiable-hash!!', 'user', 'active'),
  ('Siti Aminah',   'siti@demo.skillbridge.test',     '!!demo-not-a-verifiable-hash!!', 'user', 'active'),
  ('Dimas Saputra', 'dimas.student@demo.skillbridge.test', '!!demo-not-a-verifiable-hash!!', 'user', 'active'),
  ('Lia Anggraini', 'lia@demo.skillbridge.test',      '!!demo-not-a-verifiable-hash!!', 'user', 'suspended')
ON CONFLICT (email) DO NOTHING;

-- Profil: dua mahasiswa opt-in publik, dua tidak. Hanya profil publik
-- aktif yang muncul di direktori; kolom public_token menjadi identitas URL.
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

COMMIT;

-- SkillBridge seed data (dibuat dari public/data/skills.json & careers.json)
-- Aman dijalankan berulang kali: memakai ON CONFLICT DO NOTHING.
-- Jalankan SETELAH db/schema.sql.

BEGIN;

-- Skills
INSERT INTO skills (name, category) VALUES
  ('PHP', 'Programming'),
  ('Python', 'Programming'),
  ('SQL', 'Database'),
  ('PostgreSQL', 'Database'),
  ('HTML', 'Web'),
  ('CSS', 'Web'),
  ('JavaScript', 'Web'),
  ('Responsive Design', 'Web'),
  ('REST APIs', 'Web'),
  ('Git', 'Tools'),
  ('Figma', 'Tools'),
  ('Excel', 'Data'),
  ('Data Visualisation', 'Data'),
  ('Statistics', 'Data'),
  ('User Research', 'Design'),
  ('Wireframing', 'Design'),
  ('Prototyping', 'Design'),
  ('Usability Testing', 'Design')
ON CONFLICT (name) DO NOTHING;

-- Careers
INSERT INTO careers (name, description) VALUES
  ('Backend Developer', 'Build APIs, databases, and server-side services for web products.'),
  ('Frontend Developer', 'Create accessible, responsive interfaces for websites and web apps.'),
  ('Data Analyst', 'Prepare data, explore patterns, and explain useful findings clearly.'),
  ('UI/UX Designer', 'Plan simple digital experiences and create clear interface designs.')
ON CONFLICT (name) DO NOTHING;

-- Skill yang dibutuhkan tiap karier
INSERT INTO career_skill_requirements (career_id, skill_id)
SELECT c.id, s.id
FROM (VALUES
  ('Backend Developer', 'PHP'),
  ('Backend Developer', 'SQL'),
  ('Backend Developer', 'PostgreSQL'),
  ('Backend Developer', 'REST APIs'),
  ('Backend Developer', 'Git'),
  ('Frontend Developer', 'HTML'),
  ('Frontend Developer', 'CSS'),
  ('Frontend Developer', 'JavaScript'),
  ('Frontend Developer', 'Responsive Design'),
  ('Frontend Developer', 'Git'),
  ('Data Analyst', 'Excel'),
  ('Data Analyst', 'SQL'),
  ('Data Analyst', 'Python'),
  ('Data Analyst', 'Data Visualisation'),
  ('Data Analyst', 'Statistics'),
  ('UI/UX Designer', 'User Research'),
  ('UI/UX Designer', 'Wireframing'),
  ('UI/UX Designer', 'Figma'),
  ('UI/UX Designer', 'Prototyping'),
  ('UI/UX Designer', 'Usability Testing')
) AS m(career_name, skill_name)
JOIN careers c ON c.name = m.career_name
JOIN skills  s ON s.name = m.skill_name
ON CONFLICT (career_id, skill_id) DO NOTHING;

COMMIT;

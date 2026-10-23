// api.js - SATU-SATUNYA tempat pengambilan data untuk seluruh halaman.
// Mode dummy: baca data/*.json + localStorage. Saat backend siap, ganti isi file ini
// dengan fetch ke endpoint asli (public/api/*.php); halaman tidak perlu berubah.
// Jalankan dari root repo agar ../data/*.json dapat dilayani:
//   php -S localhost:8000        lalu buka  http://localhost:8000/public/dashboard.html

const API = (() => {
  const LS = {
    skills: "sb.mySkills",
    evidence: "sb.myEvidence",
    target: "sb.targetCareer",
    customSkills: "sb.customSkills",
  };

  function resolveUrl(path) {
    if (typeof window !== "undefined" && window.APP_BASE_URL !== undefined) {
      const base = window.APP_BASE_URL === "/" ? "" : window.APP_BASE_URL.replace(/\/+$/, "");
      const clean = path.replace(/^(\.\.\/)+/, "").replace(/^\/+/, "");
      return (base ? base : "") + "/" + clean;
    }
    return path;
  }

  // GET api/careers.php
  async function getCareers() {
    const res = await fetch(resolveUrl("../data/careers.json"));
    return res.json();
  }

  // GET api/skills.php  -> katalog bawaan + skill baru buatan pengguna
  async function getSkillCatalog() {
    const res = await fetch(resolveUrl("../data/skills.json"));
    const seeded = await res.json();
    return [...seeded, ...readCustomSkills()];
  }

  // POST api/skills.php  { name, category }  -> skill baru, id dibuat di server
  async function addCustomSkill(name, category) {
    const custom = readCustomSkills();
    const existing = custom.find((s) => s.name.toLowerCase() === name.toLowerCase());
    if (existing) return existing;

    // id lanjutan dari katalog bawaan agar tidak bentrok
    const seeded = await fetch(resolveUrl("../data/skills.json")).then((res) => res.json());
    const maxId = [...seeded, ...custom].reduce((max, s) => Math.max(max, s.id), 0);

    const skill = { id: maxId + 1, name, category };
    custom.push(skill);
    localStorage.setItem(LS.customSkills, JSON.stringify(custom));
    return skill;
  }

  // POST api/target.php  { career_id }
  function setTarget(id) {
    localStorage.setItem(LS.target, String(id));
  }

  // GET api/target.php
  function getTargetId() {
    const raw = localStorage.getItem(LS.target);
    return raw === null ? null : Number(raw);
  }

  // GET api/skills.php?mine=1
  // Mengembalikan skill pengguna + evidence_count + label (Strong jika >= 2 evidence).
  function getMySkills() {
    const ids = readIds(LS.skills);
    const counts = evidenceCountBySkill(ids);
    return ids.map((id) => {
      const count = counts.get(id) || 0;
      return { skill_id: id, evidence_count: count, label: count >= 2 ? "Strong" : "Growing" };
    });
  }

  // POST api/skills.php  { skill_id }
  function addSkill(id) {
    const ids = readIds(LS.skills);
    if (ids.includes(id)) return ids;
    ids.push(id);
    localStorage.setItem(LS.skills, JSON.stringify(ids));
    return ids;
  }

  // DELETE api/skills.php  { skill_id }
  function removeSkill(id) {
    const ids = readIds(LS.skills).filter((x) => x !== id);
    localStorage.setItem(LS.skills, JSON.stringify(ids));
    return ids;
  }

  // GET api/evidence.php
  function getEvidence() {
    const raw = localStorage.getItem(LS.evidence);
    return raw === null ? seedEvidence() : JSON.parse(raw);
  }

  // POST api/evidence.php  { source_type, title, description, file_url, skill_ids[] }
  function addEvidence(data) {
    const list = getEvidence();
    const id = list.reduce((max, e) => Math.max(max, e.id), 0) + 1;
    list.push({ id, ...data });
    localStorage.setItem(LS.evidence, JSON.stringify(list));
    return list;
  }

  // DELETE api/evidence.php  { id }
  function removeEvidence(id) {
    const list = getEvidence().filter((e) => e.id !== id);
    localStorage.setItem(LS.evidence, JSON.stringify(list));
    return list;
  }

  // GET api/gap.php  -> dua daftar: dimiliki vs perlu dikembangkan
  async function getSkillGap() {
    const [careers, catalog] = await Promise.all([getCareers(), getSkillCatalog()]);
    const career = careers.find((c) => c.id === getTargetId()) || null;
    if (!career) return { career: null, have: [], explore: [] };

    const mine = new Set(readIds(LS.skills));
    const byName = new Map(catalog.map((s) => [s.name, s]));
    const counts = evidenceCountBySkill([...mine]);
    const required = career.skills.map((n) => byName.get(n)).filter(Boolean);

    return {
      career,
      have: required
        .filter((s) => mine.has(s.id))
        .map((s) => ({ ...s, evidence_count: counts.get(s.id) || 0 })),
      explore: required.filter((s) => !mine.has(s.id)),
    };
  }

  // ============ Admin SkillBridge ============
  // Semua fungsi admin di bawah ini memakai data dummy di data/admin-*.json.
  // Saat backend siap, ganti tiap isi fungsi dengan fetch ke endpoint asli.

  const ADMIN_LS = {
    users: "sb.admin.users",
    jobs: "sb.admin.jobs",
    vacancies: "sb.admin.vacancies",
    content: "sb.admin.content",
  };

  // GET api/admin/users.php
  async function getAdminUsers() {
    return readAdminCollection("users", "../data/admin-users.json");
  }

  // GET api/admin/jobs.php  -> daftar akun Admin Job
  async function getJobAdmins() {
    return readAdminCollection("jobs", "../data/admin-jobs.json");
  }

  // GET api/admin/vacancies.php
  async function getVacancies() {
    return readAdminCollection("vacancies", "../data/admin-vacancies.json");
  }

  // GET api/admin/content.php
  async function getAdminContent() {
    return readAdminCollection("content", "../data/admin-content.json");
  }

  // GET api/skills.php?admin=1 -> katalog skill master (sumber: skills.json)
  async function getMasterSkills() {
    const res = await fetch(resolveUrl("../data/skills.json"));
    return res.json();
  }

    // GET api/careers.php?admin=1 -> daftar target karier master
  async function getMasterCareers() {
    return getCareers();
  }

  // GET api/admin/mapping.php -> relasi career <-> skill
  async function getCareerSkillMapping() {
    const [careers, skills] = await Promise.all([getMasterCareers(), getMasterSkills()]);
    const byName = new Map(skills.map((s) => [s.name, s]));
    const rows = [];
    let id = 1;
    for (const career of careers) {
      for (const name of career.skills || []) {
        const skill = byName.get(name);
        rows.push({
          id: id++,
          career_id: career.id,
          career_name: career.name,
          skill_id: skill ? skill.id : null,
          skill_name: name,
          skill_category: skill ? skill.category : "—",
        });
      }
    }
    return rows;
  }

  // PATCH api/admin/users.php  { id, status }
  async function setAdminRecordStatus(collection, id, status) {
    const key = ADMIN_LS[collection];
    const list = await readAdminCollection(collection, `../data/admin-${collection}.json`);
    const item = list.find((row) => row.id === id);
    if (!item) return list;
    item.status = status;
    localStorage.setItem(key, JSON.stringify(list));
    return list;
  }

  // DELETE api/admin/<collection>.php  { id }
  async function removeAdminRecord(collection, id) {
    const key = ADMIN_LS[collection];
    const list = (await readAdminCollection(collection, `../data/admin-${collection}.json`)).filter(
      (row) => row.id !== id
    );
    localStorage.setItem(key, JSON.stringify(list));
    return list;
  }

  // GET api/admin/stats.php  -> angka ringkasan dashboard
  async function getAdminStats() {
    const [users, jobs, vacancies, content] = await Promise.all([
      getAdminUsers(),
      getJobAdmins(),
      getVacancies(),
      getAdminContent(),
    ]);
    return {
      users: users.length,
      jobAdmins: jobs.length,
      openVacancies: vacancies.filter((v) => v.status === "open").length,
      publishedContent: content.filter((c) => c.status === "published").length,
    };
  }

  // ============ Recruiter / Perusahaan ============

  const REC_LS = {
    company: "sb.rec.company",
    team: "sb.rec.team",
    roles: "sb.rec.roles",
    activity: "sb.rec.activity",
    candidates: "sb.rec.candidates",
  };

  // GET api/recruiter/company.php
  async function getRecruiterCompany() {
    return readRecCollection("company", "../data/recruiter-company.json");
  }

  // GET api/recruiter/team.php
  async function getRecruiterTeam() {
    return readRecCollection("team", "../data/recruiter-team.json");
  }

  // GET api/recruiter/roles.php
  async function getRecruiterRoles() {
    return readRecCollection("roles", "../data/recruiter-roles.json");
  }

  // GET api/recruiter/activity.php
  async function getRecruiterActivity() {
    return readRecCollection("activity", "../data/recruiter-activity.json");
  }

  // GET api/recruiter/candidates.php
  async function getRecruiterCandidates() {
    return readRecCollection("candidates", "../data/recruiter-candidates.json");
  }

  // PATCH api/recruiter/team.php  { id, status }
  async function setRecruiterStatus(id, status) {
    const list = await getRecruiterTeam();
    const item = list.find((r) => r.id === id);
    if (!item) return list;
    item.status = status;
    localStorage.setItem(REC_LS.team, JSON.stringify(list));
    return list;
  }

  // DELETE api/recruiter/team.php  { id }
  async function removeRecruiterRecord(id) {
    const list = (await getRecruiterTeam()).filter((r) => r.id !== id);
    localStorage.setItem(REC_LS.team, JSON.stringify(list));
    return list;
  }

  // GET api/recruiter/stats.php
  async function getRecruiterStats() {
    const [team, roles, candidates] = await Promise.all([
      getRecruiterTeam(),
      getRecruiterRoles(),
      getRecruiterCandidates(),
    ]);
    return {
      teamSize: team.length,
      activeRecruiters: team.filter((t) => t.status === "active").length,
      openRoles: roles.length,
      totalPositions: roles.reduce((s, r) => s + (r.positions || 0), 0),
      pipelineCandidates: candidates.length,
    };
  }

  // ---- helper internal recruiter ----

  async function readRecCollection(name, url) {
    const raw = localStorage.getItem(REC_LS[name]);
    if (raw !== null) return JSON.parse(raw);
    const res = await fetch(resolveUrl(url));
    const seeded = await res.json();
    localStorage.setItem(REC_LS[name], JSON.stringify(seeded));
    return seeded;
  }

  // ---- helper internal admin ----

  // Ambil koleksi admin: localStorage kalau sudah pernah diubah, jika belum dari JSON seed.
  async function readAdminCollection(name, url) {
    const raw = localStorage.getItem(ADMIN_LS[name]);
    if (raw !== null) return JSON.parse(raw);
    const res = await fetch(resolveUrl(url));
    const seeded = await res.json();
    localStorage.setItem(ADMIN_LS[name], JSON.stringify(seeded));
    return seeded;
  }

  // ---- helper internal ----

  function readIds(key) {
    const raw = localStorage.getItem(key);
    return raw === null ? [] : JSON.parse(raw);
  }

  function readCustomSkills() {
    return readIds(LS.customSkills);
  }

  function seedEvidence() {
    localStorage.setItem(LS.evidence, JSON.stringify([]));
    return [];
  }

  function evidenceCountBySkill(skillIds) {
    const counts = new Map();
    for (const ev of getEvidence()) {
      for (const sid of ev.skill_ids || []) {
        if (skillIds.includes(sid)) counts.set(sid, (counts.get(sid) || 0) + 1);
      }
    }
    return counts;
  }

  // Nilai CSRF dari <meta name="csrf-token"> (dipakai di mode asli).
  function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : "";
  }

  return {
    getCareers,
    getSkillCatalog,
    addCustomSkill,
    setTarget,
    getTargetId,
    getMySkills,
    addSkill,
    removeSkill,
    getEvidence,
    addEvidence,
    removeEvidence,
    getSkillGap,
    csrfToken,
    // admin
    getAdminUsers,
    getJobAdmins,
    getVacancies,
    getAdminContent,
    getAdminStats,
    getMasterSkills,
    getMasterCareers,
    getCareerSkillMapping,
    setAdminRecordStatus,
    removeAdminRecord,
    // recruiter
    getRecruiterCompany,
    getRecruiterTeam,
    getRecruiterRoles,
    getRecruiterActivity,
    getRecruiterCandidates,
    setRecruiterStatus,
    removeRecruiterRecord,
    getRecruiterStats,
  };
})();

window.API = API;
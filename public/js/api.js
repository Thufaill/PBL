// api.js - SATU-SATUNYA tempat pengambilan data untuk seluruh halaman.
//
// Semua baca/tulis domain lewat endpoint PHP di public/api/ yang berbicara ke
// PostgreSQL. Tidak ada lagi data domain di localStorage dan tidak ada lagi
// berkas data/*.json: satu sumber kebenaran adalah database, sehingga dua
// browser yang login dengan akun yang sama melihat data yang sama.
//
// Kontrak untuk halaman tetap sama seperti mode dummy dulu, dengan satu
// tambahan: fungsi tulis kini async dan mengembalikan data terbaru.
//   await API.addEvidence(payload)
//   API.getEvidence()          // sinkron, dibaca dari snapshot sesi

const API = (() => {
  const BASE = (() => {
    if (typeof window !== "undefined" && window.APP_BASE_URL !== undefined) {
      return window.APP_BASE_URL === "/" ? "" : window.APP_BASE_URL.replace(/\/+$/, "");
    }
    return "";
  })();

  // Kunci localStorage peninggalan mode dummy. Domain TIDAK dibaca dari sini
  // lagi; daftar ini hanya untuk membersihkan sisa data lama satu kali.
  // Kunci sb.rec.* dipertahankan khusus sebagai pembersih konsol HR yang
  // sudah dihapus dan tidak boleh dipakai fitur baru.
  const LEGACY_KEYS = [
    "sb.mySkills", "sb.myEvidence", "sb.targetCareer", "sb.customSkills",
    "sb.profile", "sb.portfolioVisibility",
    "sb.admin.users", "sb.admin.jobs", "sb.admin.vacancies", "sb.admin.content",
    "sb.admin.config", "sb.admin.profile",
    "sb.rec.company", "sb.rec.team", "sb.rec.roles", "sb.rec.activity",
    "sb.rec.candidates", "sb.rec.company.edit",
  ];

  function purgeLegacy() {
    for (const key of Object.keys(localStorage)) {
      if (LEGACY_KEYS.includes(key) || key.startsWith("sb.rec.mapping.")) {
        localStorage.removeItem(key);
      }
    }
  }

  class ApiError extends Error {
    constructor(message, status, detail) {
      super(message);
      this.name = "ApiError";
      this.status = status;
      this.detail = detail;
    }
  }

  function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : "";
  }

  // ---- Indikator loading global ----
  // ui.js mendengarkan dua event ini untuk menampilkan progress bar. Jumlah
  // request dihitung di sini supaya halaman tidak perlu memberi tahu kapan
  // data mulai/selesai dimuat.
  let activeRequests = 0;

  function announce(name, detail) {
    if (typeof window === "undefined") return;
    window.dispatchEvent(new CustomEvent(name, { detail }));
  }

  // PATCH/DELETE dikirim sebagai POST + X-HTTP-Method-Override karena
  // sebagian shared hosting memblokir method tersebut di level web server.
  async function request(method, path, payload, options = {}) {
    const isWrite = method !== "GET";
    const init = {
      method: isWrite ? "POST" : "GET",
      credentials: "same-origin",
      headers: {},
    };
    if (isWrite) {
      if (method !== "POST") init.headers["X-HTTP-Method-Override"] = method;
      init.headers["X-CSRF-Token"] = csrfToken();
      if (payload instanceof FormData) {
        init.body = payload;
      } else {
        init.headers["Content-Type"] = "application/json";
        init.body = JSON.stringify(payload ?? {});
      }
    }

    activeRequests += 1;
    announce("sb:api:start", { method, path, activeRequests });
    try {
      let res;
      try {
        res = await fetch(BASE + path, init);
      } catch (networkError) {
        throw new ApiError("Koneksi ke server gagal. Periksa jaringan Anda.", 0, String(networkError));
      }

      const text = await res.text();
      let data;
      try {
        data = text ? JSON.parse(text) : {};
      } catch {
        // PHP warning yang tercetak di depan JSON: jangan dianggap data kosong.
        throw new ApiError(`Respons server tidak dapat dibaca (HTTP ${res.status}).`, res.status, text.slice(0, 200));
      }
      if (!res.ok || data.ok === false) {
        throw new ApiError(data.error || `Permintaan gagal (HTTP ${res.status}).`, res.status, data);
      }
      if (isWrite && !options.keepSnapshot) applyWriteSnapshot(data);
      return data;
    } finally {
      activeRequests = Math.max(0, activeRequests - 1);
      announce("sb:api:end", { method, path, activeRequests });
    }
  }

  // ---- Snapshot sesi: alasan getter mahasiswa tetap sinkron ----
  let snapshot = null;
  let sessionPromise = null;

  function applyWriteSnapshot(data) {
    if (!snapshot) return;
    if (Array.isArray(data.skills)) snapshot.skills = data.skills;
    if (Array.isArray(data.evidence)) snapshot.evidence = data.evidence;
    if ("target_career_id" in data) snapshot.target_career_id = data.target_career_id;
  }

  async function loadSession() {
    const data = await request("GET", "/api/session.php");
    snapshot = {
      user: data.user || null,
      target_career_id: data.target_career_id ?? null,
      skills: data.skills || [],
      evidence: data.evidence || [],
      profile: data.profile || {},
    };
    return snapshot;
  }

  function ensureSession() {
    if (snapshot) return Promise.resolve(snapshot);
    if (!sessionPromise) {
      sessionPromise = loadSession().finally(() => { sessionPromise = null; });
    }
    return sessionPromise;
  }

  async function refreshSession() {
    snapshot = null;
    return loadSession();
  }

  // ---- Katalog (careers + skills + categories), sekali per halaman ----
  let catalog = null;
  let catalogPromise = null;

  async function loadCatalog() {
    catalog = await request("GET", "/api/catalog.php");
    return catalog;
  }

  function ensureCatalog() {
    if (catalog) return Promise.resolve(catalog);
    if (!catalogPromise) {
      catalogPromise = loadCatalog().finally(() => { catalogPromise = null; });
    }
    return catalogPromise;
  }

  // ---- Mahasiswa ----

  async function getCareers() {
    return (await ensureCatalog()).careers;
  }

  async function getSkillCatalog() {
    return (await ensureCatalog()).skills;
  }

  async function getSkillCategories() {
    return (await ensureCatalog()).categories;
  }

  // GET api/session.php -> { user, skills, evidence, profile, target_career_id }
  function getSession() {
    return snapshot;
  }

  function getTargetId() {
    return snapshot ? snapshot.target_career_id : null;
  }

  function getMySkills() {
    return snapshot ? snapshot.skills : [];
  }

  function getEvidence() {
    return snapshot ? snapshot.evidence : [];
  }

  // POST api/skills.php { name, category } -> skill master dibuat/dipakai ulang
  async function addCustomSkill(name, category) {
    catalogPromise = null;
    await ensureSession();
    const data = await request("POST", "/api/skills.php", { name, category });
    return data.skill;
  }

  // POST api/skills.php { skill_id }
  async function addSkill(id) {
    await ensureSession();
    const data = await request("POST", "/api/skills.php", { skill_id: id });
    return data.skills;
  }

  // DELETE api/skills.php { skill_id }
  async function removeSkill(id) {
    await ensureSession();
    const data = await request("DELETE", "/api/skills.php", { skill_id: id });
    return data.skills;
  }

  // PATCH api/skills.php { skill_id, status } untuk skill hasil ekstraksi CV
  async function setSkillStatus(id, status) {
    await ensureSession();
    const data = await request("PATCH", "/api/skills.php", { skill_id: id, status });
    return data.skills;
  }

  // POST api/target.php { career_id }
  async function setTarget(careerId) {
    await ensureSession();
    return request("POST", "/api/target.php", { career_id: careerId });
  }

  async function getTarget() {
    await ensureSession();
    return request("GET", "/api/target.php");
  }

  // POST api/evidence.php { source_type, title, description, file_url, skill_ids[] }
  async function addEvidence(payload) {
    await ensureSession();
    return request("POST", "/api/evidence.php", payload);
  }

  // Nama lama yang dipakai halaman evidence.js; keduanya endpoint yang sama.
  const createEvidence = addEvidence;

  async function removeEvidence(id) {
    await ensureSession();
    return request("DELETE", "/api/evidence.php", { id });
  }

  // ---- Profil ----
  async function getProfile() {
    await ensureSession();
    return request("GET", "/api/profile.php");
  }

  async function saveProfile(patch) {
    await ensureSession();
    return request("PATCH", "/api/profile.php", patch);
  }

  async function changePassword(currentPassword, newPassword) {
    return request("POST", "/api/password.php", {
      current_password: currentPassword,
      new_password: newPassword,
    });
  }

  async function uploadPhoto(file) {
    const form = new FormData();
    form.append("photo", file);
    snapshot = null;
    return request("POST", "/api/profile-photo.php", form);
  }

  async function removePhoto() {
    snapshot = null;
    return request("DELETE", "/api/profile-photo.php", {});
  }

  // Identitas akun untuk layar pengaturan admin.
  async function getAccount() {
    return request("GET", "/api/account.php");
  }

  async function saveAccount(patch) {
    return request("PATCH", "/api/account.php", patch);
  }

  // ---- Admin SkillBridge ----

  async function getAdminStats() {
    return request("GET", "/api/admin/stats.php");
  }

  async function getAdminUsers() {
    return (await request("GET", "/api/admin/users.php")).users;
  }

  async function getAdmins() {
    return (await request("GET", "/api/admin/admins.php")).admins;
  }

  async function getAdminContent() {
    return (await request("GET", "/api/admin/content.php")).content;
  }

  async function getMasterSkills() {
    return (await request("GET", "/api/admin/skills.php")).skills;
  }

  // Kategori resmi yang diterima server (samakan dengan validasi POST/PATCH).
  async function getAdminSkillCategories() {
    return (await request("GET", "/api/admin/skills.php")).categories;
  }

  async function getMasterCareers() {
    return (await request("GET", "/api/admin/careers.php")).careers;
  }

  async function getCareerSkillMapping() {
    return (await request("GET", "/api/admin/mapping.php")).mapping;
  }

  async function addMasterSkill(payload) {
    catalogPromise = null;
    return request("POST", "/api/admin/skills.php", payload);
  }

  async function updateMasterSkill(payload) {
    catalogPromise = null;
    return request("PATCH", "/api/admin/skills.php", payload);
  }

  async function addMasterCareer(payload) {
    return request("POST", "/api/admin/careers.php", payload);
  }

  async function updateMasterCareer(payload) {
    return request("PATCH", "/api/admin/careers.php", payload);
  }

  async function addMapping(payload) {
    return request("POST", "/api/admin/mapping.php", payload);
  }

  async function updateMapping(payload) {
    return request("PATCH", "/api/admin/mapping.php", payload);
  }

  async function removeMapping(payload) {
    return request("DELETE", "/api/admin/mapping.php", payload);
  }

  async function addContent(payload) {
    return request("POST", "/api/admin/content.php", payload);
  }

  async function updateContent(payload) {
    return request("PATCH", "/api/admin/content.php", payload);
  }

  async function getSettings() {
    return (await request("GET", "/api/admin/settings.php")).settings;
  }

  async function saveSettings(settings) {
    return request("PATCH", "/api/admin/settings.php", { settings });
  }

  // Pemetaan collection lama -> endpoint, supaya pemanggil lama
  // (setAdminRecordStatus / removeAdminRecord) tidak perlu diubah.
  const ADMIN_ENDPOINT = {
    users: "/api/admin/users.php",
    admins: "/api/admin/admins.php",
    skills: "/api/admin/skills.php",
    careers: "/api/admin/careers.php",
    content: "/api/admin/content.php",
    mapping: "/api/admin/mapping.php",
  };

  function adminEndpoint(collection) {
    const path = ADMIN_ENDPOINT[collection];
    if (!path) throw new ApiError(`Koleksi admin '${collection}' tidak dikenal.`, 400);
    return path;
  }

  async function setAdminRecordStatus(collection, id, status) {
    return request("PATCH", adminEndpoint(collection), { id, status });
  }

  async function removeAdminRecord(collection, id) {
    return request("DELETE", adminEndpoint(collection), { id });
  }

  // ---- Direktori profil publik ----
  async function searchProfiles(query) {
    return request("GET", `/api/search.php?q=${encodeURIComponent(query)}`);
  }

  async function getPublicProfile(token) {
    return request("GET", `/api/public-profile.php?token=${encodeURIComponent(token)}`);
  }

  purgeLegacy();

  return {
    ApiError,
    csrfToken,
    // sesi & katalog
    ensureSession,
    refreshSession,
    ensureCatalog,
    getSession,
    getCareers,
    getSkillCatalog,
    getSkillCategories,
    // mahasiswa
    getTargetId,
    getTarget,
    setTarget,
    getMySkills,
    addSkill,
    removeSkill,
    setSkillStatus,
    addCustomSkill,
    getEvidence,
    addEvidence,
    createEvidence,
    removeEvidence,
    // profil & akun
    getProfile,
    saveProfile,
    changePassword,
    uploadPhoto,
    removePhoto,
    getAccount,
    saveAccount,
    // direktori publik
    searchProfiles,
    getPublicProfile,
    // admin
    getAdminStats,
    getAdminUsers,
    getAdmins,
    getAdminContent,
    getMasterSkills,
    getAdminSkillCategories,
    getMasterCareers,
    getCareerSkillMapping,
    addMasterSkill,
    updateMasterSkill,
    addMasterCareer,
    updateMasterCareer,
    addMapping,
    updateMapping,
    removeMapping,
    addContent,
    updateContent,
    getSettings,
    saveSettings,
    setAdminRecordStatus,
    removeAdminRecord,
  };
})();

window.API = API;

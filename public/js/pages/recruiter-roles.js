// Target Role & Kebutuhan Skill — kartu role + mapping skill dua-panel.
const { el, clear } = UI;

let allRoles = [];
let selectedRoleId = null;
let catalogSkills = [];
let connectedIds = new Set();
let mappingDirty = false;

function renderDate() {
  const months = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember",
  ];
  const now = new Date();
  document.getElementById("rec-today").textContent =
    `${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
}

function chip(label) {
  return el("span", { class: "rec-skill-chip" }, [
    el("span", { class: "rec-skill-dot" }),
    label,
  ]);
}

function renderCards() {
  const wrap = document.getElementById("rec-role-cards");
  clear(wrap);
  if (!allRoles.length) {
    wrap.append(el("p", { class: "rec-empty", text: "Belum ada target role." }));
    return;
  }
  for (const r of allRoles) {
    const card = el("button", {
      type: "button",
      class: "rec-role-card" + (r.id === selectedRoleId ? " rec-role-card--active" : ""),
      onClick: () => selectRole(r.id),
    }, [
      el("div", { class: "rec-role-card-head" }, [
        el("span", { class: "rec-role-card-name", text: r.name }),
        el("span", { class: "rec-role-card-pos", text: `${r.positions} posisi` }),
      ]),
      el("p", { class: "rec-role-card-cat", text: r.category }),
      el("div", { class: "rec-role-card-skills" }, (r.skills || []).slice(0, 3).map(chip)),
      el("div", { class: "rec-role-card-foot" }, [
        el("span", { text: `${r.matches} kandidat cocok` }),
        el("span", { class: "rec-pager-link", text: "Kelola →" }),
      ]),
    ]);
    wrap.append(card);
  }
}

// Mapping disimpan di PostgreSQL (vacancy_skills) lewat API.saveRoleMapping().
// `connectedIds` adalah draf di memori; server tetap sumber kebenaran sampai
// tombol Simpan diklik, dan Batal menarik ulang isi server.
function selectRole(id) {
  selectedRoleId = id;
  const role = allRoles.find((r) => r.id === id);
  connectedIds = new Set(role ? role.skill_ids || [] : []);
  mappingDirty = false;
  saveBtn.disabled = !role;
  cancelBtn.disabled = !role;
  renderCards();
  renderMapping(role);
}

function renderMapping(role) {
  const panel = document.getElementById("rec-mapping-panel");
  if (!role) {
    clear(panel);
    panel.append(el("p", { class: "rec-empty", text: "Pilih target role untuk mengelola mapping skill." }));
    return;
  }

  clear(panel);

  // Panel kiri: skill terhubung
  const left = el("div", { class: "rec-mapping-col" }, [
    el("h3", { class: "rec-mapping-col-title", text: "Skill Terhubung" }),
    el("p", { class: "rec-mapping-col-sub", text: `${connectedIds.size} skill dipilih untuk role ini.` }),
  ]);
  const list = el("div", { class: "rec-mapping-list" });
  if (!connectedIds.size) {
    list.append(el("p", { class: "rec-empty", text: "Belum ada skill terhubung." }));
  } else {
    for (const sid of connectedIds) {
      const s = catalogSkills.find((c) => c.id === sid);
      if (!s) continue;
      list.append(el("div", { class: "rec-mapping-item" }, [
        el("span", { class: "rec-mapping-item-name", text: s.name }),
        el("span", { class: "rec-mapping-item-cat", text: s.category }),
        el("button", {
          type: "button",
          class: "rec-pager-link rec-pager-link--danger",
          text: "Hapus",
          onClick: () => { connectedIds.delete(sid); mappingDirty = true; renderMapping(role); },
        }),
      ]));
    }
  }
  left.append(list);
  left.append(el("p", { class: "rec-info-note", text: "Skill yang terhubung menentukan kandidat mana yang muncul di pipeline role ini." }));

  // Panel kanan: katalog skill + checkbox
  const right = el("div", { class: "rec-mapping-col" }, [
    el("h3", { class: "rec-mapping-col-title", text: "Panel Data Skill" }),
    el("input", {
      type: "search",
      class: "rec-search",
      id: "rec-skill-search",
      placeholder: "Cari skill…",
      "aria-label": "Cari skill",
    }),
  ]);
  const catList = el("div", { class: "rec-mapping-list", id: "rec-skill-catalog" });
  right.append(catList);
  const count = el("p", { class: "rec-mapping-count", id: "rec-skill-count" });
  right.append(count);

  function renderCatalog() {
    const q = (document.getElementById("rec-skill-search")?.value || "").toLowerCase();
    clear(catList);
    let shown = 0;
    for (const s of catalogSkills) {
      if (q && !s.name.toLowerCase().includes(q)) continue;
      shown += 1;
      const checked = connectedIds.has(s.id);
      const row = el("label", { class: "rec-check-row" }, [
        el("input", {
          type: "checkbox",
          class: "rec-check",
          ...(checked ? { checked: "checked" } : {}),
          onChange: (e) => {
            if (e.target.checked) connectedIds.add(s.id);
            else connectedIds.delete(s.id);
            mappingDirty = true;
            renderMapping(role);
          },
        }),
        el("span", { class: "rec-check-name", text: s.name }),
        el("span", { class: "rec-check-cat", text: s.category }),
      ]);
      catList.append(row);
    }
    if (!shown) catList.append(el("p", { class: "rec-empty", text: "Tidak ada skill yang cocok." }));
    const c = document.getElementById("rec-skill-count");
    if (c) c.textContent = `${connectedIds.size} skill dipilih dari ${catalogSkills.length} tersedia`;
  }

  panel.append(left, right);
  renderCatalog();
  document.getElementById("rec-skill-search").addEventListener("input", renderCatalog);
}

async function load() {
  renderDate();
  try {
    [allRoles, catalogSkills] = await Promise.all([
      API.getRecruiterRoles(),
      API.getSkillCatalog(),
    ]);
  } catch (err) {
    allRoles = [];
    catalogSkills = [];
    UI.toast(err.message, "error");
  }
  selectedRoleId = allRoles[0]?.id ?? null;
  renderCards();
  selectRole(selectedRoleId);
}

const saveBtn = document.getElementById("btn-save-mapping");
const cancelBtn = document.getElementById("btn-cancel-mapping");

saveBtn.addEventListener("click", async () => {
  if (selectedRoleId == null) {
    UI.toast("Pilih target role terlebih dahulu.", "error");
    return;
  }
  saveBtn.disabled = true;
  try {
    await API.saveRoleMapping(selectedRoleId, [...connectedIds]);
    mappingDirty = false;
    UI.toast("Mapping skill tersimpan.");
    // Muat ulang supaya kartu role menampilkan jumlah kandidat yang cocok terbaru.
    allRoles = await API.getRecruiterRoles();
    const active = selectedRoleId;
    renderCards();
    selectRole(active);
  } catch (err) {
    UI.toast(err.message, "error");
  } finally {
    saveBtn.disabled = false;
  }
});

cancelBtn.addEventListener("click", () => {
  selectRole(selectedRoleId);
  UI.toast(mappingDirty ? "Perubahan dibatalkan." : "Tidak ada perubahan.");
});

load();

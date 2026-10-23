// Panel Perusahaan & Recruiter — dashboard utama.
const { el, clear } = UI;

// Map status/role tim ke badge pill (selaras desain Figma)
const STATUS_BADGE = {
  active: "rec-badge--indigo",
  inactive: "rec-badge--gray",
  pending: "rec-badge--pink",
};

const STATUS_LABEL = {
  active: "Aktif",
  inactive: "Nonaktif",
  pending: "Pending",
};

const ACTIVITY_BADGE = {
  interview: "rec-badge--pink",
  role: "rec-badge--indigo",
  review: "rec-badge--gray",
};

function initials(name) {
  return String(name || "")
    .split(" ")
    .map((w) => w.charAt(0))
    .join("")
    .slice(0, 2)
    .toUpperCase();
}

async function renderCompany() {
  const c = await API.getRecruiterCompany();
  document.getElementById("company-sub").textContent = `${c.name} · ${c.tagline}`;
}

async function renderStats() {
  const s = await API.getRecruiterStats();
  const grid = document.getElementById("rec-stats-grid");
  clear(grid);
  const cards = [
    { label: "Tim Recruiter", value: s.teamSize, context: `${s.activeRecruiters} aktif peninjauan kandidat` },
    { label: "Role Dibuka", value: s.openRoles, context: "Target role yang dibuka perusahaan" },
    { label: "Kursi Kosong", value: s.totalPositions, context: "Posisi yang menunggu kandidat" },
    { label: "Kandidat Pipeline", value: s.pipelineCandidates, context: "Menunggu peninjauan tim" },
    { label: "Skill Terpetakan", value: "15", primary: true, context: "Kebutuhan skill dari seluruh role aktif" },
  ];
  for (const c of cards) {
    grid.appendChild(
      el("article", { class: "rec-stat-card" }, [
        el("p", { class: "rec-stat-label", text: c.label }),
        el("p", {
          class: `rec-stat-value${c.primary ? " rec-stat-value--primary" : ""}`,
          text: String(c.value),
        }),
        el("p", { class: "rec-stat-context", text: c.context }),
      ])
    );
  }
}

async function renderTeam() {
  const [team, roles] = await Promise.all([API.getRecruiterTeam(), API.getRecruiterRoles()]);
  const active = team.filter((t) => t.status === "active").length;
  document.getElementById("team-summary").textContent =
    `${active} dari ${team.length} anggota aktif · ${roles.length} role terbuka`;

  const body = document.getElementById("rec-team-body");
  clear(body);
  if (team.length === 0) {
    body.appendChild(el("p", { class: "rec-empty", text: "Belum ada anggota tim recruiter." }));
    return;
  }
  for (const m of team.slice(0, 4)) {
    let action;
    if (m.status === "pending") {
      action = el("button", {
        type: "button",
        class: "rec-row-action",
        text: "Kirim Ulang",
        onClick: () => {
          UI.toast(`Undangan untuk ${m.name} dikirim ulang.`);
        },
      });
    } else if (m.status === "inactive") {
      action = el("button", {
        type: "button",
        class: "rec-row-action",
        text: "Aktifkan",
        onClick: async () => {
          await API.setRecruiterStatus(m.id, "active");
          UI.toast(`${m.name} diaktifkan.`);
          load();
        },
      });
    } else {
      action = el("a", {
        class: "rec-row-action",
        href: "team.php",
        text: "Kelola",
      });
    }

    body.appendChild(
      el("div", { class: "rec-row" }, [
        el("div", { class: "rec-row-name" }, [
          el("span", { class: "rec-avatar-sm", text: initials(m.name) }),
          el("div", { class: "rec-row-name-text" }, [
            el("span", { class: "rec-row-name-main", text: m.name }),
            el("span", { class: "rec-row-name-sub", text: m.email }),
          ]),
        ]),
        el("div", { class: "rec-row-role" }, [
          el("span", { class: "rec-role-text", text: m.role_label }),
        ]),
        el("div", { class: "rec-row-status" }, [
          el("span", { class: `rec-badge ${STATUS_BADGE[m.status] || "rec-badge--gray"}`, text: STATUS_LABEL[m.status] || m.status }),
        ]),
        el("div", { class: "rec-row-actions" }, [action]),
      ])
    );
  }
}

async function renderRoles() {
  const roles = await API.getRecruiterRoles();
  document.getElementById("roles-summary").textContent = `Sorotan dari ${roles.length} target role yang dibuka`;

  const body = document.getElementById("rec-roles-body");
  clear(body);
  for (const r of roles) {
    body.appendChild(
      el("div", { class: "rec-role-row" }, [
        el("div", { class: "rec-role-identity" }, [
          el("span", { class: "rec-role-name", text: r.name }),
          el("span", { class: "rec-role-sub", text: `${r.category} · ${r.positions} posisi` }),
        ]),
        el(
          "div",
          { class: "rec-role-skills" },
          (r.skills || []).map((s) => el("span", { class: "rec-skill-chip", text: s }))
        ),
        el("div", { class: "rec-role-match" }, [
          el("span", { class: "rec-role-match-value", text: String(r.matches) }),
          el("span", { class: "rec-role-match-label", text: "kandidat" }),
        ]),
      ])
    );
  }
  document.getElementById("roles-footer").textContent =
    `${roles.length} target role utama · Berdasarkan kecocokan skill kandidat`;
}

async function renderActivity() {
  const items = await API.getRecruiterActivity();
  const body = document.getElementById("rec-activity-body");
  clear(body);
  for (const a of items.slice(0, 3)) {
    body.appendChild(
      el("div", { class: "rec-activity-item" }, [
        el("span", { class: "rec-activity-time", text: a.time }),
        el("div", { class: "rec-activity-content" }, [
          el("span", { class: "rec-activity-text", text: `${a.actor} ${a.text}` }),
          el("span", {
            class: `rec-badge rec-activity-badge ${ACTIVITY_BADGE[a.type] || "rec-badge--gray"}`,
            text: a.type_label,
          }),
        ]),
      ])
    );
  }
}

function renderDate() {
  const months = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember",
  ];
  const now = new Date();
  document.getElementById("rec-today").textContent =
    `${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
}

// Fokus ke form Manajemen Tim (form undang anggota ada di sana).
function openAddRecruiter() {
  window.location.href = "team.php#invite";
}

async function load() {
  renderDate();
  try {
    await Promise.all([renderCompany(), renderStats(), renderTeam(), renderRoles(), renderActivity()]);
  } catch (err) {
    UI.toast("Gagal memuat data perusahaan: " + err.message);
  }
}

document.getElementById("btn-add-recruiter").addEventListener("click", openAddRecruiter);

load();

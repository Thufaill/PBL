// Manajemen Recruiter / Tim — tabel anggota tim + filter + pagination.
const { el, clear } = UI;

const STATUS_BADGE = {
  active: "rec-badge--indigo",
  inactive: "rec-badge--gray",
  pending: "rec-badge--pink",
};

const STATUS_LABEL = { active: "Aktif", inactive: "Nonaktif", pending: "Pending Invite" };

const PAGE_SIZE = 5;

let allTeam = [];
let page = 1;

function initials(name) {
  return String(name || "")
    .split(" ")
    .map((w) => w.charAt(0))
    .join("")
    .slice(0, 2)
    .toUpperCase();
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

async function renderStats() {
  const team = allTeam;
  const grid = document.getElementById("rec-team-stats");
  clear(grid);
  const cards = [
    { label: "Total Recruiter", value: team.length, context: `${team.filter((t) => t.status === "active").length} aktif · ${team.filter((t) => t.status === "inactive").length} nonaktif` },
    { label: "Lead & Manager", value: team.filter((t) => t.role !== "recruiter").length, context: "Akses penuh ke pipeline" },
    { label: "Undangan Pending", value: team.filter((t) => t.status === "pending").length, context: "Menunggu konfirmasi email" },
  ];
  for (const c of cards) {
    grid.appendChild(
      el("article", { class: "rec-stat-card" }, [
        el("p", { class: "rec-stat-label", text: c.label }),
        el("p", { class: "rec-stat-value", text: String(c.value) }),
        el("p", { class: "rec-stat-context", text: c.context }),
      ])
    );
  }
}

function applyFilter() {
  const q = (document.getElementById("rec-team-search")?.value || "").toLowerCase();
  const role = document.getElementById("rec-team-role-filter")?.value || "all";
  const status = document.getElementById("rec-team-status-filter")?.value || "all";
  return allTeam.filter((m) => {
    const hit = m.name.toLowerCase().includes(q) || m.email.toLowerCase().includes(q);
    return hit && (role === "all" || m.role === role) && (status === "all" || m.status === status);
  });
}

function renderActions(m, reload) {
  const buttons = [];

  if (m.status === "pending") {
    buttons.push(
      el("button", {
        type: "button",
        class: "rec-pager-link",
        text: "Kirim Ulang",
        onClick: () => UI.toast(`Undangan untuk ${m.name} dikirim ulang.`),
      })
    );
  } else if (m.status === "inactive") {
    buttons.push(
      el("button", {
        type: "button",
        class: "rec-pager-link",
        text: "Aktifkan",
        onClick: async () => {
          await API.setRecruiterStatus(m.id, "active");
          UI.toast(`${m.name} diaktifkan.`);
          reload();
        },
      })
    );
  } else {
    buttons.push(
      el("button", {
        type: "button",
        class: "rec-pager-link",
        text: "Nonaktifkan",
        onClick: async () => {
          await API.setRecruiterStatus(m.id, "inactive");
          UI.toast(`${m.name} dinonaktifkan.`);
          reload();
        },
      })
    );
  }

  if (m.role !== "lead") {
    buttons.push(
      el("button", {
        type: "button",
        class: "rec-pager-link rec-pager-link--danger",
        text: "Hapus",
        onClick: () => {
          if (!confirm(`Hapus ${m.name} dari tim recruiter?`)) return;
          API.removeRecruiterRecord(m.id).then(() => {
            UI.toast(`${m.name} dihapus dari tim.`);
            reload();
          });
        },
      })
    );
  }

  return el("div", { class: "table-actions rec-row-actions" }, buttons);
}

function render() {
  const list = applyFilter();
  const totalPages = Math.max(1, Math.ceil(list.length / PAGE_SIZE));
  if (page > totalPages) page = totalPages;

  const slice = list.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);
  const tbody = document.getElementById("rec-team-tbody");
  clear(tbody);

  if (slice.length === 0) {
    const td = el("td", { colspan: "5", class: "rec-empty", text: "Tidak ada recruiter yang cocok." });
    tbody.appendChild(el("tr", {}, [td]));
  } else {
    for (const m of slice) {
      tbody.appendChild(
        el("tr", {}, [
          el("td", {}, [
            el("div", { class: "rec-row-name" }, [
              el("span", { class: "rec-avatar-sm", text: initials(m.name) }),
              el("div", { class: "rec-row-name-text" }, [
                el("span", { class: "rec-row-name-main", text: m.name }),
                el("span", { class: "rec-row-name-sub", text: m.email }),
              ]),
            ]),
          ]),
          el("td", {}, [
            el("span", { class: "rec-role-text", text: m.role_label }),
          ]),
          el("td", {}, [
            el("span", {
              class: `rec-badge ${STATUS_BADGE[m.status] || "rec-badge--gray"}`,
              text: STATUS_LABEL[m.status] || m.status,
            }),
          ]),
          el("td", { text: UI.formatDate(m.created_at) }),
          el("td", {}, [renderActions(m, load)]),
        ])
      );
    }
  }

  document.getElementById("rec-team-page").textContent = String(page);
  document.getElementById("rec-team-prev").disabled = page <= 1;
  document.getElementById("rec-team-next").disabled = page >= totalPages;
}

function openInvite() {
  const body = [
    el("div", { class: "rec-field" }, [
      el("label", { for: "invite-name", text: "Nama Lengkap" }),
      el("input", { id: "invite-name", name: "name", type: "text", placeholder: "Nama recruiter" }),
    ]),
    el("div", { class: "rec-field" }, [
      el("label", { for: "invite-email", text: "Email Perusahaan" }),
      el("input", { id: "invite-email", name: "email", type: "email", placeholder: "nama@perusahaan.com" }),
    ]),
    el("div", { class: "rec-field" }, [
      el("label", { for: "invite-role", text: "Peran" }),
      el("select", { id: "invite-role", name: "role" }, [
        el("option", { value: "recruiter", text: "Recruiter" }),
        el("option", { value: "hiring_manager", text: "Hiring Manager" }),
      ]),
    ]),
  ];

  UI.modal({
    title: "Undang Recruiter Baru",
    body,
    submitLabel: "Kirim Undangan",
    onSubmit: (_close, form) => {
      const name = form.querySelector("#invite-name").value.trim();
      if (!name) {
        UI.toast("Nama recruiter wajib diisi.");
        return false;
      }
      UI.toast(`Undangan untuk ${name} dikirim.`);
      return true;
    },
  });
}

async function load() {
  renderDate();
  try {
    allTeam = await API.getRecruiterTeam();
    await renderStats();
    render(); document.getElementById("rec-team-tbody")?.addEventListener("click", async (e) => { const btn = e.target.closest("button"); if (!btn) return; const row = btn.closest("tr"); if (!row) return; const name = row.querySelector(".rec-col-name strong, .rec-col-name")?.textContent?.trim() || "Anggota"; if (btn.textContent.includes("Kirim Ulang")) { UI.toast(`Undangan untuk ${name} dikirim ulang.`); } });
  } catch (err) {
    UI.toast("Gagal memuat tim recruiter: " + err.message);
  }
}

document.getElementById("rec-team-search").addEventListener("input", () => {
  page = 1;
  render(); document.getElementById("rec-team-tbody")?.addEventListener("click", async (e) => { const btn = e.target.closest("button"); if (!btn) return; const row = btn.closest("tr"); if (!row) return; const name = row.querySelector(".rec-col-name strong, .rec-col-name")?.textContent?.trim() || "Anggota"; if (btn.textContent.includes("Kirim Ulang")) { UI.toast(`Undangan untuk ${name} dikirim ulang.`); } });
});
document.getElementById("rec-team-role-filter").addEventListener("change", () => {
  page = 1;
  render(); document.getElementById("rec-team-tbody")?.addEventListener("click", async (e) => { const btn = e.target.closest("button"); if (!btn) return; const row = btn.closest("tr"); if (!row) return; const name = row.querySelector(".rec-col-name strong, .rec-col-name")?.textContent?.trim() || "Anggota"; if (btn.textContent.includes("Kirim Ulang")) { UI.toast(`Undangan untuk ${name} dikirim ulang.`); } });
});
document.getElementById("rec-team-status-filter").addEventListener("change", () => {
  page = 1;
  render(); document.getElementById("rec-team-tbody")?.addEventListener("click", async (e) => { const btn = e.target.closest("button"); if (!btn) return; const row = btn.closest("tr"); if (!row) return; const name = row.querySelector(".rec-col-name strong, .rec-col-name")?.textContent?.trim() || "Anggota"; if (btn.textContent.includes("Kirim Ulang")) { UI.toast(`Undangan untuk ${name} dikirim ulang.`); } });
});
document.getElementById("rec-team-prev").addEventListener("click", () => {
  page -= 1;
  render(); document.getElementById("rec-team-tbody")?.addEventListener("click", async (e) => { const btn = e.target.closest("button"); if (!btn) return; const row = btn.closest("tr"); if (!row) return; const name = row.querySelector(".rec-col-name strong, .rec-col-name")?.textContent?.trim() || "Anggota"; if (btn.textContent.includes("Kirim Ulang")) { UI.toast(`Undangan untuk ${name} dikirim ulang.`); } });
});
document.getElementById("rec-team-next").addEventListener("click", () => {
  page += 1;
  render(); document.getElementById("rec-team-tbody")?.addEventListener("click", async (e) => { const btn = e.target.closest("button"); if (!btn) return; const row = btn.closest("tr"); if (!row) return; const name = row.querySelector(".rec-col-name strong, .rec-col-name")?.textContent?.trim() || "Anggota"; if (btn.textContent.includes("Kirim Ulang")) { UI.toast(`Undangan untuk ${name} dikirim ulang.`); } });
});
document.getElementById("btn-invite-recruiter").addEventListener("click", openInvite);

// Tombol "Tambah Recruiter" dari dashboard mengarah ke sini dengan #invite.
if (window.location.hash === "#invite") openInvite();

load();
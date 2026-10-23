// admin-admins.js - manajemen akun administrator (Admin Job / Admin SkillBridge)

(async () => {
  const { el, clear } = UI;

  const ROLE_LABEL = {
    job_admin: "Admin Job",
    skillbridge_admin: "Admin SkillBridge",
    owner: "Admin",
  };

  function initials(name) {
    return name.split(" ").map((w) => w[0]).slice(0, 2).join("").toUpperCase();
  }

  let allAdmins = [];

  async function load() {
    try {
      allAdmins = await API.getJobAdmins();
    } catch {
      allAdmins = [];
    }
    applyFilter();
  }

  function applyFilter() {
    const q = (document.getElementById("admin-search").value || "").toLowerCase();
    const role = document.getElementById("admin-role-filter").value;
    const list = allAdmins.filter((a) => {
      const matchQ =
        !q ||
        a.name.toLowerCase().includes(q) ||
        a.email.toLowerCase().includes(q) ||
        (a.company || "").toLowerCase().includes(q);
      const matchR = role === "all" || a.role === role;
      return matchQ && matchR;
    });
    render(list);
  }

  function render(list) {
    const body = document.getElementById("admins-table-body");
    clear(body);

    if (list.length === 0) {
      body.append(el("tr", {}, [el("td", { colspan: "7", class: "admin-empty", text: "Tidak ada administrator yang cocok." })]));
      return;
    }

    for (const a of list) {
      const active = a.status === "active";
      body.append(
        el("tr", {}, [
          el("td", {}, [
            el("div", { class: "admin-user-cell" }, [
              el("div", { class: "admin-user-avatar-sm", text: initials(a.name) }),
              el("div", {}, [
                el("p", { class: "admin-user-name", text: a.name }),
                el("p", { class: "admin-user-email", text: a.email }),
              ]),
            ]),
          ]),
          el("td", {}, [el("span", { class: "admin-badge-pill", text: ROLE_LABEL[a.role] || a.role })]),
          el("td", { text: a.company || "—" }),
          el("td", { text: String(a.active_vacancies ?? 0) }),
          el("td", {}, [
            el("span", {
              class: "admin-badge-pill",
              text: active ? "Aktif" : "Non-aktif",
            }),
          ]),
          el("td", { text: UI.formatDate(a.created_at) }),
          el("td", { class: "admin-text-end" }, [
            el("div", { class: "table-actions admin-actions" }, [
              el("button", {
                type: "button",
                class: "admin-icon-btn",
                "data-action": "status", "data-id": String(a.id), "aria-label": `Ubah status ${a.name}`,
                onClick: async () => {
                  const next = active ? "inactive" : "active";
                  await API.setAdminRecordStatus("jobs", a.id, next);
                  UI.toast(`Status ${a.name} diubah`);
                  load(); document.getElementById("admins-table-body")?.addEventListener("click", async (e) => { const btn = e.target.closest("button[data-action]"); if (!btn) return; const action = btn.dataset.action; const id = Number(btn.dataset.id); const a = allAdmins.find(x => x.id === id); if (!a) return; if (action === "status") { const next = a.status === "active" ? "inactive" : "active"; await API.setAdminRecordStatus("jobs", a.id, next); UI.toast(`Status ${a.name} diubah`); load(); } else if (action === "delete") { if (!confirm(`Hapus administrator ${a.name}?`)) return; await API.removeAdminRecord("jobs", a.id); UI.toast("Administrator dihapus"); load(); } });
                },
              }, [
                el("svg", { width: "14", height: "14", viewBox: "0 0 16 16", fill: "none", stroke: "currentColor", "stroke-width": "1.5", "stroke-linecap": "round", "stroke-linejoin": "round" }, [
                  el("path", { d: "M2 8a6 6 0 0111.2-3M14 8a6 6 0 01-11.2 3" }),
                  el("path", { d: "M13.5 2v3h-3M2.5 14v-3h3" }),
                ]),
              ]),
              el("button", {
                type: "button",
                class: "admin-icon-btn admin-icon-btn-danger",
                "data-action": "delete", "data-id": String(a.id), "aria-label": `Hapus ${a.name}`,
                onClick: async () => {
                  if (!confirm(`Hapus administrator ${a.name}?`)) return;
                  await API.removeAdminRecord("jobs", a.id);
                  UI.toast("Administrator dihapus");
                  load(); document.getElementById("admins-table-body")?.addEventListener("click", async (e) => { const btn = e.target.closest("button[data-action]"); if (!btn) return; const action = btn.dataset.action; const id = Number(btn.dataset.id); const a = allAdmins.find(x => x.id === id); if (!a) return; if (action === "status") { const next = a.status === "active" ? "inactive" : "active"; await API.setAdminRecordStatus("jobs", a.id, next); UI.toast(`Status ${a.name} diubah`); load(); } else if (action === "delete") { if (!confirm(`Hapus administrator ${a.name}?`)) return; await API.removeAdminRecord("jobs", a.id); UI.toast("Administrator dihapus"); load(); } });
                },
              }, [
                el("svg", { width: "14", height: "14", viewBox: "0 0 16 16", fill: "none", stroke: "currentColor", "stroke-width": "1.5", "stroke-linecap": "round", "stroke-linejoin": "round" }, [
                  el("path", { d: "M3 5h10M6 5V3.5a1 1 0 011-1h2a1 1 0 011 1V5M5.5 5l.5 9h4l.5-9" }),
                ]),
              ]),
            ]),
          ]),
        ])
      );
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    load(); document.getElementById("admins-table-body")?.addEventListener("click", async (e) => { const btn = e.target.closest("button[data-action]"); if (!btn) return; const action = btn.dataset.action; const id = Number(btn.dataset.id); const a = allAdmins.find(x => x.id === id); if (!a) return; if (action === "status") { const next = a.status === "active" ? "inactive" : "active"; await API.setAdminRecordStatus("jobs", a.id, next); UI.toast(`Status ${a.name} diubah`); load(); } else if (action === "delete") { if (!confirm(`Hapus administrator ${a.name}?`)) return; await API.removeAdminRecord("jobs", a.id); UI.toast("Administrator dihapus"); load(); } });
    document.getElementById("admin-search").addEventListener("input", applyFilter);
    document.getElementById("admin-role-filter").addEventListener("change", applyFilter);
  });
})();
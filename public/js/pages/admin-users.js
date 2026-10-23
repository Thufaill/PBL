// admin-users.js - manajemen pengguna (Admin Console)

document.addEventListener("DOMContentLoaded", () => {
  const { el, clear } = UI;

  const STATUS = {
    active: { cls: "", label: "Aktif" },
    inactive: { cls: "", label: "Non-aktif" },
    suspended: { cls: "admin-badge-danger", label: "Suspended" },
  };

  let allUsers = [];

  function initials(name) {
    return name.split(" ").map((w) => w[0]).slice(0, 2).join("").toUpperCase();
  }

  async function load() {
    try {
      allUsers = await API.getAdminUsers();
    } catch {
      allUsers = [];
    }
    applyFilter();
  }

  function applyFilter() {
    const q = (document.getElementById("user-search").value || "").toLowerCase();
    const status = document.getElementById("user-status-filter").value;
    const list = allUsers.filter((u) => {
      const matchQ =
        !q || u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q);
      const matchS = status === "all" || u.status === status;
      return matchQ && matchS;
    });
    render(list);
  }

  function render(list) {
    const body = document.getElementById("users-table-body");
    clear(body);

    if (list.length === 0) {
      body.append(
        el("tr", {}, [
          el("td", { colspan: "7", class: "admin-empty", text: "Tidak ada pengguna yang cocok." }),
        ])
      );
      return;
    }

    for (const u of list) {
      const badge = STATUS[u.status] || STATUS.active;
      body.append(
        el("tr", {}, [
          el("td", {}, [
            el("div", { class: "admin-user-cell" }, [
              el("div", { class: "admin-user-avatar-sm", text: initials(u.name) }),
              el("div", {}, [
                el("p", { class: "admin-user-name", text: u.name }),
                el("p", { class: "admin-user-email", text: u.email }),
              ]),
            ]),
          ]),
          el("td", { text: u.career_target || "—" }),
          el("td", { text: String(u.skills_count ?? 0) }),
          el("td", { text: String(u.evidence_count ?? 0) }),
          el("td", {}, [el("span", { class: `admin-badge-pill ${badge.cls}`, text: badge.label })]),
          el("td", { text: UI.formatDate(u.created_at) }),
          el("td", { class: "admin-text-end" }, [
            el("div", { class: "table-actions admin-actions" }, [
              el("button", {
                type: "button",
                class: "admin-icon-btn",
                "data-action": "edit", "data-id": String(u.id), "aria-label": `Edit ${u.name}`,
                title: "Edit",
                innerHTML:
                  '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8.5 2.5l3 3L4 13H1v-3L8.5 2.5z"/></svg>',
                onClick: () => UI.toast(`Edit ${u.name} (dummy)`),
              }),
              el("button", {
                type: "button",
                class: "admin-icon-btn admin-icon-btn-danger",
                "data-action": "delete", "data-id": String(u.id), "aria-label": `Hapus ${u.name}`,
                title: "Hapus",
                innerHTML:
                  '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3.5h10M5 3.5V2a1 1 0 011-1h2a1 1 0 011 1v1.5M3 3.5l.5 8.5h7l.5-8.5"/></svg>',
                onClick: async () => {
                  if (!confirm(`Hapus akun ${u.name}?`)) return;
                  await API.removeAdminRecord("users", u.id);
                  UI.toast("Pengguna dihapus");
                  load(); document.getElementById("users-table-body")?.addEventListener("click", async (e) => { const btn = e.target.closest("button[data-action]"); if (!btn) return; const action = btn.dataset.action; const id = Number(btn.dataset.id); const u = allUsers.find(x => x.id === id); if (!u) return; if (action === "edit") { UI.toast(`Edit ${u.name} (dummy)`); } else if (action === "delete") { if (!confirm(`Hapus akun ${u.name}?`)) return; await API.removeAdminRecord("users", u.id); UI.toast("Pengguna dihapus"); load(); } });
                },
              }),
            ]),
          ]),
        ])
      );
    }
  }

  load(); document.getElementById("users-table-body")?.addEventListener("click", async (e) => { const btn = e.target.closest("button[data-action]"); if (!btn) return; const action = btn.dataset.action; const id = Number(btn.dataset.id); const u = allUsers.find(x => x.id === id); if (!u) return; if (action === "edit") { UI.toast(`Edit ${u.name} (dummy)`); } else if (action === "delete") { if (!confirm(`Hapus akun ${u.name}?`)) return; await API.removeAdminRecord("users", u.id); UI.toast("Pengguna dihapus"); load(); } });
  document.getElementById("user-search").addEventListener("input", applyFilter);
  document.getElementById("user-status-filter").addEventListener("change", applyFilter);

  document.getElementById("btn-add-user").addEventListener("click", () => {
    UI.modal({
      title: "Tambah Pengguna",
      body: [
        el("div", { class: "field" }, [
          el("label", { for: "f-name", text: "Nama lengkap" }),
          el("input", { type: "text", id: "f-name", name: "name", placeholder: "Nama pengguna" }),
        ]),
        el("div", { class: "field" }, [
          el("label", { for: "f-email", text: "Email" }),
          el("input", {
            type: "email",
            id: "f-email",
            name: "email",
            placeholder: "email@example.com",
          }),
        ]),
      ],
      submitLabel: "Simpan",
      onSubmit: (close) => {
        UI.toast("Pengguna ditambahkan (dummy)");
        close();
      },
    });
  });
});
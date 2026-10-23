// admin-users.js - manajemen pengguna (Admin Console)
// Baca/tulis lewat api/admin/users.php: GET daftar, PATCH status, DELETE akun.
// Pendaftaran akun mahasiswa tetap lewat form publik, jadi konsol tidak
// punya endpoint "buat pengguna".

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
    } catch (err) {
      allUsers = [];
      UI.toast(err.message, "error");
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
                "data-action": "status", "data-id": String(u.id), "aria-label": `Ubah status ${u.name}`,
                title: u.status === "active" ? "Nonaktifkan" : "Aktifkan",
                innerHTML:
                  '<svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 8a6 6 0 0111.2-3M14 8a6 6 0 01-11.2 3"/><path d="M13.5 2v3h-3M2.5 14v-3h3"/></svg>',
              }),
              el("button", {
                type: "button",
                class: "admin-icon-btn admin-icon-btn-danger",
                "data-action": "delete", "data-id": String(u.id), "aria-label": `Hapus ${u.name}`,
                title: "Hapus",
                innerHTML:
                  '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3.5h10M5 3.5V2a1 1 0 011-1h2a1 1 0 011 1v1.5M3 3.5l.5 8.5h7l.5-8.5"/></svg>',
              }),
            ]),
          ]),
        ])
      );
    }
  }

  // Satu delegated handler, dipasang sekali: render() ulang baris tidak
  // menambah listener baru, jadi aksi tidak berjalan berkali-kali.
  document.getElementById("users-table-body")?.addEventListener("click", async (e) => {
    const btn = e.target.closest("button[data-action]");
    if (!btn) return;
    const u = allUsers.find((x) => x.id === Number(btn.dataset.id));
    if (!u) return;
    try {
      if (btn.dataset.action === "status") {
        const next = u.status === "active" ? "inactive" : "active";
        await API.setAdminRecordStatus("users", u.id, next);
        UI.toast(`Status ${u.name} diubah`);
      } else if (btn.dataset.action === "delete") {
        if (!confirm(`Hapus akun ${u.name}?`)) return;
        await API.removeAdminRecord("users", u.id);
        UI.toast("Pengguna dihapus");
      } else {
        return;
      }
      await load();
    } catch (err) {
      UI.toast(err.message, "error");
    }
  });

  document.getElementById("user-search").addEventListener("input", applyFilter);
  document.getElementById("user-status-filter").addEventListener("change", applyFilter);

  load();
});

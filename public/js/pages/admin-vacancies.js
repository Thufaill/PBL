// admin-vacancies.js - manajemen lowongan kerja (Admin Console)

(async () => {
  const { el, clear } = UI;

  const STATUS = {
    open: { cls: "", label: "Dibuka" },
    closed: { cls: "admin-badge-danger", label: "Ditutup" },
    draft: { cls: "", label: "Draft" },
  };

  let allVacancies = [];

  async function load() {
    try {
      allVacancies = await API.getVacancies();
    } catch {
      allVacancies = [];
    }
    applyFilter();
  }

  function applyFilter() {
    const q = (document.getElementById("vacancy-search")?.value || "").toLowerCase();
    const status = document.getElementById("vacancy-status-filter")?.value || "all";
    const list = allVacancies.filter((v) => {
      const matchQ =
        !q || v.title.toLowerCase().includes(q) || v.company.toLowerCase().includes(q);
      const matchS = status === "all" || v.status === status;
      return matchQ && matchS;
    });
    render(list);
  }

  function render(list) {
    const body = document.getElementById("vacancies-table-body");
    if (!body) return;
    clear(body);

    if (list.length === 0) {
      body.append(el("tr", {}, [el("td", { colspan: "8", class: "admin-empty", text: "Tidak ada lowongan yang cocok." })]));
      return;
    }

    for (const v of list) {
      const badge = STATUS[v.status] || STATUS.draft;
      body.append(
        el("tr", {}, [
          el("td", {}, [el("span", { class: "admin-user-name", text: v.title })]),
          el("td", { text: v.company }),
          el("td", { text: v.location }),
          el("td", {}, [el("span", { class: "admin-user-email", text: v.type })]),
          el("td", { text: String(v.applicants ?? 0) }),
          el("td", {}, [el("span", { class: `admin-badge-pill ${badge.cls}`, text: badge.label })]),
          el("td", { text: UI.formatDate(v.created_at) }),
          el("td", { class: "admin-text-end" }, [
            el("div", { class: "table-actions admin-actions" }, [
              el("button", {
                class: "admin-icon-btn", type: "button", "data-action": "status", "data-id": String(v.id), "aria-label": "Ubah status", title: "Ubah status",
                innerHTML: '<svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 8a6 6 0 0111.2-3M14 8a6 6 0 01-11.2 3"/><path d="M13.5 2v3h-3M2.5 14v-3h3"/></svg>',
                onClick: async () => {
                  const next = v.status === "open" ? "closed" : "open";
                  await API.setAdminRecordStatus("vacancies", v.id, next);
                  UI.toast(`Status "${v.title}" diubah`);
                  load(); document.getElementById("vacancies-table-body")?.addEventListener("click", async (e) => { const btn = e.target.closest("button[data-action]"); if (!btn) return; const action = btn.dataset.action; const id = Number(btn.dataset.id); const v = allVacancies.find(x => x.id === id); if (!v) return; if (action === "status") { const next = v.status === "open" ? "closed" : "open"; await API.setAdminRecordStatus("vacancies", v.id, next); UI.toast(`Status "${v.title}" diubah`); load(); } else if (action === "delete") { if (!confirm(`Hapus lowongan "${v.title}"?`)) return; await API.removeAdminRecord("vacancies", v.id); UI.toast("Lowongan dihapus"); load(); } });
                },
              }),
              el("button", {
                class: "admin-icon-btn admin-icon-btn-danger", type: "button", "data-action": "delete", "data-id": String(v.id), "aria-label": "Hapus", title: "Hapus",
                innerHTML: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3.5h10M5 3.5V2a1 1 0 011-1h2a1 1 0 011 1v1.5M3 3.5l.5 8.5h7l.5-8.5"/></svg>',
                onClick: async () => {
                  if (!confirm(`Hapus lowongan "${v.title}"?`)) return;
                  await API.removeAdminRecord("vacancies", v.id);
                  UI.toast("Lowongan dihapus");
                  load(); document.getElementById("vacancies-table-body")?.addEventListener("click", async (e) => { const btn = e.target.closest("button[data-action]"); if (!btn) return; const action = btn.dataset.action; const id = Number(btn.dataset.id); const v = allVacancies.find(x => x.id === id); if (!v) return; if (action === "status") { const next = v.status === "open" ? "closed" : "open"; await API.setAdminRecordStatus("vacancies", v.id, next); UI.toast(`Status "${v.title}" diubah`); load(); } else if (action === "delete") { if (!confirm(`Hapus lowongan "${v.title}"?`)) return; await API.removeAdminRecord("vacancies", v.id); UI.toast("Lowongan dihapus"); load(); } });
                },
              }),
            ]),
          ]),
        ])
      );
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    load(); document.getElementById("vacancies-table-body")?.addEventListener("click", async (e) => { const btn = e.target.closest("button[data-action]"); if (!btn) return; const action = btn.dataset.action; const id = Number(btn.dataset.id); const v = allVacancies.find(x => x.id === id); if (!v) return; if (action === "status") { const next = v.status === "open" ? "closed" : "open"; await API.setAdminRecordStatus("vacancies", v.id, next); UI.toast(`Status "${v.title}" diubah`); load(); } else if (action === "delete") { if (!confirm(`Hapus lowongan "${v.title}"?`)) return; await API.removeAdminRecord("vacancies", v.id); UI.toast("Lowongan dihapus"); load(); } });
    document.getElementById("vacancy-search")?.addEventListener("input", applyFilter);
    document.getElementById("vacancy-status-filter")?.addEventListener("change", applyFilter);
    document.getElementById("btn-add-vacancy")?.addEventListener("click", () => {
      UI.modal({
        title: "Lowongan Baru",
        body: [
          el("div", { class: "field" }, [
            el("label", { for: "f-vtitle", text: "Posisi" }),
            el("input", { type: "text", id: "f-vtitle", placeholder: "Contoh: Backend Developer" }),
          ]),
          el("div", { class: "field" }, [
            el("label", { for: "f-vcompany", text: "Perusahaan" }),
            el("input", { type: "text", id: "f-vcompany", placeholder: "Nama perusahaan" }),
          ]),
          el("div", { class: "field" }, [
            el("label", { for: "f-vloc", text: "Lokasi" }),
            el("input", { type: "text", id: "f-vloc", placeholder: "Jakarta / Remote" }),
          ]),
        ],
        submitLabel: "Simpan",
        onSubmit: (close) => { UI.toast("Lowongan ditambahkan (dummy)"); close(); },
      });
    });
  });
})();
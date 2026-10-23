// admin-vacancies.js - manajemen lowongan kerja (Admin Console)
// Semua baca/tulis lewat api/admin/vacancies.php (tabel vacancies).

(async () => {
  const { el, clear } = UI;

  const STATUS = {
    open: { cls: "", label: "Dibuka" },
    closed: { cls: "admin-badge-danger", label: "Ditutup" },
    draft: { cls: "", label: "Draft" },
    pending: { cls: "", label: "Menunggu" },
    rejected: { cls: "admin-badge-danger", label: "Ditolak" },
  };

  let allVacancies = [];

  async function load() {
    try {
      allVacancies = await API.getVacancies();
    } catch (err) {
      allVacancies = [];
      UI.toast(err.message, "error");
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
          el("td", { text: v.location || "—" }),
          el("td", {}, [el("span", { class: "admin-user-email", text: v.type })]),
          el("td", { text: String(v.applicants ?? 0) }),
          el("td", {}, [el("span", { class: `admin-badge-pill ${badge.cls}`, text: badge.label })]),
          el("td", { text: UI.formatDate(v.created_at) }),
          el("td", { class: "admin-text-end" }, [
            el("div", { class: "table-actions admin-actions" }, [
              el("button", {
                class: "admin-icon-btn", type: "button", "data-action": "status", "data-id": String(v.id),
                "aria-label": "Ubah status", title: "Ubah status",
                innerHTML: '<svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 8a6 6 0 0111.2-3M14 8a6 6 0 01-11.2 3"/><path d="M13.5 2v3h-3M2.5 14v-3h3"/></svg>',
              }),
              el("button", {
                class: "admin-icon-btn admin-icon-btn-danger", type: "button", "data-action": "delete", "data-id": String(v.id),
                "aria-label": "Hapus", title: "Hapus",
                innerHTML: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3.5h10M5 3.5V2a1 1 0 011-1h2a1 1 0 011 1v1.5M3 3.5l.5 8.5h7l.5-8.5"/></svg>',
              }),
            ]),
          ]),
        ])
      );
    }
  }

  function openForm() {
    UI.modal({
      title: "Lowongan Baru",
      body: [
        el("div", { class: "field" }, [
          el("label", { for: "f-vtitle", text: "Posisi" }),
          el("input", { type: "text", id: "f-vtitle", name: "title", placeholder: "Contoh: Backend Developer" }),
        ]),
        el("div", { class: "field" }, [
          el("label", { for: "f-vcompany", text: "Perusahaan" }),
          el("input", { type: "text", id: "f-vcompany", name: "company", placeholder: "Nama perusahaan" }),
        ]),
        el("div", { class: "field" }, [
          el("label", { for: "f-vloc", text: "Lokasi" }),
          el("input", { type: "text", id: "f-vloc", name: "location", placeholder: "Jakarta / Remote" }),
        ]),
        el("div", { class: "field" }, [
          el("label", { for: "f-vtype", text: "Tipe" }),
          el("input", { type: "text", id: "f-vtype", name: "type", placeholder: "Full-time", value: "Full-time" }),
        ]),
        el("div", { class: "field" }, [
          el("label", { for: "f-vpos", text: "Jumlah posisi" }),
          el("input", { type: "number", id: "f-vpos", name: "positions", min: "1", value: "1" }),
        ]),
      ],
      submitLabel: "Simpan",
      onSubmit: async (close, form) => {
        try {
          await API.addVacancy({
            title: form.elements.title.value.trim(),
            company: form.elements.company.value.trim(),
            location: form.elements.location.value.trim(),
            type: form.elements.type.value.trim(),
            positions: Number(form.elements.positions.value) || 1,
            status: "draft",
          });
          UI.toast("Lowongan ditambahkan");
        } catch (err) {
          UI.toast(err.message, "error");
          return false;
        }
      },
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    load();

    // Satu delegated handler untuk seluruh tombol tabel; render() hanya
    // membangun ulang baris, tidak memasang listener baru.
    document.getElementById("vacancies-table-body")?.addEventListener("click", async (e) => {
      const btn = e.target.closest("button[data-action]");
      if (!btn) return;
      const v = allVacancies.find((x) => x.id === Number(btn.dataset.id));
      if (!v) return;
      try {
        if (btn.dataset.action === "status") {
          const next = v.status === "open" ? "closed" : "open";
          await API.setAdminRecordStatus("vacancies", v.id, next);
          UI.toast(`Status "${v.title}" diubah`);
        } else if (btn.dataset.action === "delete") {
          if (!confirm(`Hapus lowongan "${v.title}"?`)) return;
          await API.removeAdminRecord("vacancies", v.id);
          UI.toast("Lowongan dihapus");
        } else {
          return;
        }
        await load();
      } catch (err) {
        UI.toast(err.message, "error");
      }
    });

    document.getElementById("vacancy-search")?.addEventListener("input", applyFilter);
    document.getElementById("vacancy-status-filter")?.addEventListener("change", applyFilter);
    document.getElementById("btn-add-vacancy")?.addEventListener("click", openForm);
  });
})();

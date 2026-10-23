// admin-careers.js - manajemen target karier master (Admin Console)
// Baca/tulis lewat api/admin/careers.php (tabel careers).

(async () => {
  const { el, clear } = UI;

  let allCareers = [];

  async function load() {
    try {
      allCareers = await API.getMasterCareers();
    } catch (err) {
      allCareers = [];
      UI.toast(err.message, "error");
    }
    applyFilter();
  }

  function applyFilter() {
    const q = (document.getElementById("admin-search")?.value || "").toLowerCase();
    const list = allCareers.filter((c) => !q || c.name.toLowerCase().includes(q));
    render(list);
  }

  function render(list) {
    const body = document.getElementById("careers-table-body");
    if (!body) return;
    clear(body);

    if (list.length === 0) {
      body.append(el("tr", {}, [el("td", { colspan: "3", class: "admin-empty", text: "Tidak ada target karier." })]));
      return;
    }

    for (const c of list) {
      const chips = (c.skills || []).slice(0, 4).map((s) =>
        el("span", { class: "admin-badge-pill", style: "margin-right:4px;margin-bottom:4px;font-size:10px;height:22px;padding:0 8px", text: s })
      );
      if ((c.skills || []).length > 4) {
        chips.push(el("span", { style: "font-size:11px;color:var(--text-muted)", text: `+${c.skills.length - 4} lagi` }));
      }

      body.append(
        el("tr", {}, [
          el("td", {}, [
            el("div", { class: "admin-user-name", text: c.name }),
            el("div", { class: "admin-user-email", text: (c.description || "").slice(0, 60) + (c.description?.length > 60 ? "…" : "") }),
          ]),
          el("td", {}, [
            el("div", { style: "display:flex;flex-wrap:wrap;gap:4px" }, chips),
            el("div", { class: "admin-user-email", text: `${c.skills_count ?? (c.skills || []).length} keterampilan wajib/pendukung` }),
          ]),
          el("td", { class: "admin-text-end" }, [
            el("div", { class: "table-actions admin-actions" }, [
              el("button", {
                class: "admin-icon-btn", type: "button", "data-action": "edit", "data-id": String(c.id), "aria-label": "Edit", title: "Edit",
                innerHTML: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8.5 2.5l3 3L4 13H1v-3L8.5 2.5z"/></svg>',
              }),
              el("button", {
                class: "admin-icon-btn admin-icon-btn-danger", type: "button", "data-action": "delete", "data-id": String(c.id), "aria-label": "Hapus", title: "Hapus",
                innerHTML: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3.5h10M5 3.5V2a1 1 0 011-1h2a1 1 0 011 1v1.5M3 3.5l.5 8.5h7l.5-8.5"/></svg>',
              }),
            ]),
          ]),
        ])
      );
    }
  }

  function openForm(career) {
    const isNew = !career;
    UI.modal({
      title: isNew ? "Tambah Target Karier" : `Ubah ${career.name}`,
      body: [
        el("div", { class: "field" }, [
          el("label", { for: "f-cname", text: "Nama karier" }),
          el("input", { type: "text", id: "f-cname", name: "name", placeholder: "Contoh: Data Engineer", value: isNew ? "" : career.name }),
        ]),
        el("div", { class: "field" }, [
          el("label", { for: "f-cdesc", text: "Deskripsi singkat" }),
          el("textarea", { id: "f-cdesc", name: "description", placeholder: "Satu kalimat tentang peran ini..." }, isNew ? [] : [career.description || ""]),
        ]),
      ],
      submitLabel: "Simpan",
      onSubmit: async (close, form) => {
        const payload = {
          name: form.elements.name.value.trim(),
          description: form.elements.description.value.trim(),
        };
        try {
          if (isNew) {
            await API.addMasterCareer(payload);
            UI.toast("Karier ditambahkan");
          } else {
            await API.updateMasterCareer({ id: career.id, ...payload });
            UI.toast("Karier diperbarui");
          }
        } catch (err) {
          UI.toast(err.message, "error");
          return false;
        }
      },
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    load();

    document.getElementById("careers-table-body")?.addEventListener("click", async (e) => {
      const btn = e.target.closest("button[data-action]");
      if (!btn) return;
      const c = allCareers.find((x) => x.id === Number(btn.dataset.id));
      if (!c) return;
      if (btn.dataset.action === "edit") {
        openForm(c);
        return;
      }
      if (btn.dataset.action !== "delete") return;
      const linked = (c.skills || []).length;
      if (!confirm(`Hapus karier "${c.name}"? ${linked} relasi keterampilan ikut terhapus.`)) return;
      try {
        await API.removeAdminRecord("careers", c.id);
        UI.toast("Karier dihapus");
        await load();
      } catch (err) {
        UI.toast(err.message, "error");
      }
    });

    document.getElementById("admin-search")?.addEventListener("input", applyFilter);
    document.getElementById("btn-add-career")?.addEventListener("click", () => openForm(null));
  });
})();

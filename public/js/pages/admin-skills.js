// admin-skills.js - manajemen keterampilan master (Admin Console)
// Semua baca/tulis lewat api/admin/skills.php (tabel skills). Daftar kategori
// diambil dari server supaya form menawarkan nilai yang sama dengan validasi.

(async () => {
  const { el, clear } = UI;

  let allSkills = [];
  let categories = [];

  async function load() {
    try {
      allSkills = await API.getMasterSkills();
    } catch (err) {
      allSkills = [];
      UI.toast(err.message, "error");
    }
    applyFilter();
  }

  function applyFilter() {
    const q = (document.getElementById("skill-search")?.value || "").toLowerCase();
    const cat = document.getElementById("skill-category-filter")?.value || "all";
    const wanted = cat === "__none" ? "" : cat;
    const list = allSkills.filter((s) => {
      const matchQ = !q || s.name.toLowerCase().includes(q);
      const matchCat = cat === "all" || (s.category || "") === wanted;
      return matchQ && matchCat;
    });
    render(list);
  }

  function fillCategoryFilter() {
    const select = document.getElementById("skill-category-filter");
    if (!select) return;
    clear(select);
    select.append(el("option", { value: "all", text: "Semua kategori" }));
    for (const c of categories) select.append(el("option", { value: c, text: c }));
    select.append(el("option", { value: "__none", text: "Tanpa kategori" }));
  }

  function render(list) {
    const body = document.getElementById("skills-table-body");
    if (!body) return;
    clear(body);

    if (list.length === 0) {
      body.append(el("tr", {}, [el("td", { colspan: "4", class: "admin-empty", text: "Tidak ada keterampilan." })]));
      return;
    }

    for (const s of list) {
      body.append(
        el("tr", {}, [
          el("td", { text: String(s.id) }),
          el("td", {}, [
            el("div", { class: "admin-user-cell" }, [
              el("div", { class: "admin-user-avatar-sm", text: s.name.slice(0, 2).toUpperCase() }),
              el("div", {}, [
                el("div", { class: "admin-user-name", text: s.name }),
                el("div", {
                  class: "admin-user-email",
                  text: `${s.users_count ?? 0} pengguna · ${s.careers_count ?? 0} karier`,
                }),
              ]),
            ]),
          ]),
          el("td", {}, [el("span", { class: "admin-badge-pill", text: s.category || "—" })]),
          el("td", { class: "admin-text-end" }, [
            el("div", { class: "table-actions admin-actions" }, [
              el("button", {
                class: "admin-icon-btn",
                type: "button",
                "data-action": "edit", "data-id": String(s.id), "aria-label": `Edit ${s.name}`,
                title: "Edit",
                innerHTML: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8.5 2.5l3 3L4 13H1v-3L8.5 2.5z"/></svg>',
              }),
              el("button", {
                class: "admin-icon-btn admin-icon-btn-danger",
                type: "button",
                "data-action": "delete", "data-id": String(s.id), "aria-label": `Hapus ${s.name}`,
                title: "Hapus",
                innerHTML: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3.5h10M5 3.5V2a1 1 0 011-1h2a1 1 0 011 1v1.5M5.5 6v5M8.5 6v5M3 3.5l.5 8.5h7l.5-8.5"/></svg>',
              }),
            ]),
          ]),
        ])
      );
    }
  }

  function openForm(skill) {
    const isNew = !skill;
    UI.modal({
      title: isNew ? "Tambah Keterampilan" : `Ubah ${skill.name}`,
      body: [
        el("div", { class: "field" }, [
          el("label", { for: "f-name", text: "Nama keterampilan" }),
          el("input", { type: "text", id: "f-name", name: "name", placeholder: "Contoh: REST APIs", value: isNew ? "" : skill.name }),
        ]),
        el("div", { class: "field" }, [
          el("label", { for: "f-cat", text: "Kategori" }),
          el(
            "select",
            { id: "f-cat", name: "category" },
            [el("option", { value: "", text: "Tanpa kategori" })].concat(
              categories.map((c) =>
                el("option", { value: c, text: c, ...(skill && c === skill.category ? { selected: "selected" } : {}) })
              )
            )
          ),
        ]),
        el("div", { class: "field" }, [
          el("label", { for: "f-desc", text: "Deskripsi" }),
          el("textarea", { id: "f-desc", name: "description" }, isNew ? [] : [skill.description || ""]),
        ]),
      ],
      submitLabel: "Simpan",
      onSubmit: async (close, form) => {
        const payload = {
          name: form.elements.name.value.trim(),
          category: form.elements.category.value,
          description: form.elements.description.value.trim(),
        };
        try {
          if (isNew) {
            await API.addMasterSkill(payload);
            UI.toast("Keterampilan ditambahkan");
          } else {
            await API.updateMasterSkill({ id: skill.id, ...payload });
            UI.toast("Keterampilan diperbarui");
          }
        } catch (err) {
          UI.toast(err.message, "error");
          return false;
        }
        await load();
      },
    });
  }

  async function removeSkill(s) {
    if (!confirm(`Hapus "${s.name}"? Skill ini akan dilepas dari ${s.users_count ?? 0} pengguna dan ${s.careers_count ?? 0} karier.`)) return;
    try {
      await API.removeAdminRecord("skills", s.id);
      UI.toast("Keterampilan dihapus");
      await load();
    } catch (err) {
      UI.toast(err.message, "error");
    }
  }

  document.addEventListener("DOMContentLoaded", async () => {
    try {
      categories = await API.getAdminSkillCategories();
    } catch (err) {
      UI.toast(err.message, "error");
    }
    fillCategoryFilter();
    load();

    // Satu delegated handler untuk seluruh tombol tabel; render() ulang
    // tidak menambah listener.
    document.getElementById("skills-table-body")?.addEventListener("click", (e) => {
      const btn = e.target.closest("button[data-action]");
      if (!btn) return;
      const s = allSkills.find((x) => x.id === Number(btn.dataset.id));
      if (!s) return;
      if (btn.dataset.action === "edit") openForm(s);
      else if (btn.dataset.action === "delete") removeSkill(s);
    });

    document.getElementById("skill-search")?.addEventListener("input", applyFilter);
    document.getElementById("skill-category-filter")?.addEventListener("change", applyFilter);
    document.getElementById("btn-add-skill")?.addEventListener("click", () => openForm(null));
  });
})();

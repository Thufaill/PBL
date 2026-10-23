// admin-mapping.js - relasi career <-> skill (Admin Console)
// Semua baca/tulis lewat api/admin/mapping.php (tabel career_skill_requirements).

(async () => {
  const { el, clear } = UI;

  let allRows = [];
  let careers = [];
  let skills = [];

  async function load() {
    try {
      allRows = await API.getCareerSkillMapping();
    } catch (err) {
      allRows = [];
      UI.toast(err.message, "error");
    }
    fillCareerFilter();
    applyFilter();
  }

  function fillCareerFilter() {
    const sel = document.getElementById("mapping-career-filter");
    if (!sel) return;
    clear(sel);
    sel.append(el("option", { value: "all", text: "Semua karier" }));
    for (const c of careers) sel.append(el("option", { value: c.name, text: c.name }));
  }

  function applyFilter() {
    const q = (document.getElementById("admin-search")?.value || "").toLowerCase();
    const career = document.getElementById("mapping-career-filter")?.value || "all";
    const list = allRows.filter((r) => {
      const matchQ =
        !q ||
        r.career_name.toLowerCase().includes(q) ||
        r.skill_name.toLowerCase().includes(q);
      const matchC = career === "all" || r.career_name === career;
      return matchQ && matchC;
    });
    render(list);
  }

  function render(list) {
    const body = document.getElementById("mapping-table-body");
    if (!body) return;
    clear(body);

    if (list.length === 0) {
      body.append(el("tr", {}, [el("td", { colspan: "4", class: "admin-empty", text: "Belum ada relasi." })]));
      return;
    }

    for (const r of list) {
      body.append(
        el("tr", {}, [
          el("td", {}, [el("span", { class: "admin-badge-pill", style: "font-size:12px;height:28px;padding:0 12px", text: r.career_name })]),
          el("td", {}, [el("span", { class: "admin-user-name", text: r.skill_name })]),
          el("td", {}, [el("span", { class: "admin-user-email", text: r.skill_category || "—" })]),
          el("td", { class: "admin-text-end" }, [
            el("div", { class: "table-actions admin-actions" }, [
              el("button", {
                class: "admin-icon-btn",
                type: "button",
                "data-action": "toggle", "data-id": `${r.career_id}_${r.skill_id}`,
                "aria-label": r.is_mandatory ? "Jadikan pendukung" : "Jadikan wajib",
                title: r.is_mandatory ? "Wajib — klik jadi pendukung" : "Pendukung — klik jadi wajib",
                innerHTML: r.is_mandatory
                  ? '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 7.5l3.5 3.5L12 3.5"/></svg>'
                  : '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="7" cy="7" r="5"/></svg>',
              }),
              el("button", {
                class: "admin-icon-btn admin-icon-btn-danger",
                type: "button",
                "data-action": "delete", "data-id": `${r.career_id}_${r.skill_id}`, "aria-label": "Hapus relasi",
                title: "Hapus relasi",
                innerHTML: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3.5h10M5 3.5V2a1 1 0 011-1h2a1 1 0 011 1v1.5M3 3.5l.5 8.5h7l.5-8.5"/></svg>',
              }),
            ]),
          ]),
        ])
      );
    }
  }

  function rowByKey(key) {
    return allRows.find((x) => `${x.career_id}_${x.skill_id}` === key);
  }

  function openForm() {
    UI.modal({
      title: "Tambah Relasi",
      body: [
        el("div", { class: "field" }, [
          el("label", { for: "f-mcareer", text: "Target karier" }),
          el(
            "select",
            { id: "f-mcareer", name: "career_id" },
            careers.map((c) => el("option", { value: String(c.id), text: c.name }))
          ),
        ]),
        el("div", { class: "field" }, [
          el("label", { for: "f-mskill", text: "Keterampilan" }),
          el(
            "select",
            { id: "f-mskill", name: "skill_id" },
            skills.map((s) => el("option", { value: String(s.id), text: `${s.name}${s.category ? " · " + s.category : ""}` }))
          ),
        ]),
      ],
      submitLabel: "Simpan",
      onSubmit: async (close, form) => {
        try {
          await API.addMapping({
            career_id: Number(form.elements.career_id.value),
            skill_id: Number(form.elements.skill_id.value),
          });
          UI.toast("Relasi ditambahkan");
        } catch (err) {
          UI.toast(err.message, "error");
          return false;
        }
        await load();
      },
    });
  }

  document.addEventListener("DOMContentLoaded", async () => {
    try {
      [careers, skills] = await Promise.all([API.getMasterCareers(), API.getMasterSkills()]);
    } catch (err) {
      UI.toast(err.message, "error");
    }
    load();

    document.getElementById("mapping-table-body")?.addEventListener("click", async (e) => {
      const btn = e.target.closest("button[data-action]");
      if (!btn) return;
      const r = rowByKey(btn.dataset.id);
      if (!r) return;
      try {
        if (btn.dataset.action === "delete") {
          if (!confirm(`Hapus relasi "${r.career_name} → ${r.skill_name}"?`)) return;
          await API.removeMapping({ career_id: r.career_id, skill_id: r.skill_id });
          UI.toast("Relasi dihapus");
        } else if (btn.dataset.action === "toggle") {
          await API.updateMapping({
            career_id: r.career_id,
            skill_id: r.skill_id,
            is_mandatory: !r.is_mandatory,
          });
          UI.toast("Kewajiban skill diperbarui");
        } else {
          return;
        }
        await load();
      } catch (err) {
        UI.toast(err.message, "error");
      }
    });

    document.getElementById("admin-search")?.addEventListener("input", applyFilter);
    document.getElementById("mapping-career-filter")?.addEventListener("change", applyFilter);
    document.getElementById("btn-add-mapping")?.addEventListener("click", openForm);
  });
})();

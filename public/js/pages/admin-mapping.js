// admin-mapping.js - relasi career <-> skill (Owner Console)

(async () => {
  const { el, clear } = UI;

  let allRows = [];

  async function load() {
    try {
      allRows = await API.getCareerSkillMapping();
    } catch {
      allRows = [];
    }
    fillCareerFilter();
    applyFilter();
  }

  function fillCareerFilter() {
    const sel = document.getElementById("mapping-career-filter");
    if (!sel) return;
    clear(sel);
    sel.append(el("option", { value: "all", text: "Semua karier" }));
    const careers = [...new Set(allRows.map((r) => r.career_name))].sort();
    for (const c of careers) sel.append(el("option", { value: c, text: c }));
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
          el("td", {}, [el("span", { class: "admin-user-email", text: r.skill_category })]),
          el("td", { class: "admin-text-end" }, [
            el("div", { class: "table-actions admin-actions" }, [
              el("button", {
                class: "admin-icon-btn admin-icon-btn-danger",
                type: "button",
                "data-action": "delete", "data-id": `${r.career_id}_${r.skill_id}`, "aria-label": "Hapus relasi",
                title: "Hapus relasi",
                innerHTML: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3.5h10M5 3.5V2a1 1 0 011-1h2a1 1 0 011 1v1.5M3 3.5l.5 8.5h7l.5-8.5"/></svg>',
                onClick: () => UI.toast(`Relasi "${r.career_name} → ${r.skill_name}" dihapus (dummy)`),
              }),
            ]),
          ]),
        ])
      );
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    load(); document.getElementById("mapping-table-body")?.addEventListener("click", (e) => { const btn = e.target.closest("button[data-action]"); if (!btn) return; const id = btn.dataset.id; const r = allMappings.find(x => `${x.career_id}_${x.skill_id}` === id); if (!r) return; UI.toast(`Relasi "${r.career_name} ? ${r.skill_name}" dihapus (dummy)`); });
    document.getElementById("admin-search")?.addEventListener("input", applyFilter);
    document.getElementById("mapping-career-filter")?.addEventListener("change", applyFilter);
    document.getElementById("btn-add-mapping")?.addEventListener("click", () => {
      UI.modal({
        title: "Tambah Relasi",
        body: [
          el("div", { class: "field" }, [
            el("label", { for: "f-mcareer", text: "Target karier" }),
            el("select", { id: "f-mcareer", name: "career" }, ["Backend Developer", "Frontend Developer", "Data Analyst", "UI/UX Designer"].map((c) => el("option", { value: c, text: c }))),
          ]),
          el("div", { class: "field" }, [
            el("label", { for: "f-mskill", text: "Keterampilan" }),
            el("input", { type: "text", id: "f-mskill", placeholder: "Contoh: PostgreSQL" }),
          ]),
        ],
        submitLabel: "Simpan",
        onSubmit: (close) => { UI.toast("Relasi ditambahkan (dummy)"); close(); },
      });
    });
  });
})();

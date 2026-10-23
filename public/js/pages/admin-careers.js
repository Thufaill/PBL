// admin-careers.js - manajemen target karier master (Owner Console)

(async () => {
  const { el, clear } = UI;

  let allCareers = [];

  async function load() {
    try {
      allCareers = await API.getMasterCareers();
    } catch {
      allCareers = [];
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
          el("td", {}, [el("div", { style: "display:flex;flex-wrap:wrap;gap:4px" }, chips)]),
          el("td", { class: "admin-text-end" }, [
            el("div", { class: "table-actions admin-actions" }, [
              el("button", {
                class: "admin-icon-btn", type: "button", "data-action": "edit", "data-id": String(c.id), "aria-label": "Edit", title: "Edit",
                innerHTML: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M8.5 2.5l3 3L4 13H1v-3L8.5 2.5z"/></svg>',
                onClick: () => UI.toast(`Edit "${c.name}" (dummy)`),
              }),
              el("button", {
                class: "admin-icon-btn admin-icon-btn-danger", type: "button", "data-action": "delete", "data-id": String(c.id), "aria-label": "Hapus", title: "Hapus",
                innerHTML: '<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3.5h10M5 3.5V2a1 1 0 011-1h2a1 1 0 011 1v1.5M3 3.5l.5 8.5h7l.5-8.5"/></svg>',
                onClick: () => UI.toast(`Hapus "${c.name}" (dummy)`),
              }),
            ]),
          ]),
        ])
      );
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    load(); document.getElementById("careers-table-body")?.addEventListener("click", (e) => { const btn = e.target.closest("button[data-action]"); if (!btn) return; const action = btn.dataset.action; const id = Number(btn.dataset.id); const c = allCareers.find(x => x.id === id); if (!c) return; if (action === "edit") { UI.toast(`Edit "${c.name}" (dummy)`); } else if (action === "delete") { UI.toast(`Hapus "${c.name}" (dummy)`); } });
    document.getElementById("admin-search")?.addEventListener("input", applyFilter);
    document.getElementById("btn-add-career")?.addEventListener("click", () => {
      UI.modal({
        title: "Tambah Target Karier",
        body: [
          el("div", { class: "field" }, [
            el("label", { for: "f-cname", text: "Nama karier" }),
            el("input", { type: "text", id: "f-cname", placeholder: "Contoh: Data Engineer" }),
          ]),
          el("div", { class: "field" }, [
            el("label", { for: "f-cdesc", text: "Deskripsi singkat" }),
            el("textarea", { id: "f-cdesc", placeholder: "Satu kalimat tentang peran ini..." }),
          ]),
        ],
        submitLabel: "Simpan",
        onSubmit: (close) => { UI.toast("Karier ditambahkan (dummy)"); close(); },
      });
    });
  });
})();

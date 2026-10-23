// Pipeline Kandidat — tabel kandidat + search + filter + pagination.
const { el, clear } = UI;

const PAGE_SIZE = 4;

let all = [];
let page = 1;

function renderDate() {
  const months = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember",
  ];
  const now = new Date();
  document.getElementById("rec-today").textContent =
    `${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
}

function chip(value, variant = "") {
  return el("span", { class: `rec-skill-chip rec-skill-chip--sm${variant ? " " + variant : ""}` }, value);
}

function applyFilter() {
  const q = (document.getElementById("rec-cand-search")?.value || "").toLowerCase();
  const role = document.getElementById("rec-cand-role")?.value || "";
  const ev = document.getElementById("rec-cand-evidence")?.value || "";

  let list = all;
  if (q) list = list.filter((c) => c.name.toLowerCase().includes(q) || (c.matched_skills || []).some((s) => s.toLowerCase().includes(q)));
  if (role) list = list.filter((c) => c.target_role === role);
  if (ev === "high") list = list.filter((c) => c.evidence_count >= 5);
  if (ev === "low") list = list.filter((c) => c.evidence_count < 5);
  return list;
}

async function loadRoleOptions() {
  const roles = await API.getRecruiterRoles();
  const sel = document.getElementById("rec-cand-role");
  clear(sel);
  sel.append(el("option", { value: "", text: "Target Role" }));
  for (const r of roles) {
    sel.append(el("option", { value: r.name, text: r.name }));
  }
}

function render() {
  const list = applyFilter();
  const total = list.length;
  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  if (page > totalPages) page = totalPages;
  const start = (page - 1) * PAGE_SIZE + 1;
  const end = Math.min(page * PAGE_SIZE, total);
  const slice = list.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

  document.getElementById("rec-cand-count").textContent = `${total} kandidat tersedia`;
  document.getElementById("rec-cand-shown").textContent = `Menampilkan ${start}–${end} dari ${total} kandidat`;
  document.getElementById("rec-cand-page").textContent = String(page);
  document.getElementById("rec-cand-prev").disabled = page <= 1;
  document.getElementById("rec-cand-next").disabled = page >= totalPages;

  const body = document.getElementById("rec-cand-body");
  clear(body);
  if (!slice.length) {
    body.append(
      el("tr", {}, [
        el("td", { colspan: 6, class: "rec-empty", text: "Tidak ada kandidat yang cocok." }),
      ])
    );
    return;
  }
  for (const c of slice) {
    body.append(
      el("tr", { class: "rec-cand-row" }, [
        el("td", { class: "rec-cand-id" }, [
          el("div", { class: "table-actions rec-cell" }, [
            el("span", { class: "rec-avatar-sm", text: initials(c.name) }),
            el("div", { class: "rec-row-name-text" }, [
              el("span", { class: "rec-row-name-main", text: c.name }),
              el("span", { class: "rec-row-name-sub", text: c.email }),
            ]),
          ]),
        ]),
        el("td", { class: "rec-cand-role" }, [
          el("span", { class: "rec-badge rec-badge--indigo", text: c.target_role }),
        ]),
        el("td", { class: "rec-cand-skills" }, [
          el("div", { class: "rec-cell rec-cell--wrap" }, (c.matched_skills || []).map((s) => chip(s))),
        ]),
        el("td", { class: "rec-cand-gaps" }, [
          el("div", { class: "rec-cell rec-cell--wrap" }, (c.gap_skills || []).map((s) => chip(s, "rec-chip--gap"))),
        ]),
        el("td", { class: "rec-cand-evidence", text: `${c.evidence_count} Items` }),
        el("td", { class: "rec-cand-actions" }, [
          el("div", { class: "table-actions rec-cell" }, [
            el("button", { type: "button", class: "rec-cand-btn", text: "Lihat Detail", onClick: () => UI.toast(`Riwayat ${c.name} belum tersedia.`) }),
            el("button",
              { type: "button", class: "rec-cand-btn rec-cand-btn--primary", text: "Undang",
                onClick: () => UI.toast(`Undangan dikirim ke ${c.name}.`),
              }),
          ]),
        ]),
      ])
    );
  }
}

function initials(name) {
  return String(name || "")
    .split(" ")
    .map((w) => w.charAt(0))
    .join("")
    .slice(0, 2)
    .toUpperCase();
}

async function load() {
  renderDate();
  try {
    await loadRoleOptions();
    all = await API.getRecruiterCandidates();
    render();
  } catch (err) {
    UI.toast("Gagal memuat kandidat: " + err.message);
  }
}

const deb = (fn) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), 250); }; };

document.getElementById("rec-cand-search").addEventListener("input", deb(() => { page = 1; render(); }));
document.getElementById("rec-cand-role").addEventListener("change", () => { page = 1; render(); });
document.getElementById("rec-cand-evidence").addEventListener("change", () => { page = 1; render(); });
document.getElementById("rec-cand-prev").addEventListener("click", () => { page -= 1; render(); });
document.getElementById("rec-cand-next").addEventListener("click", () => { page += 1; render(); });

load();

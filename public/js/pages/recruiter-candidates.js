// Pipeline Kandidat — tabel kandidat + search + filter + pagination.
// Sumber: api/hr/candidates.php (v_public_candidates + candidate_reviews).
// Filter role dikirim ke server karena matched/gap skill dihitung per role.
const { el, clear } = UI;

const PAGE_SIZE = 4;

const REVIEW_LABEL = {
  new: "Baru",
  reviewing: "Ditinjau",
  interview: "Wawancara",
  offered: "Offered",
  rejected: "Ditolak",
  hired: "Diterima",
};

const REVIEW_BADGE = {
  new: "rec-badge--gray",
  reviewing: "rec-badge--indigo",
  interview: "rec-badge--pink",
  offered: "rec-badge--indigo",
  rejected: "rec-badge--gray",
  hired: "rec-badge--indigo",
};

let all = [];
let roles = [];
let page = 1;

function initials(name) {
  return String(name || "")
    .split(" ")
    .map((w) => w.charAt(0))
    .join("")
    .slice(0, 2)
    .toUpperCase();
}

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

function selectedRoleId() {
  return document.getElementById("rec-cand-role")?.value || "";
}

function applyFilter() {
  const q = (document.getElementById("rec-cand-search")?.value || "").toLowerCase();
  const ev = document.getElementById("rec-cand-evidence")?.value || "";

  let list = all;
  if (q) {
    list = list.filter(
      (c) =>
        c.name.toLowerCase().includes(q) ||
        c.email.toLowerCase().includes(q) ||
        (c.matched_skills || []).some((s) => s.toLowerCase().includes(q))
    );
  }
  if (ev === "high") list = list.filter((c) => c.evidence_count >= 5);
  if (ev === "low") list = list.filter((c) => c.evidence_count < 5);
  return list;
}

async function fetchCandidates() {
  const roleId = selectedRoleId();
  all = await API.getRecruiterCandidates(roleId || null);
}

function render() {
  const list = applyFilter();
  const total = list.length;
  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  if (page > totalPages) page = totalPages;
  const start = total === 0 ? 0 : (page - 1) * PAGE_SIZE + 1;
  const end = Math.min(page * PAGE_SIZE, total);
  const slice = list.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

  document.getElementById("rec-cand-count").textContent = `${total} kandidat tersedia`;
  document.getElementById("rec-cand-shown").textContent = total
    ? `Menampilkan ${start}–${end} dari ${total} kandidat`
    : "Tidak ada yang ditampilkan";
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
    const status = c.review_status || "new";
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
          el("div", { class: "rec-cell" }, [
            el("span", { class: "rec-badge rec-badge--indigo", text: c.target_role || "Belum ditentukan" }),
            el("span", { class: "rec-badge " + (REVIEW_BADGE[status] || "rec-badge--gray"), text: REVIEW_LABEL[status] || status }),
          ]),
        ]),
        el("td", { class: "rec-cand-skills" }, [
          el("div", { class: "rec-cell rec-cell--wrap" }, (c.matched_skills || []).length
            ? (c.matched_skills || []).map((s) => chip(s))
            : [el("span", { class: "rec-cell-empty", text: "Tidak ada" })]),
        ]),
        el("td", { class: "rec-cand-gaps" }, [
          el("div", { class: "rec-cell rec-cell--wrap" }, (c.gap_skills || []).length
            ? (c.gap_skills || []).map((s) => chip(s, "rec-chip--gap"))
            : [el("span", { class: "rec-cell-empty", text: "Tidak ada" })]),
        ]),
        el("td", { class: "rec-cand-evidence", text: `${c.evidence_count} item` }),
        el("td", { class: "rec-cand-actions" }, [
          el("div", { class: "table-actions rec-cell" }, [
            el("button", { type: "button", class: "rec-cand-btn", text: "Tinjau", onClick: () => openReview(c) }),
            el("button", {
              type: "button",
              class: "rec-cand-btn rec-cand-btn--primary",
              text: "Tandai Wawancara",
              onClick: () => setReview(c, "interview"),
            }),
          ]),
        ]),
      ])
    );
  }
}

async function setReview(c, status, note) {
  const roleId = Number(selectedRoleId()) || null;
  try {
    await API.setCandidateStatus({ id: c.id, status, note: note ?? c.note, vacancy_id: roleId });
    UI.toast(`Review ${c.name} diperbarui.`);
    await fetchCandidates();
    render();
  } catch (err) {
    UI.toast(err.message, "error");
  }
}

function openReview(c) {
  UI.modal({
    title: `Tinjau ${c.name}`,
    body: [
      el("div", { class: "rec-field" }, [
        el("label", { for: "review-status", text: "Status review" }),
        el(
          "select",
          { id: "review-status", name: "status" },
          Object.entries(REVIEW_LABEL).map(([value, label]) =>
            el("option", { value, text: label, ...(c.review_status === value ? { selected: "selected" } : {}) })
          )
        ),
      ]),
      el("div", { class: "rec-field" }, [
        el("label", { for: "review-note", text: "Catatan" }),
        el("textarea", { id: "review-note", name: "note", rows: 4 }, [c.note || ""]),
      ]),
    ],
    submitLabel: "Simpan Review",
    onSubmit: async (close, form) => {
      const roleId = Number(selectedRoleId()) || null;
      try {
        await API.setCandidateStatus({
          id: c.id,
          status: form.elements.status.value,
          note: form.elements.note.value.trim(),
          vacancy_id: roleId,
        });
        UI.toast(`Review ${c.name} diperbarui.`);
      } catch (err) {
        UI.toast(err.message, "error");
        return false;
      }
      await fetchCandidates();
      render();
    },
  });
}

async function loadRoleOptions() {
  roles = await API.getRecruiterRoles();
  const sel = document.getElementById("rec-cand-role");
  clear(sel);
  sel.append(el("option", { value: "", text: "Target Role" }));
  for (const r of roles) {
    sel.append(el("option", { value: String(r.id), text: r.name }));
  }
}

const deb = (fn) => { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), 250); }; };

async function load() {
  renderDate();
  try {
    await loadRoleOptions();
    await fetchCandidates();
  } catch (err) {
    all = [];
    UI.toast("Gagal memuat kandidat: " + err.message, "error");
  }
  render();
}

document.getElementById("rec-cand-search").addEventListener("input", deb(() => { page = 1; render(); }));
document.getElementById("rec-cand-role").addEventListener("change", async () => {
  page = 1;
  try {
    await fetchCandidates();
  } catch (err) {
    UI.toast(err.message, "error");
  }
  render();
});
document.getElementById("rec-cand-evidence").addEventListener("change", () => { page = 1; render(); });
document.getElementById("rec-cand-prev").addEventListener("click", () => { page -= 1; render(); });
document.getElementById("rec-cand-next").addEventListener("click", () => { page += 1; render(); });

load();

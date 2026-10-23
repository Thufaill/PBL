// Log Aktivitas Recruiter — daftar aktivitas + filter tipe.
const { el, clear } = UI;

const TYPE_BADGE = {
  interview: "rec-badge--pink",
  role: "rec-badge--indigo",
  review: "rec-badge--gray",
};

let allActivity = [];

function renderDate() {
  const months = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember",
  ];
  const now = new Date();
  document.getElementById("rec-today").textContent =
    `${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
}

function applyFilter() {
  const q = (document.getElementById("rec-act-search")?.value || "").toLowerCase();
  const type = document.getElementById("rec-act-type")?.value || "";
  return allActivity.filter((a) => {
    if (type && a.type !== type) return false;
    if (q && !`${a.actor} ${a.text}`.toLowerCase().includes(q)) return false;
    return true;
  });
}

function render() {
  const body = document.getElementById("rec-act-body");
  clear(body);
  const list = applyFilter();
  document.getElementById("rec-act-count").textContent =
    list.length ? `${list.length} aktivitas` : "";

  if (!list.length) {
    body.append(el("p", { class: "rec-empty", text: "Tidak ada aktivitas yang cocok." }));
    return;
  }

  for (const a of list) {
    body.append(
      el("div", { class: "rec-activity-item" }, [
        el("span", { class: "rec-activity-time", text: a.time }),
        el("div", { class: "rec-activity-content" }, [
          el("span", { class: "rec-activity-text", text: `${a.actor} ${a.text}` }),
          el("span", {
            class: `rec-badge rec-activity-badge ${TYPE_BADGE[a.type] || "rec-badge--gray"}`,
            text: a.type_label,
          }),
        ]),
      ])
    );
  }
}

async function load() {
  renderDate();
  try {
    allActivity = await API.getRecruiterActivity();
  } catch {
    allActivity = [];
  }
  render();
}

document.getElementById("rec-act-search").addEventListener("input", render);
document.getElementById("rec-act-type").addEventListener("change", render);

load();

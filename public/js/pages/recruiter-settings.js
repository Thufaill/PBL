// Pengaturan — tab switching + profil perusahaan lewat API.saveRecruiterCompany().
const { el } = UI;

const TABS = ["company", "email", "security", "api"];

function renderDate() {
  const months = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember",
  ];
  const now = new Date();
  document.getElementById("rec-today").textContent =
    `${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
}

function switchTab(name) {
  for (const t of TABS) {
    const active = t === name;
    document.querySelector(`.rec-tab[data-tab="${t}"]`).classList.toggle("active", active);
    document.querySelector(`.rec-tab[data-tab="${t}"]`).setAttribute("aria-selected", String(active));
    document.getElementById(`tab-${t}`).classList.toggle("active", active);
  }
}

function initTabs() {
  for (const t of TABS) {
    document
      .querySelector(`.rec-tab[data-tab="${t}"]`)
      .addEventListener("click", () => switchTab(t));
  }
}

const STATUS_LABELS = { verified: "Terverifikasi", pending: "Menunggu verifikasi", suspended: "Ditangguhkan" };

async function loadCompany() {
  const c = await API.getRecruiterCompany();
  document.getElementById("company-name").value = c.name || "";
  document.getElementById("company-email").value = c.email_domain || "";
  document.getElementById("company-location").value = c.location || "";
  document.getElementById("company-description").value = c.description || "";
  document.getElementById("company-logo-initial").textContent = c.logo_initial || "-";

  document.getElementById("company-status-badge").textContent =
    STATUS_LABELS[c.status] || c.status;
  document.getElementById("company-card-sub").textContent =
    `Informasi resmi ${c.name || "perusahaan Anda"} untuk mendukung proses rekrutmen.`;
}

async function handleSave(btn) {
  const form = document.getElementById("company-form");
  const payload = {
    name: form.querySelector("#company-name").value.trim(),
    email_domain: form.querySelector("#company-email").value.trim(),
    location: form.querySelector("#company-location").value.trim(),
    description: form.querySelector("#company-description").value.trim(),
  };
  if (!payload.name) {
    UI.toast("Nama perusahaan wajib diisi.", "error");
    return;
  }
  btn.disabled = true;
  try {
    await API.saveRecruiterCompany(payload);
    UI.toast("Pengaturan berhasil disimpan.");
    await loadCompany();
  } catch (err) {
    UI.toast(err.message, "error");
  } finally {
    btn.disabled = false;
  }
}

renderDate();
initTabs();
const saveBtn = document.getElementById("btn-save-settings");
saveBtn.addEventListener("click", () => handleSave(saveBtn));

loadCompany().catch((err) => UI.toast(err.message, "error"));

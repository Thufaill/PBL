// Pengaturan — tab switching + simpan profil perusahaan (localStorage).
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

function handleSave() {
  const form = document.getElementById("company-form");
  const updated = {
    name: form.querySelector("#company-name").value.trim(),
    email_domain: form.querySelector("#company-email").value.trim(),
    location: form.querySelector("#company-location").value.trim(),
    description: form.querySelector("#company-description").value.trim(),
  };
  localStorage.setItem("sb.rec.company.edit", JSON.stringify(updated));
  UI.toast("Pengaturan berhasil disimpan.");
}

function handleUpload() {
  UI.toast("Unggah logo akan tersedia saat backend upload file siap.");
}

renderDate();
initTabs();
document.getElementById("btn-save-settings").addEventListener("click", handleSave);
document.getElementById("btn-upload-logo").addEventListener("click", handleUpload);

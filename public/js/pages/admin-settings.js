// admin-settings.js - halaman pengaturan sistem (Admin Console)
// Profil admin -> api/account.php + api/password.php
// Konfigurasi  -> api/admin/settings.php (tabel system_settings)

document.addEventListener("DOMContentLoaded", () => {
  const formProfile = document.getElementById("form-admin-profile");
  const formConfig = document.getElementById("form-platform-config");

  // Catatan: formProfile.name menunjuk atribut name <form>, bukan input-nya,
  // jadi input identitas diambil lewat id.
  const nameInput = document.getElementById("set-name");
  const emailInput = document.getElementById("set-email");
  const currentInput = document.getElementById("set-current-password");
  const passwordInput = document.getElementById("set-password");

  async function loadProfile() {
    const a = await API.getAccount();
    nameInput.value = a.name || "";
    emailInput.value = a.email || "";
  }

  async function loadConfig() {
    const s = await API.getSettings();
    formConfig.site_name.value = s.site_name;
    formConfig.max_evidence.value = s.max_evidence;
    formConfig.default_visibility.value = s.default_visibility;
    formConfig.registration_open.checked = Boolean(s.registration_open);
  }

  function busy(form, on) {
    const bar = form.parentElement.querySelector("button[type=submit][form]");
    if (bar) bar.disabled = on;
  }

  if (formProfile) {
    formProfile.addEventListener("submit", async (e) => {
      e.preventDefault();
      const name = nameInput.value.trim();
      const email = emailInput.value.trim();
      const current = currentInput.value;
      const password = passwordInput.value;

      if (!name || !email) {
        UI.toast("Nama dan email wajib diisi.", "error");
        return;
      }
      if (password && !current) {
        UI.toast("Isi password saat ini untuk mengganti password.", "error");
        return;
      }

      busy(formProfile, true);
      try {
        await API.saveAccount({ name, email });
        if (password) {
          await API.changePassword(current, password);
        }
        currentInput.value = "";
        passwordInput.value = "";
        UI.toast("Profil administrator diperbarui");
        await loadProfile();
      } catch (err) {
        UI.toast(err.message, "error");
      } finally {
        busy(formProfile, false);
      }
    });
  }

  if (formConfig) {
    formConfig.addEventListener("submit", async (e) => {
      e.preventDefault();
      const settings = {
        site_name: formConfig.site_name.value.trim(),
        max_evidence: Number(formConfig.max_evidence.value),
        default_visibility: formConfig.default_visibility.value,
        registration_open: formConfig.registration_open.checked,
      };
      busy(formConfig, true);
      try {
        await API.saveSettings(settings);
        UI.toast("Konfigurasi platform disimpan");
        await loadConfig();
      } catch (err) {
        UI.toast(err.message, "error");
      } finally {
        busy(formConfig, false);
      }
    });
  }

  Promise.all([loadProfile(), loadConfig()]).catch((err) => UI.toast(err.message, "error"));
});

// admin-settings.js - halaman pengaturan sistem (Admin Console)

document.addEventListener("DOMContentLoaded", () => {
  const formProfile = document.getElementById("form-admin-profile");
  if (formProfile) {
    formProfile.addEventListener("submit", (e) => {
      e.preventDefault();
      const name = formProfile.name.value.trim();
      const email = formProfile.email.value.trim();
      const password = formProfile.password.value;

      if (!name || !email) {
        UI.toast("Nama dan email wajib diisi");
        return;
      }
      // dummy: simpan ke localStorage untuk preview sesi berikutnya
      localStorage.setItem(
        "sb.admin.profile",
        JSON.stringify({ name, email, password: password ? "***" : undefined })
      );
      UI.toast("Profil administrator diperbarui");
    });
  }

  const formConfig = document.getElementById("form-platform-config");
  if (formConfig) {
    formConfig.addEventListener("submit", (e) => {
      e.preventDefault();
      const config = {
        site_name: formConfig.site_name.value.trim(),
        max_evidence: Number(formConfig.max_evidence.value),
        default_visibility: formConfig.default_visibility.value,
        registration_open: formConfig.registration_open.checked,
      };
      localStorage.setItem("sb.admin.config", JSON.stringify(config));
      UI.toast("Konfigurasi platform disimpan");
    });

    // Load saved config if any
    const saved = localStorage.getItem("sb.admin.config");
    if (saved) {
      try {
        const cfg = JSON.parse(saved);
        if (cfg.site_name) formConfig.site_name.value = cfg.site_name;
        if (cfg.max_evidence) formConfig.max_evidence.value = cfg.max_evidence;
        if (cfg.default_visibility) formConfig.default_visibility.value = cfg.default_visibility;
        if (typeof cfg.registration_open === "boolean") formConfig.registration_open.checked = cfg.registration_open;
      } catch {}
    }
  }
});

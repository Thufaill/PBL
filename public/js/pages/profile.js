// profile.js - My Profile page.
// Profil, visibilitas, password, dan foto kini disimpan di PostgreSQL lewat
// public/api/profile.php dan public/api/profile-photo.php. Kunci localStorage
// "sb.profile" dan "sb.portfolioVisibility" sudah tidak dipakai lagi, jadi
// identitas default palsu ("Jhon Doe") hilang dan perubahan berlaku di semua
// browser untuk akun yang sama.

(async () => {
  const { el } = UI;
  const $ = (id) => document.getElementById(id);

  const [careers] = await Promise.all([API.getCareers(), API.ensureSession()]);
  let profile = await API.getProfile();

  const EMPTY = "Belum diisi";
  // Foto served dari satu URL tetap; angka ini ditambah setelah unggah agar
  // browser tidak menampilkan salinan lama dari cache.
  let photoStamp = Date.now();

  function photoSrc() {
    if (!profile.photo) return null;
    return profile.photo + (profile.photo.includes("?") ? "&" : "?") + "v=" + photoStamp;
  }

  // Mockup memakai ikon user di lingkaran avatar; foto menggantikannya.
  const USER_ICON = `<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>`;

  function renderAvatar() {
    const box = $("profile-avatar");
    UI.clear(box);
    const src = photoSrc();
    if (src) {
      box.append(el("img", { src, alt: profile.name || "Foto profil" }));
    } else {
      box.innerHTML = USER_ICON;
    }
  }

  function render() {
    renderAvatar();
    const name = profile.name || "Tanpa nama";
    $("profile-display-name").textContent = name;
    $("detail-fullname").textContent = name;
    $("detail-email").textContent = profile.email || EMPTY;
    $("detail-institution").textContent = profile.institution || EMPTY;
    $("detail-program").textContent = profile.program || EMPTY;
    $("detail-semester").textContent = profile.semester || EMPTY;
    $("detail-location").textContent = profile.location || EMPTY;
    $("detail-member-since").textContent = profile.member_since ? UI.formatDate(profile.member_since) : EMPTY;
    $("detail-visibility").textContent = profile.visibility || "Private";

    // Ringkasan portofolio: angka dari database, bukan dari mockup.
    const mine = API.getMySkills();
    const evidence = API.getEvidence();
    const targetCareer = careers.find((c) => c.id === API.getTargetId()) || null;

    $("summary-skills").textContent = String(mine.length);
    $("summary-evidence").textContent = `${evidence.length} ${evidence.length === 1 ? "item" : "items"}`;
    $("summary-career").textContent = targetCareer ? targetCareer.name : "Not set";
  }

  render();

  // ---- Tombol ----
  $("btn-edit-profile").addEventListener("click", openEditPersonal);
  $("btn-edit-personal").addEventListener("click", openEditPersonal);
  $("btn-change-password").addEventListener("click", openChangePassword);
  $("btn-visibility").addEventListener("click", openVisibility);
  $("btn-change-photo").addEventListener("click", openChangePhoto);

  // ---- Modal: Edit Personal Information ----
  function openEditPersonal() {
    const fields = [
      ["Full name", "edit-name", "text", profile.name || ""],
      ["Email address", "edit-email", "email", profile.email || ""],
      ["Institution", "edit-institution", "text", profile.institution || ""],
      ["Study program", "edit-program", "text", profile.program || ""],
      ["Semester", "edit-semester", "text", profile.semester || ""],
      ["Location", "edit-location", "text", profile.location || ""],
    ];

    const inputs = {};
    const body = fields.map(([label, id, type, value]) => {
      const input = el("input", { type, id, value, required: "required" });
      inputs[id] = input;
      return el("div", { class: "field" }, [el("label", { for: id, text: label }), input]);
    });

    const errorEl = el("p", { class: "field-error", style: "display: none" });
    body.push(errorEl);

    UI.modal({
      title: "Edit Personal Information",
      body,
      submitLabel: "Save changes",
      onSubmit: async () => {
        errorEl.style.display = "none";
        const name = inputs["edit-name"].value.trim();
        const email = inputs["edit-email"].value.trim();
        if (!name || !email) {
          errorEl.textContent = "Name and email are required.";
          errorEl.style.display = "block";
          return false;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
          errorEl.textContent = "Enter a valid email address.";
          errorEl.style.display = "block";
          return false;
        }

        try {
          profile = await API.saveProfile({
            name,
            email,
            institution: inputs["edit-institution"].value.trim(),
            program: inputs["edit-program"].value.trim(),
            semester: inputs["edit-semester"].value.trim(),
            location: inputs["edit-location"].value.trim(),
          });
        } catch (err) {
          errorEl.textContent = err.message;
          errorEl.style.display = "block";
          return false;
        }

        render();
        UI.toast("Profil tersimpan di server.");
        return true;
      },
    });
  }

  // ---- Modal: Change Password ----
  function openChangePassword() {
    const cur = el("input", { type: "password", id: "pw-current", required: "required" });
    const next = el("input", { type: "password", id: "pw-new", required: "required" });
    const confirm = el("input", { type: "password", id: "pw-confirm", required: "required" });
    const errorEl = el("p", { class: "field-error", style: "display: none" });

    UI.modal({
      title: "Change Password",
      body: [
        el("div", { class: "field" }, [el("label", { for: "pw-current", text: "Current password" }), cur]),
        el("div", { class: "field" }, [el("label", { for: "pw-new", text: "New password (min. 8 characters)" }), next]),
        el("div", { class: "field" }, [el("label", { for: "pw-confirm", text: "Confirm new password" }), confirm]),
        errorEl,
      ],
      submitLabel: "Update password",
      onSubmit: async () => {
        errorEl.style.display = "none";
        if (!cur.value || !next.value) {
          errorEl.textContent = "Fill in all password fields.";
          errorEl.style.display = "block";
          return false;
        }
        if (next.value.length < 8) {
          errorEl.textContent = "New password must be at least 8 characters.";
          errorEl.style.display = "block";
          return false;
        }
        if (next.value !== confirm.value) {
          errorEl.textContent = "Passwords do not match.";
          errorEl.style.display = "block";
          return false;
        }
        try {
          await API.changePassword(cur.value, next.value);
        } catch (err) {
          errorEl.textContent = err.message;
          errorEl.style.display = "block";
          return false;
        }
        UI.toast("Password diganti.");
        return true;
      },
    });
  }

  // ---- Modal: Portfolio Visibility ----
  function openVisibility() {
    const current = profile.visibility || "Private";
    const options = [
      ["Public", "HR can find you in the candidate list and view your portfolio."],
      ["Private", "Only you can view your portfolio."],
    ];
    const radios = {};
    const body = options.map(([value, desc]) => {
      const radio = el("input", {
        type: "radio",
        name: "visibility",
        value,
        id: `vis-${value.toLowerCase()}`,
        ...(value === current ? { checked: true } : {}),
      });
      radios[value] = radio;
      return el("label", {
        for: `vis-${value.toLowerCase()}`,
        style: "display: flex; gap: 12px; align-items: flex-start; padding: 12px; border: 1px solid var(--border); border-radius: var(--radius-sm); cursor: pointer",
      }, [
        radio,
        el("div", {}, [
          el("p", { style: "font-size: 14px; font-weight: 700; color: var(--text-primary)", text: value }),
          el("p", { style: "font-size: 12px; color: var(--text-muted); margin-top: 2px", text: desc }),
        ]),
      ]);
    });
    const errorEl = el("p", { class: "field-error", style: "display: none" });
    body.push(errorEl);

    UI.modal({
      title: "Portfolio Visibility",
      body,
      submitLabel: "Save",
      onSubmit: async () => {
        errorEl.style.display = "none";
        const chosen = Object.entries(radios).find(([, r]) => r.checked);
        if (!chosen) return false;
        try {
          profile = await API.saveProfile({ visibility: chosen[0] });
        } catch (err) {
          errorEl.textContent = err.message;
          errorEl.style.display = "block";
          return false;
        }
        render();
        UI.toast(`Visibilitas: ${chosen[0]}`);
        return true;
      },
    });
  }

  // ---- Modal: Change Photo (unggah sungguhan ke storage/ di server) ----
  function openChangePhoto() {
    const input = el("input", { type: "file", id: "photo-file", accept: "image/png,image/jpeg,image/webp" });
    const preview = el("div", {
      style: "width: 64px; height: 64px; border-radius: 50%; background: var(--tint-gray); display: grid; place-items: center; overflow: hidden; color: var(--text-muted); font-size: 12px",
      text: "Preview",
    });
    const errorEl = el("p", { class: "field-error", style: "display: none" });

    input.addEventListener("change", () => {
      const file = input.files && input.files[0];
      errorEl.style.display = "none";
      if (!file) return;
      if (file.size > 2 * 1024 * 1024) {
        errorEl.textContent = "Image must be under 2 MB.";
        errorEl.style.display = "block";
        input.value = "";
        return;
      }
      const reader = new FileReader();
      reader.onload = () => {
        UI.clear(preview);
        preview.append(el("img", { src: reader.result, alt: "Preview", style: "width: 100%; height: 100%; object-fit: cover" }));
      };
      reader.readAsDataURL(file);
    });

    UI.modal({
      title: "Change Photo",
      body: [
        el("div", { style: "display: flex; gap: 16px; align-items: center" }, [preview, el("div", {}, [el("p", { style: "font-size: 13px; color: var(--text-muted)", text: "PNG, JPG or WEBP, max 2 MB. Saved on the server, not in this browser." })])]),
        el("div", { class: "field" }, [el("label", { for: "photo-file", text: "Choose image" }), input]),
        errorEl,
      ],
      submitLabel: "Save photo",
      onSubmit: async () => {
        errorEl.style.display = "none";
        const file = input.files && input.files[0];
        try {
          if (file) {
            const res = await API.uploadPhoto(file);
            profile = { ...profile, photo: res.photo };
            photoStamp = Date.now();
            UI.toast("Foto tersimpan di server.");
          } else if (profile.photo) {
            await API.removePhoto();
            profile = { ...profile, photo: null };
            UI.toast("Foto dihapus.");
          } else {
            errorEl.textContent = "Pilih gambar dulu.";
            errorEl.style.display = "block";
            return false;
          }
        } catch (err) {
          errorEl.textContent = err.message;
          errorEl.style.display = "block";
          return false;
        }
        render();
        return true;
      },
    });
  }
})();

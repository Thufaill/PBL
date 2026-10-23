// profile.js - My Profile page

(async () => {
  const { el } = UI;

  // ---- Profile data (localStorage dummy; backend replaces via api.js) ----
  const LS_PROFILE = "sb.profile";
  const LS_VISIBILITY = "sb.portfolioVisibility";

  function readProfile() {
    const raw = localStorage.getItem(LS_PROFILE);
    if (raw) return JSON.parse(raw);
    // Backend: defaults mirror mockup; upgrade path = GET api/profile.php
    return {
      name: "Jhon Doe",
      email: "jhondoe@gmail.com",
      institution: "Politeknik Negeri Malang",
      program: "Information Technology",
      semester: "3",
      location: "Malang, Indonesia",
      memberSince: "January 2026",
      photo: null,
    };
  }

  function saveProfile(p) {
    localStorage.setItem(LS_PROFILE, JSON.stringify(p));
  }

  function readVisibility() {
    return localStorage.getItem(LS_VISIBILITY) || "Public";
  }

  function saveVisibility(v) {
    localStorage.setItem(LS_VISIBILITY, v);
  }

  let profile = readProfile();

  // ---- Load dynamic counts ----
  const [careers] = await Promise.all([API.getCareers()]);
  const mine = API.getMySkills();
  const evidence = API.getEvidence();
  const targetId = API.getTargetId();
  const target = careers.find((c) => c.id === targetId) || null;

  // ---- Render static fields ----
  const $ = (id) => document.getElementById(id);

  // Mockup shows a user-round icon in the blue avatar circle; a chosen photo replaces it.
  const USER_ICON = `<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>`;

  function renderAvatar() {
    const box = $("profile-avatar");
    UI.clear(box);
    if (profile.photo) {
      box.append(el("img", { src: profile.photo, alt: profile.name }));
    } else {
      box.innerHTML = USER_ICON;
    }
  }

  function render() {
    renderAvatar();
    $("profile-display-name").textContent = profile.name;
    $("detail-fullname").textContent = profile.name;
    $("detail-email").textContent = profile.email;
    $("detail-institution").textContent = profile.institution;
    $("detail-program").textContent = profile.program;
    $("detail-semester").textContent = profile.semester;
    $("detail-location").textContent = profile.location;
    $("detail-member-since").textContent = profile.memberSince;
    $("detail-visibility").textContent = readVisibility();

    // Portfolio overview counts (real data, not mockup numbers)
    $("summary-skills").textContent = String(mine.length);
    $("summary-evidence").textContent = `${evidence.length} ${evidence.length === 1 ? "item" : "items"}`;
    $("summary-career").textContent = target ? target.name : "Not set";
  }

  render();

  // ---- Edit Profile (top button) -> opens personal info modal ----
  $("btn-edit-profile").addEventListener("click", openEditPersonal);
  $("btn-edit-personal").addEventListener("click", openEditPersonal);
  $("btn-change-password").addEventListener("click", openChangePassword);
  $("btn-visibility").addEventListener("click", openVisibility);
  $("btn-change-photo").addEventListener("click", openChangePhoto);

  // ---- Modal: Edit Personal Information ----
  function openEditPersonal() {
    const fields = [
      ["Full name", "edit-name", "text", profile.name],
      ["Email address", "edit-email", "email", profile.email],
      ["Institution", "edit-institution", "text", profile.institution],
      ["Study program", "edit-program", "text", profile.program],
      ["Semester", "edit-semester", "text", profile.semester],
      ["Location", "edit-location", "text", profile.location],
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
      onSubmit: (close) => {
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
        profile = {
          ...profile,
          name,
          email,
          institution: inputs["edit-institution"].value.trim(),
          program: inputs["edit-program"].value.trim(),
          semester: inputs["edit-semester"].value.trim(),
          location: inputs["edit-location"].value.trim(),
        };
        saveProfile(profile);
        // Backend: POST api/profile.php when backend ready
        render();
        UI.toast("Profile updated");
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
        el("div", { class: "field" }, [el("label", { for: "pw-new", text: "New password" }), next]),
        el("div", { class: "field" }, [el("label", { for: "pw-confirm", text: "Confirm new password" }), confirm]),
        errorEl,
      ],
      submitLabel: "Update password",
      onSubmit: (close) => {
        errorEl.style.display = "none";
        if (!cur.value || !next.value) {
          errorEl.textContent = "Fill in all password fields.";
          errorEl.style.display = "block";
          return false;
        }
        if (next.value.length < 6) {
          errorEl.textContent = "New password must be at least 6 characters.";
          errorEl.style.display = "block";
          return false;
        }
        if (next.value !== confirm.value) {
          errorEl.textContent = "Passwords do not match.";
          errorEl.style.display = "block";
          return false;
        }
        // Backend: POST api/auth/password.php with CSRF; dummy mode just acknowledges
        UI.toast("Password updated");
        return true;
      },
    });
  }

  // ---- Modal: Portfolio Visibility ----
  function openVisibility() {
    const current = readVisibility();
    const options = [
      ["Public", "Anyone can view your student portfolio."],
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

    UI.modal({
      title: "Portfolio Visibility",
      body,
      submitLabel: "Save",
      onSubmit: () => {
        const chosen = Object.entries(radios).find(([, r]) => r.checked);
        if (chosen) {
          saveVisibility(chosen[0]);
          // Backend: PATCH api/profile.php { visibility }
          render();
          UI.toast(`Visibility set to ${chosen[0]}`);
        }
        return true;
      },
    });
  }

  // ---- Change Photo (file input -> data URL preview, local only) ----
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
        preview.dataset.dataurl = reader.result;
      };
      reader.readAsDataURL(file);
    });

    UI.modal({
      title: "Change Photo",
      body: [
        el("div", { style: "display: flex; gap: 16px; align-items: center" }, [preview, el("div", {}, [el("p", { style: "font-size: 13px; color: var(--text-muted)", text: "PNG, JPG or WEBP, max 2 MB." })])]),
        el("div", { class: "field" }, [el("label", { for: "photo-file", text: "Choose image" }), input]),
        errorEl,
      ],
      submitLabel: "Save photo",
      onSubmit: () => {
        const dataurl = preview.dataset.dataurl || null;
        profile = { ...profile, photo: dataurl };
        saveProfile(profile);
        // Backend: POST multipart api/profile/photo.php; data URL is local-only placeholder
        render();
        UI.toast(dataurl ? "Photo updated" : "No photo selected");
        return true;
      },
    });
  }
})();

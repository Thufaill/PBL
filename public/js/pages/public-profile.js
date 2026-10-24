// public-profile.js - render halaman profil publik berbasis token.

(async () => {
  const { el, clear, safeUrl, formatDate } = UI;
  const root = document.getElementById("public-profile-root");
  if (!root) return;

  const token = new URLSearchParams(window.location.search).get("token") || "";

  function showError(message) {
    clear(root);
    root.classList.remove("sb-is-loading");
    root.removeAttribute("aria-busy");
    root.append(
      el("div", { class: "public-profile-error", role: "status" }, [
        el("h1", { text: "Profil tidak tersedia" }),
        el("p", { text: message }),
      ])
    );
  }

  function detail(label, value) {
    if (!value) return null;
    return el("div", { class: "public-profile-detail" }, [
      el("dt", { text: label }),
      el("dd", { text: value }),
    ]);
  }

  function render(profile) {
    clear(root);
    root.classList.remove("sb-is-loading");
    root.removeAttribute("aria-busy");

    const photo = profile.photo_url ? safeUrl(profile.photo_url) : null;
    const avatar = photo
      ? el("img", { class: "public-profile-avatar", src: photo, alt: "", loading: "lazy" })
      : el("div", { class: "public-profile-avatar public-profile-avatar--initials", text: profile.initials || "SB", "aria-hidden": "true" });

    const groups = new Map();
    for (const skill of profile.skills || []) {
      const key = skill.category || "Lainnya";
      if (!groups.has(key)) groups.set(key, []);
      groups.get(key).push(skill);
    }

    const details = el("dl", { class: "public-profile-details" }, [
      detail("Institusi", profile.institution),
      detail("Program studi", profile.program),
      detail("Semester", profile.semester),
      detail("Lokasi", profile.location),
      detail("Bergabung", formatDate(profile.member_since)),
      detail("Total evidence", String(profile.evidence_count || 0)),
    ]);

    const skillSections = [...groups.entries()].map(([category, skills]) =>
      el("div", { class: "public-profile-skill-group" }, [
        el("h3", { text: category }),
        el("div", { class: "public-profile-skill-list" }, skills.map((skill) =>
          el("span", { class: "public-profile-skill" }, [
            el("span", { text: skill.name }),
            el("span", { class: "public-profile-skill-count", text: `${skill.evidence_count} evidence` }),
          ])
        )),
      ])
    );

    root.append(
      el("section", { class: "public-profile-hero" }, [
        avatar,
        el("div", {}, [
          el("h1", { class: "public-profile-name", text: profile.name }),
          profile.target_role ? el("p", { class: "public-profile-role", text: profile.target_role }) : null,
          el("span", { class: "public-profile-badge", text: "Profil Publik" }),
        ]),
      ]),
      profile.bio ? el("section", { class: "public-profile-card" }, [
        el("h2", { text: "Tentang" }),
        el("p", { class: "public-profile-bio", text: profile.bio }),
      ]) : null,
      el("section", { class: "public-profile-card" }, [
        el("h2", { text: "Detail" }),
        details,
      ]),
      el("section", { class: "public-profile-card" }, [
        el("h2", { text: "Keterampilan" }),
        skillSections.length
          ? el("div", {}, skillSections)
          : el("p", { class: "public-profile-empty", text: "Belum ada keterampilan yang dikonfirmasi." }),
      ])
    );
  }

  if (!token) {
    showError("Tautan profil tidak lengkap.");
    return;
  }

  try {
    const data = await API.getPublicProfile(token);
    render(data.profile);
  } catch (err) {
    const notFound = err instanceof API.ApiError && err.status === 404;
    showError(notFound
      ? "Profil tidak ditemukan atau tidak lagi publik."
      : "Profil gagal dimuat. Coba lagi beberapa saat lagi.");
  }
})();

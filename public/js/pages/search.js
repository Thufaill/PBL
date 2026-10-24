// search.js - direktori profil publik untuk mahasiswa dan admin.
(function () {
  const page = document.body.dataset.searchPage || "mahasiswa";
  const isAdmin = page === "admin";
  const copy = isAdmin
    ? {
        hint: "Ketik minimal 2 karakter untuk mencari profil publik.",
        loading: "Mencari profil...",
        empty: (q) => `Tidak ada profil publik yang cocok dengan “${q}”.`,
        results: (n) => `${n} profil publik ditemukan.`,
        truncated: "Menampilkan 30 hasil pertama. Persempit kata kunci untuk hasil yang lebih spesifik.",
        error: "Pencarian gagal. Coba lagi.",
        target: "Target karier",
        evidence: (n) => `${n} evidence`,
      }
    : {
        hint: "Type at least 2 characters to search public profiles.",
        loading: "Searching profiles...",
        empty: (q) => `No public profiles match “${q}”.`,
        results: (n) => `${n} public profile${n === 1 ? "" : "s"} found.`,
        truncated: "Showing the first 30 results. Narrow your search for more specific matches.",
        error: "Search failed. Please try again.",
        target: "Career target",
        evidence: (n) => `${n} evidence item${n === 1 ? "" : "s"}`,
      };

  const form = document.getElementById("people-search-form");
  const input = document.getElementById("people-search-input");
  const results = document.getElementById("people-search-results");
  const status = document.getElementById("people-search-status");
  if (!form || !input || !results || !status) return;

  const { el, clear, safeUrl } = UI;
  let debounceTimer = null;
  let requestId = 0;

  function showState(message, kind) {
    clear(results);
    results.append(el("div", { class: `people-state people-state--${kind || "hint"}`, text: message }));
  }

  function renderCard(profile) {
    const link = safeUrl(profile.profile_url);
    if (!link) return null;

    const photo = profile.photo_url ? safeUrl(profile.photo_url) : null;
    const avatar = photo
      ? el("img", { class: "person-card-avatar", src: photo, alt: "", loading: "lazy" })
      : el("span", { class: "person-card-avatar person-card-avatar--initials", text: profile.initials || "SB", "aria-hidden": "true" });

    const meta = [profile.institution, profile.program, profile.semester ? `Semester ${profile.semester}` : null]
      .filter(Boolean)
      .join(" · ");

    const skillNames = profile.skills || [];
    const skillNodes = skillNames.slice(0, 4).map((name) => el("span", { class: "person-skill", text: name }));
    if (skillNames.length > 4) {
      skillNodes.push(el("span", { class: "person-skill person-skill--more", text: `+${skillNames.length - 4}` }));
    }

    return el("a", { class: "person-card", href: link }, [
      el("div", { class: "person-card-head" }, [
        avatar,
        el("div", { class: "person-card-identity" }, [
          el("h3", { class: "person-card-name", text: profile.name }),
          el("p", { class: "person-card-meta", text: meta || "—" }),
        ]),
      ]),
      profile.location ? el("p", { class: "person-card-location", text: profile.location }) : null,
      profile.target_role ? el("p", { class: "person-card-target", text: `${copy.target}: ${profile.target_role}` }) : null,
      skillNodes.length ? el("div", { class: "person-card-skills" }, skillNodes) : null,
      el("p", { class: "person-card-evidence", text: copy.evidence(profile.evidence_count || 0) }),
    ]);
  }

  async function runSearch(value, options = {}) {
    const q = value.trim();

    if (options.updateUrl !== false) {
      const url = new URL(window.location.href);
      if (q) url.searchParams.set("q", q);
      else url.searchParams.delete("q");
      window.history.replaceState({}, "", url);
    }

    if (q.length < 2) {
      status.textContent = "";
      showState(copy.hint);
      return;
    }

    const id = ++requestId;
    status.textContent = copy.loading;
    showState(copy.loading, "loading");

    try {
      const data = await API.searchProfiles(q);
      if (id !== requestId) return;

      clear(results);
      if (!data.profiles.length) {
        status.textContent = "";
        showState(copy.empty(q));
        return;
      }

      status.textContent = copy.results(data.total);
      for (const profile of data.profiles) {
        const card = renderCard(profile);
        if (card) results.append(card);
      }
      if (data.truncated) {
        results.append(el("p", { class: "people-search-note", text: copy.truncated }));
      }
    } catch (err) {
      if (id !== requestId) return;
      status.textContent = "";
      showState(err instanceof API.ApiError ? err.message : copy.error, "error");
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    const initial = new URLSearchParams(window.location.search).get("q") || "";
    input.value = initial;
    runSearch(initial, { updateUrl: false });

    form.addEventListener("submit", (event) => {
      event.preventDefault();
      clearTimeout(debounceTimer);
      runSearch(input.value);
    });

    input.addEventListener("input", () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => runSearch(input.value), 250);
    });
  });
})();

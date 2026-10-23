// gap.js - Skill Gap page

(async () => {
  const { el } = UI;

  // Ikon per kategori (sama seperti skills.js)
  const CATEGORY_ICONS = {
    Programming: `<path d="M8 6 3 12l5 6M16 6l5 6-5 6"/>`,
    Database: `<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v12c0 1.66 3.58 3 8 3s8-1.34 8-3V6M4 12c0 1.66 3.58 3 8 3s8-1.34 8-3"/>`,
    Web: `<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M9 9v11"/>`,
    Tools: `<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17v3h3l5.3-5.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.1-2.1 2.7-2.5z"/>`,
    Data: `<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>`,
    Design: `<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>`,
  };
  const CATEGORY_ICON_FALLBACK = `<path d="M4 6h16M4 12h16M4 18h10"/>`;

  function categoryIconSvg(category) {
    const path = CATEGORY_ICONS[category] || CATEGORY_ICON_FALLBACK;
    return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${path}</svg>`;
  }

  const gap = await API.getSkillGap();

  if (!gap.career) {
    document.body.innerHTML = `<main class="container"><p style="text-align: center; margin-top: 100px; color: var(--text-muted)">No target career set. <a href="career.php" style="color: var(--primary); font-weight: 600">Choose one</a>.</p></main>`;
    return;
  }

  // Target info
  document.getElementById("target-info").append(
    el("h2", { style: "font-size: 30px; font-weight: 700; line-height: 1.1; color: var(--text-primary)", text: gap.career.name }),
    el("p", { style: "font-size: 16px; line-height: 1.5; color: var(--text-muted); margin-top: 8px; opacity: 0.85", text: gap.career.description })
  );

  // Stat tiles
  const evidence = await API.getEvidence();
  const tilesEl = document.getElementById("stat-tiles");
  tilesEl.append(
    el("div", { class: "stat-tile" }, [
      el("p", { class: "stat-label", text: "Skills you already have" }),
      el("p", { class: "stat-value", text: String(gap.have.length) }),
    ]),
    el("div", { class: "stat-tile" }, [
      el("p", { class: "stat-label", text: "What to explore next" }),
      el("p", { class: "stat-value", text: String(gap.explore.length) }),
    ]),
    el("div", { class: "stat-tile" }, [
      el("p", { class: "stat-label", text: "Evidence" }),
      el("p", { class: "stat-value", text: String(evidence.length) }),
    ])
  );

  // Skills you have
  const haveEl = document.getElementById("have-list");
  if (gap.have.length === 0) {
    haveEl.append(el("p", { style: "color: var(--text-muted); font-size: 15px", text: "No matching skills added yet." }));
  } else {
    haveEl.append(el("div", { style: "display: flex; flex-direction: column" }, gap.have.map((s) => renderSkillRow(s, s.evidence_count, "have"))));
  }

  // Explore next
  const exploreEl = document.getElementById("explore-list");
  if (gap.explore.length === 0) {
    exploreEl.append(el("p", { style: "color: var(--text-muted); font-size: 15px", text: "All required skills covered." }));
  } else {
    exploreEl.append(el("div", { style: "display: flex; flex-direction: column" }, gap.explore.slice(0, 1).map((s) => renderSkillRow(s, 0, "explore"))));
  }

  // Next step box
  const nextStepEl = document.getElementById("next-step-box");
  if (gap.explore.length > 0) {
    const first = gap.explore[0];
    nextStepEl.append(
      el("h3", { style: "font-size: 18px; font-weight: 700; line-height: 1.1; color: var(--text-primary)", text: "Simple next step" }),
      el("p", { style: "font-size: 13px; line-height: 1.45; color: var(--text-primary); opacity: 0.85", text: `Add ${first.name} to your next coursework project, then attach the finished project as evidence.` }),
      el("button", {
        class: "btn btn-accent",
        style: "width: fit-content; margin-top: 8px; gap: 7px",
        onClick: () => UI.toast("Feature coming soon"),
      }, [
        el("svg", { width: "16", height: "16", viewBox: "0 0 16 16", fill: "none", stroke: "currentColor", "stroke-width": "2", innerHTML: `<path d="M3.333 8h9.334M8 3.333v9.334" stroke-linecap="round"/>` }),
        document.createTextNode(`Add ${first.name}`),
      ])
    );
  } else {
    nextStepEl.append(
      el("h3", { style: "font-size: 18px; font-weight: 700; line-height: 1.1; color: var(--text-primary)", text: "All covered" }),
      el("p", { style: "font-size: 13px; line-height: 1.45; color: var(--text-primary); opacity: 0.85", text: "You have all required skills for this target. Focus on building more evidence." })
    );
  }

  function renderSkillRow(skill, evidenceCount, type) {
    const avatarContent = el("div", {
      style: "width: 38px; height: 38px; border-radius: 10px; background: var(--primary-dark); color: #fff; display: grid; place-items: center; flex-shrink: 0",
      innerHTML: categoryIconSvg(skill.category),
    });

    const badge = type === "have"
      ? el("span", { class: "badge-info", text: `${evidenceCount} evidence` })
      : el("span", { class: "badge-not-added", text: "Not added" });

    return el("div", { class: "skill-row" }, [
      avatarContent,
      el("div", { style: "flex: 1; min-width: 0" }, [
        el("p", { style: "font-size: 15px; font-weight: 700; line-height: 1.2; color: var(--text-primary)", text: skill.name }),
        el("p", { style: "font-size: 12px; color: var(--text-muted); margin-top: 4px; opacity: 0.85", text: skill.category || (type === "have" ? "Programming language" : "Backend web framework") }),
      ]),
      badge,
    ]);
  }
})();

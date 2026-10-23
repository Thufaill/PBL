// career.js - Career Target page

(async () => {
  const { el, clear } = UI;
  const [careers, catalog] = await Promise.all([API.getCareers(), API.getSkillCatalog()]);

  const mine = new Set(API.getMySkills().map((s) => s.skill_id));

  const gridEl = document.getElementById("career-grid");
  const bannerText = document.getElementById("banner-text");

  render();

  function getTarget() {
    const targetId = API.getTargetId();
    return careers.find((c) => c.id === targetId) || careers[0];
  }

  // Target selalu di urutan teratas, career lain mengikuti urutan asli.
  function sortedCareers() {
    const target = getTarget();
    const rest = careers.filter((c) => c.id !== target.id);
    return [target, ...rest];
  }

  function render() {
    const target = getTarget();
    const list = sortedCareers();

    bannerText.textContent = `${target.name} is your current target. Required skills below are a practical starter list for this project.`;

    gridEl.classList.remove("career-grid--animated");
    clear(gridEl);
    for (const career of list) {
      gridEl.append(renderCareerCard(career, career.id === target.id, mine, catalog));
    }
    // Re-trigger animasi fade-in pada setiap render ulang.
    void gridEl.offsetWidth;
    requestAnimationFrame(() => gridEl.classList.add("career-grid--animated"));
  }

  function selectTarget(career) {
    API.setTarget(career.id);
    render();
  }

  function renderCareerCard(career, isSelected, mine, catalog) {
    const iconMap = {
      "Backend Developer": `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="2" y="3" width="16" height="14" rx="2"/><path d="M6 8l2 2-2 2M10 12h4"/></svg>`,
      "Frontend Developer": `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.2"><rect x="2" y="3" width="16" height="14" rx="2"/><path d="M2 7h16"/></svg>`,
      "Data Analyst": `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M3 16v-6M10 16V8M17 16v-3"/><circle cx="3" cy="3" r="1.5"/><circle cx="10" cy="3" r="1.5"/><circle cx="17" cy="3" r="1.5"/></svg>`,
      "UI/UX Designer": `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.2"><circle cx="10" cy="10" r="3"/><path d="M10 2v2M10 16v2M18 10h-2M4 10H2M15.5 4.5l-1.4 1.4M5.9 14.1l-1.4 1.4M15.5 15.5l-1.4-1.4M5.9 5.9L4.5 4.5"/></svg>`,
    };
    const icon = iconMap[career.name] || iconMap["Backend Developer"];

    const card = el("article", {
      class: isSelected ? "career-card is-selected" : "career-card",
      "data-id": career.id,
    });

    // Head: badge target (jika dipilih) + icon + title
    const head = el("div", { class: "career-card-head" }, [
      el("div", { class: "career-card-icon", innerHTML: icon }),
      el("div", { style: "flex: 1; min-width: 0" }, [
        el("h3", { class: "career-card-title", text: career.name }),
      ]),
    ]);
    card.append(head);

    if (isSelected) {
      card.append(
        el("span", { class: "target-badge" }, [
          el("svg", { width: "12", height: "12", viewBox: "0 0 12 12", fill: "none", stroke: "currentColor", "stroke-width": "1.6", innerHTML: `<circle cx="6" cy="6" r="4.5"/><circle cx="6" cy="6" r="1.5"/>` }),
          document.createTextNode("Your Target"),
        ])
      );
    }

    // Description
    card.append(el("p", { class: "career-card-desc", text: career.description }));

    // Required skills
    const skillNames = career.skills.map((name) => catalog.find((s) => s.name === name)).filter(Boolean);
    const matched = skillNames.filter((s) => mine.has(s.id)).length;

    card.append(
      el("div", { class: "career-card-skills" }, [
        el("p", { class: "career-card-skills-label", text: "Required skills" }),
        el("div", { class: "career-card-chips" }, skillNames.map((s) => renderSkillChip(s, mine.has(s.id)))),
      ])
    );

    if (isSelected) {
      card.append(el("p", { class: "career-card-count", text: `${matched} of ${skillNames.length} skills covered` }));
      card.append(
        el("button", {
          class: "btn btn-accent",
          style: "width: fit-content; gap: 7px",
          disabled: true,
        }, [
          el("svg", { width: "16", height: "16", viewBox: "0 0 16 16", fill: "none", stroke: "currentColor", "stroke-width": "1.5", innerHTML: `<circle cx="8" cy="8" r="6"/><circle cx="8" cy="8" r="2"/>` }),
          document.createTextNode("Current Target"),
        ])
      );
    } else {
      card.append(
        el("div", { class: "career-card-foot" }, [
          el("p", { class: "career-card-foot-text", text: "Review this path" }),
          el("button", {
            class: "btn btn-primary btn-sm",
            onClick: () => selectTarget(career),
          }, [
            el("svg", { width: "14", height: "14", viewBox: "0 0 14 14", fill: "none", stroke: "currentColor", "stroke-width": "1.5", innerHTML: `<circle cx="7" cy="7" r="5.5"/><circle cx="7" cy="7" r="1.8"/>` }),
            document.createTextNode("Set as Target"),
          ]),
        ])
      );
    }

    return card;
  }

  function renderSkillChip(skill, owned) {
    const color = owned ? "var(--accent)" : "var(--primary)";
    const bg = owned ? "var(--tint-pink)" : "var(--tint-indigo)";
    return el("span", {
      class: "skill-chip",
      style: `--chip-color: ${color}; --chip-bg: ${bg}`,
    }, [
      el("span", { style: `display: inline-block; width: 5px; height: 5px; border-radius: 50%; background: ${color}` }),
      document.createTextNode(skill.name),
    ]);
  }
})();

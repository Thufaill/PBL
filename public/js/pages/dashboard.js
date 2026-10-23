// dashboard.js - render dashboard sesuai Figma spec

(async () => {
  const { el } = UI;
  const [careers, catalog, gap, evidence] = await Promise.all([
    API.getCareers(),
    API.getSkillCatalog(),
    API.getSkillGap(),
    API.getEvidence(),
  ]);

  const mine = API.getMySkills();
  const targetId = API.getTargetId();
  const target = careers.find((c) => c.id === targetId) || careers[0];

  // Card: Target Career
  document.getElementById("card-target").append(
    el("p", { style: "font-size: 36px; font-weight: 700; line-height: 1.1; color: var(--text-primary)", text: target.name }),
    el("p", { style: "font-size: 15px; line-height: 1.45; color: var(--text-muted); margin-top: 12px", text: target.description })
  );

  // Card: Skills Summary
  const strongCount = mine.filter((s) => s.label === "Strong").length;
  const mySkillNames = mine
    .map((s) => catalog.find((c) => c.id === s.skill_id)?.name)
    .filter(Boolean)
    .slice(0, 5);

  document.getElementById("card-skills").append(
    el("p", { style: "font-size: 36px; font-weight: 700; line-height: 1.1; color: var(--text-primary)", text: `${mine.length} skills` }),
    el("p", { style: "font-size: 15px; line-height: 1.45; color: var(--text-muted); margin-top: 12px", text: `Five of your skills are already strong, and three are ready for your next focus.` }),
    mySkillNames.length > 0
      ? el("div", { style: "display: flex; flex-wrap: wrap; gap: 8px; margin-top: 18px" },
          mySkillNames.map((name) => {
            const chip = el("div", { class: "tag-chip", style: "display: inline-flex; align-items: center; gap: 7px; background: var(--tint-gray); border: 1px solid var(--border); padding: 8px 12px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 700; color: var(--primary-dark)" });
            // Nama skill bisa berasal dari input pengguna: jangan lewat innerHTML (XSS).
            chip.append(
              el("span", { style: "display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: var(--primary)" }),
              document.createTextNode(name)
            );
            return chip;
          }))
      : null
  );

  // Card: Skill Gap
  const exploreNames = gap.explore.map((s) => s.name).slice(0, 3).join(", ");
  document.getElementById("card-explore").append(
    el("p", { style: "font-size: 36px; font-weight: 700; line-height: 1.1; color: var(--text-primary)", text: `${gap.explore.length} to develop` }),
    el("p", { style: "font-size: 15px; line-height: 1.45; color: var(--text-muted); margin-top: 12px", text: `Focus on ${exploreNames || "your next goals"} this semester. You already have ${strongCount} strong skills in place.` })
  );

  // Card: Evidence Summary
  const recent = evidence.slice(-3).reverse();
  const evCardEl = document.getElementById("card-evidence");
  evCardEl.append(
    el("p", { style: "font-size: 36px; font-weight: 700; line-height: 1.1; color: var(--text-primary)", text: `${evidence.length} items` }),
    el("p", { style: "font-size: 15px; line-height: 1.45; color: var(--text-muted); margin-top: 12px", text: "Projects, certificates, and links are all in one place so you can review them quickly." })
  );

  if (recent.length > 0) {
    const listEl = el("div", { style: "display: grid; gap: 12px; margin-top: 18px" });
    for (const ev of recent) {
      listEl.append(renderEvidenceItem(ev));
    }
    evCardEl.append(listEl);
  }

  function renderEvidenceItem(ev) {
    const iconMap = {
      project: `<svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1"><path d="M6.375 2.125h4.25M4.25 14.167h8.5a1.417 1.417 0 0 0 1.417-1.417V4.25a1.417 1.417 0 0 0-1.417-1.417h-8.5A1.417 1.417 0 0 0 2.833 4.25v8.5c0 .782.635 1.417 1.417 1.417z"/></svg>`,
      certificate: `<svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1"><circle cx="8.5" cy="5.667" r="4.25"/><path d="M5.667 12.042v2.833l2.833-1.417 2.833 1.417v-2.833"/></svg>`,
      portfolio: `<svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1"><circle cx="8.5" cy="8.5" r="7.084"/><path d="M1.416 9.917h14.168M8.5 8.5c1.566 0 2.834-3.175 2.834-7.084M8.5 8.5c-1.566 0-2.834-3.175-2.834-7.084"/></svg>`,
      other: `<svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1"><path d="M9.917 1.416H4.25a1.417 1.417 0 0 0-1.417 1.417v11.334c0 .782.635 1.416 1.417 1.416h8.5c.782 0 1.417-.634 1.417-1.416V5.667L9.917 1.416z"/><path d="M9.917 1.416v4.25h4.25M10.625 9.208H6.375M10.625 11.333H6.375M7.792 7.083H6.375"/></svg>`,
    };
    const icon = iconMap[ev.source_type] || iconMap.other;

    return el("div", { style: "display: flex; gap: 13px; padding: 11px 0; border-bottom: 1px solid var(--border); opacity: 0.9; align-items: center" }, [
      el("div", { style: "width: 38px; height: 38px; border-radius: 10px; background: var(--tint-gray); display: grid; place-items: center; flex-shrink: 0", innerHTML: icon }),
      el("div", { style: "flex: 1; min-width: 0" }, [
        el("p", { style: "font-size: 14px; font-weight: 700; color: var(--text-primary)", text: ev.title }),
        el("p", { style: "font-size: 11px; color: var(--text-muted)", text: `${ev.source_type} · ${UI.formatDate(ev.metadata?.date)}` }),
      ]),
      el("span", { style: "background: var(--tint-pink); color: var(--accent); font-size: 11px; font-weight: 700; padding: 5px 10px; border-radius: var(--radius-round); opacity: 0.9", text: "Added" }),
    ]);
  }
})();
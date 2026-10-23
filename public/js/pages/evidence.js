// evidence.js - Evidence page

(async () => {
  const { el } = UI;
  const evidence = await API.getEvidence();

  // Update count
  document.getElementById("count-label").textContent = `${evidence.length} recent items`;

  const gridEl = document.getElementById("evidence-grid");

  if (evidence.length === 0) {
    gridEl.style.gridTemplateColumns = "1fr";
    gridEl.append(
      el("div", { style: "text-align: center; padding: 48px 20px; color: var(--text-muted)" }, [
        el("p", { style: "font-size: 18px; font-weight: 600; margin-bottom: 8px", text: "No evidence yet" }),
        el("p", { style: "font-size: 15px", text: "Use the Add Evidence button to start documenting your work." }),
      ])
    );
  } else {
    const cards = evidence.slice().reverse().map((ev) => renderEvidenceCard(ev));
    gridEl.append(...cards);
  }

  // Modal elements
  const modal = document.getElementById("modal-add-evidence");
  const form = document.getElementById("form-add-evidence");
  const typeInput = document.getElementById("input-type");
  const skillsInput = document.getElementById("input-skills");
  const skillsContainer = document.getElementById("skills-container");

  // Skills list
  const availableSkills = ["JavaScript", "Node.js", "SQL", "REST APIs", "Git", "Express.js", "Testing", "Deployment", "HTML", "CSS", "React", "TypeScript", "Excel", "Python", "Data visualisation", "Statistics", "User research", "Wireframing", "Figma", "Prototyping", "User Experience", "User Interface", "Communication", "AI Design", "Laravel", "PHP", "Docker", "Unit testing", "Data analysis", "UI design"];
  let selectedSkills = [];

  // Render skill chips
  availableSkills.forEach((skill) => {
    const chip = document.createElement("button");
    chip.type = "button";
    chip.textContent = skill;
    chip.style.cssText = "padding: 6px 12px; border: 1px solid #E2E8F0; background: #F1F5F9; color: #475569; border-radius: 16px; font-size: 12px; font-weight: 500; cursor: pointer; transition: all 0.2s";
    chip.dataset.skill = skill;

    chip.onclick = () => {
      if (selectedSkills.includes(skill)) {
        selectedSkills = selectedSkills.filter((s) => s !== skill);
        chip.style.border = "1px solid #E2E8F0";
        chip.style.background = "#F1F5F9";
        chip.style.color = "#475569";
      } else if (selectedSkills.length < 6) {
        selectedSkills.push(skill);
        chip.style.border = "2px solid #6366F1";
        chip.style.background = "#F3F0FF";
        chip.style.color = "#6366F1";
      }
      skillsInput.value = JSON.stringify(selectedSkills);
    };

    skillsContainer.appendChild(chip);
  });

  // Type selection
  document.querySelectorAll(".type-option").forEach((btn) => {
    btn.onclick = () => {
      document.querySelectorAll(".type-option").forEach((b) => {
        b.style.border = "2px solid #E2E8F0";
        b.style.background = "#FFFFFF";
        b.querySelector("span").style.color = "#475569";
        const svg = b.querySelector("svg");
        if (svg) svg.setAttribute("stroke", "#475569");
      });

      btn.style.border = "2px solid #6366F1";
      btn.style.background = "#F3F0FF";
      btn.querySelector("span").style.color = "#6366F1";
      const svg = btn.querySelector("svg");
      if (svg && svg.hasAttribute("stroke")) svg.setAttribute("stroke", "#6366F1");

      typeInput.value = btn.dataset.value;
    };
  });

  // Demo banner auto-fill
  document.getElementById("demo-banner").onclick = () => {
    document.getElementById("input-title").value = "CV Full Stack Developer - 2024";
    document.getElementById("input-attachment").value = "CV_Demo_Fullstack.pdf";
    document.getElementById("input-summary").value = "Pengalaman 3+ tahun dalam pengembangan web full-stack menggunakan React, Node.js, dan PostgreSQL. Membangun aplikasi e-commerce dengan performa tinggi dan UI/UX yang responsif.";

    // Auto-select demo skills
    selectedSkills = ["JavaScript", "React", "Node.js", "SQL", "Git"];
    skillsInput.value = JSON.stringify(selectedSkills);
    skillsContainer.querySelectorAll("button").forEach((chip) => {
      if (selectedSkills.includes(chip.dataset.skill)) {
        chip.style.border = "2px solid #6366F1";
        chip.style.background = "#F3F0FF";
        chip.style.color = "#6366F1";
      }
    });

    UI.toast("Form diisi dengan contoh CV");
  };

  // Open modal
  document.getElementById("btn-add-evidence").addEventListener("click", () => {
    modal.style.display = "flex";
  });

  // Close modal
  const closeModal = () => {
    modal.style.display = "none";
    form.reset();
    selectedSkills = [];
    skillsInput.value = "";
    typeInput.value = "cv";

    // Reset all chips
    skillsContainer.querySelectorAll("button").forEach((chip) => {
      chip.style.border = "1px solid #E2E8F0";
      chip.style.background = "#F1F5F9";
      chip.style.color = "#475569";
    });

    // Reset type selection
    document.querySelectorAll(".type-option").forEach((b, i) => {
      if (i === 0) {
        b.style.border = "2px solid #6366F1";
        b.style.background = "#F3F0FF";
        b.querySelector("span").style.color = "#6366F1";
        const svg = b.querySelector("svg");
        if (svg) svg.setAttribute("stroke", "#6366F1");
      } else {
        b.style.border = "2px solid #E2E8F0";
        b.style.background = "#FFFFFF";
        b.querySelector("span").style.color = "#475569";
        const svg = b.querySelector("svg");
        if (svg) svg.setAttribute("stroke", "#475569");
      }
    });
  };

  document.getElementById("btn-close-modal").addEventListener("click", closeModal);
  document.getElementById("btn-cancel").addEventListener("click", closeModal);
  modal.addEventListener("click", closeModal);
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal.style.display === "flex") closeModal();
  });

  // Form submit
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    if (selectedSkills.length === 0 || selectedSkills.length > 6) {
      UI.toast("Pilih 1-6 skill", "error");
      return;
    }

    const formData = new FormData(form);
    const payload = {
      title: formData.get("title"),
      description: formData.get("summary") || "",
      source_type: typeInput.value,
      file_url: formData.get("attachment") || null,
      metadata: {
        date: new Date().toISOString().split("T")[0],
        level: formData.get("level"),
        skills: selectedSkills,
      },
    };

    try {
      await API.createEvidence(payload);
      UI.toast("Bukti berhasil disimpan!");
      closeModal();
      location.reload();
    } catch (err) {
      UI.toast(`Error: ${err.message}`, "error");
    }
  });

  function renderEvidenceCard(ev) {
    const typeMap = {
      project: { label: "Project", color: "var(--accent)", bg: "var(--tint-pink)" },
      certificate: { label: "Certificate", color: "#7c3aed", bg: "#f5f3ff" },
      portfolio: { label: "Portfolio", color: "#0891b2", bg: "#ecfeff" },
      other: { label: "Other", color: "var(--text-muted)", bg: "var(--tint-gray)" },
    };
    const type = typeMap[ev.source_type] || typeMap.other;

    const iconMap = {
      project: `<svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1"><path d="M6.375 2.125h4.25M4.25 14.167h8.5a1.417 1.417 0 0 0 1.417-1.417V4.25a1.417 1.417 0 0 0-1.417-1.417h-8.5A1.417 1.417 0 0 0 2.833 4.25v8.5c0 .782.635 1.417 1.417 1.417z"/></svg>`,
      certificate: `<svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1"><circle cx="8.5" cy="5.667" r="4.25"/><path d="M5.667 12.042v2.833l2.833-1.417 2.833 1.417v-2.833"/></svg>`,
      portfolio: `<svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1"><circle cx="8.5" cy="8.5" r="7.084"/><path d="M1.416 9.917h14.168M8.5 8.5c1.566 0 2.834-3.175 2.834-7.084M8.5 8.5c-1.566 0-2.834-3.175-2.834-7.084"/></svg>`,
      other: `<svg width="17" height="17" viewBox="0 0 17 17" fill="none" stroke="currentColor" stroke-width="1"><path d="M9.917 1.416H4.25a1.417 1.417 0 0 0-1.417 1.417v11.334c0 .782.635 1.416 1.417 1.416h8.5c.782 0 1.417-.634 1.417-1.416V5.667L9.917 1.416z"/><path d="M9.917 1.416v4.25h4.25M10.625 9.208H6.375M10.625 11.333H6.375M7.792 7.083H6.375"/></svg>`,
    };
    const icon = iconMap[ev.source_type] || iconMap.other;

    const card = el("article", { style: "background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 20px; box-shadow: var(--shadow-sm); display: grid; gap: 16px; position: relative" });

    // Top: badge + icon
    card.append(
      el("div", { style: "display: flex; justify-content: space-between; align-items: center" }, [
        el("span", { style: `display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; background: ${type.bg}; color: ${type.color}`, text: type.label }),
        el("div", { style: "width: 38px; height: 38px; border-radius: 10px; background: var(--tint-gray); display: grid; place-items: center", innerHTML: icon }),
      ])
    );

    // Title + description
    card.append(
      el("div", { style: "min-height: 80px" }, [
        el("h3", { style: "font-size: 18px; font-weight: 700; line-height: 1.3; color: var(--text-primary); margin-bottom: 8px", text: ev.title }),
        el("p", { style: "font-size: 14px; line-height: 1.5; color: var(--text-muted)", text: ev.description || "No description provided." }),
      ])
    );

    // Bottom: date + Open link
    const dateStr = ev.metadata?.date ? UI.formatDate(ev.metadata.date) : "No date";
    card.append(
      el("div", { style: "display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid var(--border)" }, [
        el("p", { style: "font-size: 13px; color: var(--text-light); font-weight: 500", text: `Updated ${dateStr}` }),
        ev.file_url
          ? el("a", {
              href: UI.safeUrl(ev.file_url) || "#",
              target: "_blank",
              rel: "noopener noreferrer",
              style: "display: inline-flex; align-items: center; gap: 6px; font-size: 14px; font-weight: 700; color: var(--primary); text-decoration: none",
            }, [
              document.createTextNode("Open"),
              el("span", { innerHTML: `<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M10.5 7.583v4.084a1.167 1.167 0 0 1-1.167 1.166H2.333a1.167 1.167 0 0 1-1.166-1.166V4.667A1.167 1.167 0 0 1 2.333 3.5h4.084M8.75 1.167h4.083v4.083M5.833 8.167l6.417-6.417"/></svg>` }),
            ])
          : el("span", { style: "font-size: 13px; color: var(--text-muted); font-weight: 500", text: "No link" }),
      ])
    );

    return card;
  }
})();

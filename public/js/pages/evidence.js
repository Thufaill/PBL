// evidence.js - Halaman bukti kompetensi.
// Seluruh baca/tulis lewat API.getEvidence()/addEvidence()/removeEvidence()
// yang berbicara ke public/api/evidence.php (PostgreSQL). Tidak ada lagi
// daftar skill hardcoded dan tidak ada lagi simpan di localStorage.

(async () => {
  const { el } = UI;

  const [, skills] = await Promise.all([API.ensureSession(), API.getSkillCatalog()]);

  const gridEl = document.getElementById("evidence-grid");
  const countEl = document.getElementById("count-label");

  // Modal elements
  const modal = document.getElementById("modal-add-evidence");
  const form = document.getElementById("form-add-evidence");
  const typeInput = document.getElementById("input-type");
  const linkInput = document.getElementById("input-link");
  const skillsContainer = document.getElementById("skills-container");

  let selectedSkillIds = [];

  // ---- Grid bukti ----
  // Render pertama dilakukan di bawah, setelah TYPE_LABELS/TYPE_ICONS
  // terisi; memanggil renderGrid() di sini menyentuh const yang belum
  // diinisialisasi (ReferenceError) dan seluruh halaman mati.

  function renderGrid() {
    const evidence = API.getEvidence();
    countEl.textContent = `${evidence.length} tersimpan`;
    gridEl.replaceChildren();

    if (evidence.length === 0) {
      gridEl.style.gridTemplateColumns = "1fr";
      gridEl.append(
        el("div", { style: "text-align: center; padding: 40px 20px; color: var(--text-muted)" }, [
          el("p", { style: "font-size: 16px; font-weight: 600; margin-bottom: 8px", text: "Belum ada bukti" }),
          el("p", { style: "font-size: 14px", text: "Gunakan tombol Add Evidence untuk mulai mendokumentasikan pekerjaanmu." }),
        ])
      );
      return;
    }

    gridEl.style.gridTemplateColumns = "";
    const cards = evidence.slice().reverse().map((ev) => renderEvidenceCard(ev));
    gridEl.append(...cards);
  }

  const TYPE_LABELS = {
    cv: { label: "CV", color: "var(--primary)", bg: "var(--tint-indigo)" },
    github: { label: "GitHub", color: "#334155", bg: "var(--tint-gray)" },
    portfolio: { label: "Portfolio", color: "#0891b2", bg: "#ecfeff" },
    project: { label: "Project", color: "var(--accent)", bg: "var(--tint-pink)" },
    certificate: { label: "Certificate", color: "#7c3aed", bg: "#f5f3ff" },
    assessment: { label: "Assessment", color: "var(--amber-text)", bg: "var(--amber-bg)" },
    other: { label: "Other", color: "var(--text-muted)", bg: "var(--tint-gray)" },
  };

  const TYPE_ICONS = {
    cv: `<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M5 2h10M4 18h12a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1z"/><circle cx="10" cy="7" r="2"/><path d="M7 11h6M7 13.5h4"/></svg>`,
    github: `<svg width="17" height="17" viewBox="0 0 20 20" fill="currentColor"><path d="M10 0C4.477 0 0 4.477 0 10c0 4.42 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.603-3.369-1.342-3.369-1.342-.454-1.155-1.11-1.462-1.11-1.462-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.578 9.578 0 0 1 10 4.836c.85.004 1.705.114 2.504.336 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C17.137 18.163 20 14.418 20 10c0-5.523-4.477-10-10-10z"/></svg>`,
    portfolio: `<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="10" cy="10" r="9"/><path d="M1 11h18M10 10c2 0 3-4 3-9M10 10c-2 0-3-4-3-9"/></svg>`,
    project: `<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 7h14M3 7V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v2M3 7v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7"/></svg>`,
    certificate: `<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="10" cy="7" r="5"/><path d="M7 14v4l3-2 3 2v-4"/></svg>`,
    assessment: `<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 2h8l4 4v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1z"/><path d="M7 10l2 2 4-4"/></svg>`,
    other: `<svg width="17" height="17" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V6l-4-4z"/><path d="M12 2v4h4"/></svg>`,
  };

  function renderEvidenceCard(ev) {
    const type = TYPE_LABELS[ev.source_type] || TYPE_LABELS.other;
    const icon = TYPE_ICONS[ev.source_type] || TYPE_ICONS.other;

    const card = el("article", { style: "background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 18px; box-shadow: var(--shadow-sm); display: grid; gap: 14px; position: relative" });

    // Top: badge + icon
    card.append(
      el("div", { style: "display: flex; justify-content: space-between; align-items: center" }, [
        el("span", { style: `display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 6px; font-size: 11px; font-weight: 700; background: ${type.bg}; color: ${type.color}`, text: type.label }),
        el("div", { style: "width: 34px; height: 34px; border-radius: 9px; background: var(--tint-gray); display: grid; place-items: center", innerHTML: icon }),
      ])
    );

    // Title + description
    card.append(
      el("div", { style: "min-height: 70px" }, [
        el("h3", { style: "font-size: 16px; font-weight: 700; line-height: 1.3; color: var(--text-primary); margin-bottom: 6px", text: ev.title }),
        el("p", { style: "font-size: 13.5px; line-height: 1.5; color: var(--text-muted)", text: ev.description || "Belum ada ringkasan." }),
      ])
    );

    // Skill yang dibuktikan (nama datang dari database)
    if ((ev.skills || []).length > 0) {
      card.append(
        el("div", { style: "display: flex; flex-wrap: wrap; gap: 6px" },
          ev.skills.map((name) =>
            el("span", { style: "font-size: 10.5px; font-weight: 600; padding: 3px 8px; border-radius: var(--radius-round); background: var(--tint-gray); color: var(--primary-dark)", text: name })
          )
        )
      );
    }

    // Bottom: date + link + hapus
    const dateStr = ev.metadata?.date ? UI.formatDate(ev.metadata.date) : UI.formatDate(ev.created_at);
    const safeLink = ev.file_url ? UI.safeUrl(ev.file_url) : null;

    card.append(
      el("div", { style: "display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px solid var(--border)" }, [
        el("p", { style: "font-size: 12px; color: var(--text-light); font-weight: 500", text: dateStr ? `Dibuat ${dateStr}` : "Tanpa tanggal" }),
        el("div", { style: "display: flex; align-items: center; gap: 10px" }, [
          safeLink
            ? el("a", {
                href: safeLink,
                target: "_blank",
                rel: "noopener noreferrer",
                style: "display: inline-flex; align-items: center; gap: 5px; font-size: 13px; font-weight: 700; color: var(--primary); text-decoration: none",
              }, [
                document.createTextNode("Buka"),
                el("span", { innerHTML: `<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M10.5 7.583v4.084a1.167 1.167 0 0 1-1.167 1.166H2.333a1.167 1.167 0 0 1-1.166-1.166V4.667A1.167 1.167 0 0 1 2.333 3.5h4.084M8.75 1.167h4.083v4.083M5.833 8.167l6.417-6.417"/></svg>` }),
              ])
            : el("span", { style: "font-size: 12px; color: var(--text-muted); font-weight: 500", text: "Tanpa tautan" }),
          el("button", {
            type: "button",
            text: "Hapus",
            style: "border: none; background: none; padding: 4px 2px; font-family: inherit; font-size: 12px; font-weight: 700; color: #b91c1c; cursor: pointer",
            onClick: () => removeEvidence(ev),
          }),
        ]),
      ])
    );

    return card;
  }

  async function removeEvidence(ev) {
    if (!confirm(`Hapus bukti "${ev.title}" dari profil Anda?`)) return;
    try {
      await API.removeEvidence(ev.id);
      UI.toast("Bukti dihapus.");
      renderGrid();
    } catch (err) {
      UI.toast(err.message);
    }
  }

  // ---- Chips skill dari katalog database ----

  for (const skill of skills) {
    const chip = el("button", {
      type: "button",
      text: skill.name,
      title: skill.category ? `${skill.name} · ${skill.category}` : skill.name,
      style: "padding: 5px 10px; border: 1px solid #E2E8F0; background: #F1F5F9; color: #475569; border-radius: 14px; font-size: 11.5px; font-weight: 500; cursor: pointer; transition: all 0.2s",
    });
    chip.dataset.skillId = String(skill.id);

    chip.addEventListener("click", () => {
      const id = skill.id;
      if (selectedSkillIds.includes(id)) {
        selectedSkillIds = selectedSkillIds.filter((s) => s !== id);
        paintChip(chip, false);
      } else if (selectedSkillIds.length < 6) {
        selectedSkillIds.push(id);
        paintChip(chip, true);
      } else {
        UI.toast("Satu bukti maksimal membuktikan 6 skill.");
      }
    });

    skillsContainer.append(chip);
  }

  function paintChip(chip, active) {
    chip.style.border = active ? "2px solid #6366F1" : "1px solid #E2E8F0";
    chip.style.background = active ? "#F3F0FF" : "#F1F5F9";
    chip.style.color = active ? "#6366F1" : "#475569";
  }

  function clearChips() {
    for (const chip of skillsContainer.querySelectorAll("button")) paintChip(chip, false);
  }

  // ---- Pilihan jenis bukti ----

  const typeButtons = [...document.querySelectorAll(".type-option")];

  function paintTypeButtons(activeIndex) {
    typeButtons.forEach((b, i) => {
      const active = i === activeIndex;
      b.style.border = active ? "2px solid #6366F1" : "2px solid #E2E8F0";
      b.style.background = active ? "#F3F0FF" : "#FFFFFF";
      b.querySelector("span").style.color = active ? "#6366F1" : "#475569";
      const svg = b.querySelector("svg");
      // Tombol GitHub memakai fill, bukan stroke: jangan dipaksa ganti warna.
      if (svg && svg.hasAttribute("stroke")) svg.setAttribute("stroke", active ? "#6366F1" : "#475569");
      if (svg && svg.hasAttribute("fill")) svg.setAttribute("fill", active ? "#6366F1" : "#475569");
    });
  }

  typeButtons.forEach((btn, index) => {
    btn.addEventListener("click", () => {
      paintTypeButtons(index);
      typeInput.value = btn.dataset.value;
    });
  });

  // ---- Isi otomatis dengan contoh ----

  document.getElementById("demo-banner").addEventListener("click", () => {
    document.getElementById("input-title").value = "CV Full Stack Developer - 2024";
    linkInput.value = "";
    document.getElementById("input-summary").value =
      "Pengalaman 3+ tahun dalam pengembangan web full-stack menggunakan React, Node.js, dan PostgreSQL. Membangun aplikasi e-commerce dengan performa tinggi dan UI/UX yang responsif.";
    document.getElementById("input-level").value = "intermediate";

    const wanted = ["javascript", "react", "node.js", "sql", "git"];
    selectedSkillIds = skills
      .filter((s) => wanted.includes(s.name.toLowerCase()))
      .slice(0, 6)
      .map((s) => s.id);
    if (selectedSkillIds.length === 0) {
      selectedSkillIds = skills.slice(0, 3).map((s) => s.id);
    }
    clearChips();
    for (const chip of skillsContainer.querySelectorAll("button")) {
      if (selectedSkillIds.includes(Number(chip.dataset.skillId))) paintChip(chip, true);
    }

    typeInput.value = "cv";
    paintTypeButtons(typeButtons.findIndex((b) => b.dataset.value === "cv"));
    UI.toast("Form diisi dengan contoh CV");
  });

  // ---- Buka / tutup modal ----

  document.getElementById("btn-add-evidence").addEventListener("click", () => {
    modal.style.display = "flex";
  });

  const closeModal = () => {
    modal.style.display = "none";
    form.reset();
    selectedSkillIds = [];
    typeInput.value = "cv";
    clearChips();
    paintTypeButtons(typeButtons.findIndex((b) => b.dataset.value === "cv"));
  };

  document.getElementById("btn-close-modal").addEventListener("click", closeModal);
  document.getElementById("btn-cancel").addEventListener("click", closeModal);
  modal.addEventListener("click", closeModal);
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal.style.display === "flex") closeModal();
  });

  // ---- Simpan ----

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    if (selectedSkillIds.length === 0) {
      UI.toast("Pilih 1-6 skill");
      return;
    }

    const formData = new FormData(form);
    const link = String(formData.get("link") || "").trim();

    const payload = {
      source_type: typeInput.value,
      title: formData.get("title"),
      description: formData.get("summary") || "",
      file_url: link === "" ? null : link,
      skill_ids: selectedSkillIds,
      metadata: {
        date: new Date().toISOString().split("T")[0],
        level: formData.get("level"),
      },
    };

    try {
      await API.addEvidence(payload);
      UI.toast("Bukti tersimpan di profil Anda.");
      closeModal();
      renderGrid();
    } catch (err) {
      UI.toast(err.message);
    }
  });

  renderGrid();
})();

// admin.js - render dashboard admin (Admin Console)
// Data dari API.getAdminStats / API.getAdminUsers dst. Saat ini memakai JSON dummy.

(async () => {
  const { el } = UI;

  // ---- Stat cards ----
  try {
    const stats = await API.getAdminStats();
    const grid = document.getElementById("admin-stats-grid");
    if (grid) {
      UI.clear(grid);
      const cards = [
        { label: "Total pengguna", value: 1248, context: "986 mahasiswa · 262 fresh graduate" },
        { label: "Administrator", value: 12, context: "1 Admin · 7 SkillBridge · 4 Job" },
        { label: "Keterampilan", value: 48, context: "Record keterampilan master" },
        { label: "Target karier", value: 8, context: "Record target karier master" },
        { label: "Relasi mapping", value: 64, context: "Relasi karier–keterampilan" },
      ];
      for (const c of cards) {
        grid.append(
          el("div", { class: "admin-stat-card" }, [
            el("div", { class: "admin-stat-card-main" }, [
              el("span", { class: "admin-stat-label", text: c.label }),
              el("span", { class: "admin-stat-value", text: c.value.toLocaleString("id-ID") }),
            ]),
            el("p", { class: "admin-stat-context", text: c.context }),
          ])
        );
      }
    }
  } catch (err) {
    // mode tanpa API tetap render angka static di HTML
  }

  // ---- System activity ----
  const systemRows = [
    { time: "10.42", text: "Nadia Putri membuat akun", cat: "Pengguna" },
    { time: "10.30", text: "Python ditambahkan ke Data Analyst", cat: "Mapping" },
    { time: "09.58", text: "Deskripsi REST APIs diperbarui", cat: "Keterampilan" },
    { time: "09.15", text: "Target karier Data Engineer dibuat", cat: "Target karier" },
  ];

  const sysBody = document.getElementById("system-activity-body");
  if (sysBody) {
    UI.clear(sysBody);
    for (const r of systemRows) {
      sysBody.append(
        el("div", { class: "admin-row" }, [
          el("span", { class: "admin-row-time", text: r.time }),
          el("span", { class: "admin-row-text col-activity", text: r.text }),
          el("span", { class: "admin-row-cat" }, [
            el("span", { class: "admin-badge-pill", text: r.cat }),
          ]),
        ])
      );
    }
  }

  // ---- Admin activity ----
  const adminRows = [
    {
      actor: "Aditya Pratama · Admin",
      detail: "Menambahkan Dimas Saputra · Admin Job",
      time: "10.12",
    },
    {
      actor: "Rani Wulandari · Admin SkillBridge",
      detail: "Memperbarui deskripsi keterampilan",
      time: "09.58",
    },
    {
      actor: "Aditya Pratama · Admin",
      detail: "Menonaktifkan akun Bima Santoso",
      time: "Kemarin",
    },
  ];

  const admBody = document.getElementById("admin-activity-body");
  if (admBody) {
    UI.clear(admBody);
    for (const r of adminRows) {
      admBody.append(
        el("div", { class: "admin-admin-row" }, [
          el("div", { class: "admin-admin-main" }, [
            el("p", { class: "admin-admin-actor", text: r.actor }),
            el("p", { class: "admin-admin-detail", text: r.detail }),
          ]),
          el("span", { class: "admin-admin-time", text: r.time }),
        ])
      );
    }
  }
})();

// admin.js - Dashboard Admin Console.
// Semua angka dan baris aktivitas datang dari public/api/admin/stats.php.
// Lima kartu yang dulu ditulis keras (1248 pengguna, 48 keterampilan, dst.)
// dan dua feed berisi nama fiktif kini dihitung dari database.

(async () => {
  const { el } = UI;

  const CARDS = [
    {
      label: "Total pengguna",
      value: (s) => s.users,
      context: (s) => `${s.activeUsers} aktif · ${s.suspendedUsers} dinonaktifkan`,
    },
    {
      label: "Administrator",
      value: (s) => s.platformAdmins,
      context: () => "Admin SkillBridge",
    },
    {
      label: "Keterampilan",
      value: (s) => s.skills,
      context: () => "Record keterampilan master",
    },
    {
      label: "Target karier",
      value: (s) => s.careers,
      context: () => "Record target karier master",
    },
    {
      label: "Relasi mapping",
      value: (s) => s.mappings,
      context: (s) => `${s.publishedContent} konten terbit`,
    },
  ];

  const grid = document.getElementById("admin-stats-grid");
  const sysBody = document.getElementById("system-activity-body");
  const admBody = document.getElementById("admin-activity-body");

  // Waktu: jam menit untuk hari ini, lalu "Kemarin" / "N hari lalu".
  function activityTime(raw) {
    if (!raw) return "—";
    const date = new Date(raw);
    if (Number.isNaN(date.getTime())) return String(raw).slice(0, 10);
    const now = new Date();
    const days = Math.floor((now.setHours(0, 0, 0, 0) - date.setHours(0, 0, 0, 0)) / 86400000);
    if (days <= 0) {
      return `${String(new Date(raw).getHours()).padStart(2, "0")}.${String(new Date(raw).getMinutes()).padStart(2, "0")}`;
    }
    if (days === 1) return "Kemarin";
    if (days < 7) return `${days} hari lalu`;
    if (days < 60) return `${Math.floor(days / 7)} minggu lalu`;
    return new Date(raw).toLocaleDateString("id-ID", { day: "numeric", month: "short", year: "numeric" });
  }

  try {
    const stats = await API.getAdminStats();

    if (grid) {
      UI.clear(grid);
      for (const c of CARDS) {
        grid.append(
          el("div", { class: "admin-stat-card" }, [
            el("div", { class: "admin-stat-card-main" }, [
              el("span", { class: "admin-stat-label", text: c.label }),
              el("span", { class: "admin-stat-value", text: Number(c.value(stats)).toLocaleString("id-ID") }),
            ]),
            el("p", { class: "admin-stat-context", text: c.context(stats) }),
          ])
        );
      }
    }

    if (sysBody) {
      UI.clear(sysBody);
      if (stats.recent.length === 0) {
        sysBody.append(el("div", { class: "admin-empty", text: "Belum ada data tercatat." }));
      }
      for (const r of stats.recent) {
        sysBody.append(
          el("div", { class: "admin-row" }, [
            el("span", { class: "admin-row-time", text: activityTime(r.time) }),
            el("span", { class: "admin-row-text col-activity", text: r.text }),
            el("span", { class: "admin-row-cat" }, [
              el("span", { class: "admin-badge-pill", text: r.cat }),
            ]),
          ])
        );
      }
    }

    if (admBody) {
      UI.clear(admBody);
      if (stats.accounts.length === 0) {
        admBody.append(el("div", { class: "admin-empty", text: "Belum ada akun administrator." }));
      }
      for (const r of stats.accounts) {
        admBody.append(
          el("div", { class: "admin-admin-row" }, [
            el("div", { class: "admin-admin-main" }, [
              el("p", { class: "admin-admin-actor", text: r.actor }),
              el("p", { class: "admin-admin-detail", text: r.detail }),
            ]),
            el("span", { class: "admin-admin-time", text: activityTime(r.time) }),
          ])
        );
      }
    }
  } catch (err) {
    if (grid) UI.clear(grid);
    const target = sysBody || grid;
    if (target) {
      target.append(el("div", { class: "admin-empty", text: err.message }));
    }
  }
})();

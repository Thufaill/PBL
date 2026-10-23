// ui.js - helper DOM bersama. Semua render memakai createElement/textContent

const UI = (() => {
  const getBaseUrl = () => {
    return (typeof window !== "undefined" && window.APP_BASE_URL !== undefined)
      ? (window.APP_BASE_URL === "/" ? "" : window.APP_BASE_URL.replace(/\/+$/, ""))
      : "";
  };

  const NAV_ITEMS = [
    { key: "dashboard", label: "Dashboard", href: "mahasiswa/dashboard.php" },
    { key: "skills", label: "My Skills", href: "mahasiswa/skills.php" },
    { key: "evidence", label: "Evidence", href: "mahasiswa/evidence.php" },
    { key: "career", label: "Career Target", href: "mahasiswa/career.php" },
    { key: "gap", label: "Skill Gap", href: "mahasiswa/gap.php" },
    { key: "profile", label: "Profile", href: "mahasiswa/profile.php" },
  ];

  // el("span", { class: "tag" }, ["PHP"]) -> <span class="tag">PHP</span>
  function el(tag, props = {}, children = []) {
    const node = document.createElement(tag);
    for (const [key, value] of Object.entries(props)) {
      if (value === undefined || value === null) continue;
      if (key === "class") node.className = value;
      else if (key === "text") node.textContent = value;
      else if (key === "onClick") node.addEventListener("click", value);
      // HANYA untuk markup statis tepercaya (mis. ikon SVG). Jangan isi dengan data pengguna/API.
      else if (key === "innerHTML") node.innerHTML = value;
      else node.setAttribute(key, value);
    }
    for (const child of [].concat(children)) {
      if (child === null || child === undefined) continue;
      node.append(child instanceof Node ? child : document.createTextNode(String(child)));
    }
    return node;
  }
 
  function clear(node) {
    while (node.firstChild) node.removeChild(node.firstChild);
    return node;
  }

  function safeUrl(url) {
    try {
      const u = new URL(url, window.location.href);
      return u.protocol === "http:" || u.protocol === "https:" ? u.href : null;
    } catch {
      return null;
    }
  }

  function toast(message) {
    const box = el("div", { class: "toast", role: "status", text: message });
    document.body.append(box);
    setTimeout(() => box.remove(), 2600);
  }

  // modal({ title, body, submitLabel, onSubmit }) -> dialog dengan focus trap.
  function modal({ title, body = [], submitLabel = "Save", cancelLabel = "Cancel", onSubmit }) {
    const previouslyFocused = document.activeElement;

    const form = el("form", { class: "modal-form", novalidate: "novalidate" }, body);
    const cancelBtn = el("button", { type: "button", class: "btn btn-secondary", text: cancelLabel, onClick: () => close() });
    const submitBtn = el("button", { type: "submit", class: "btn btn-accent", text: submitLabel });

    const dialog = el("div", { class: "modal", role: "dialog", "aria-modal": "true", "aria-label": title }, [
      el("h3", { class: "modal-title", text: title }),
      form,
      el("div", { class: "modal-actions" }, [cancelBtn, submitBtn]),
    ]);

    const overlay = el("div", { class: "modal-overlay" }, [dialog]);

    function focusables() {
      return [...dialog.querySelectorAll("button, input, select, textarea, a[href]")].filter(
        (node) => !node.disabled
      );
    }

    function onKeydown(event) {
      if (event.key === "Escape") {
        event.preventDefault();
        close();
        return;
      }
      if (event.key !== "Tab") return;
      const nodes = focusables();
      if (nodes.length === 0) return;
      const first = nodes[0];
      const last = nodes[nodes.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    }

    function close() {
      document.removeEventListener("keydown", onKeydown);
      overlay.remove();
      if (previouslyFocused instanceof HTMLElement) previouslyFocused.focus();
    }

    overlay.addEventListener("mousedown", (event) => {
      if (event.target === overlay) close();
    });

    form.addEventListener("submit", (event) => {
      event.preventDefault();
      if (onSubmit && onSubmit(close, form) === false) return;
      close();
    });

    document.addEventListener("keydown", onKeydown);
    document.body.append(overlay);
    (focusables()[0] || dialog).focus();
    return close;
  }

  function renderNavbar() {
    // Cegah render ulang jika navbar sudah di-render oleh server (PHP)
    if (document.querySelector("header.navbar")) return;

    const active = document.body.dataset.nav || "";
    const base = getBaseUrl();

    const navLinks = el(
      "div",
      { class: "navbar-nav" },
      NAV_ITEMS.map((item) =>
        el("a", {
          class: item.key === active ? "nav-item active" : "nav-item",
          href: `${base}/${item.href}`,
          text: item.label,
          ...(item.key === active ? { "aria-current": "page" } : {}),
        })
      )
    );

    const brand = el("a", { class: "navbar-brand", href: `${base}/index.php`, "aria-label": "SkillBridge home" }, [
      el("div", { class: "navbar-logo", text: "S" }),
      el("div", { class: "navbar-brand-text" }, [
        el("div", { class: "navbar-brand-name", text: "SkillBridge" }),
        el("div", { class: "navbar-brand-subtitle", text: "STUDENT PORTFOLIO" }),
      ]),
    ]);

    const profile = el("div", { class: "navbar-profile" }, [
      el("div", { class: "navbar-profile-text" }, [
        el("div", { class: "navbar-profile-name", text: "Jhon Doe" }),
        el("div", { class: "navbar-profile-email", text: "jhondoe@gmail.com" }),
      ]),
      el("div", { class: "navbar-avatar", text: "JD" }),
    ]);

    const toggle = el("button", {
      type: "button",
      class: "navbar-toggle",
      "aria-label": "Buka menu navigasi",
      "aria-expanded": "false",
      innerHTML: '<span class="navbar-toggle-bar"></span><span class="navbar-toggle-bar"></span><span class="navbar-toggle-bar"></span>',
    });

    const bar = el("header", { class: "navbar" }, [brand, toggle, navLinks, profile]);
    document.body.prepend(bar);
  }

  document.addEventListener("DOMContentLoaded", () => {
    if (document.body.dataset.nav !== undefined) renderNavbar();

    // Event Delegation: Navbar Hamburger Toggle Mobile
    document.addEventListener("click", (e) => {
      const toggle = e.target.closest(".navbar-toggle");
      if (toggle) {
        const navbar = toggle.closest(".navbar");
        if (navbar) {
          const isOpen = navbar.classList.toggle("is-open");
          toggle.setAttribute("aria-expanded", String(isOpen));
        }
        return;
      }

      // Close mobile navbar when clicking outside
      const openNav = document.querySelector(".navbar.is-open");
      if (openNav && !openNav.contains(e.target)) {
        openNav.classList.remove("is-open");
        const toggleBtn = openNav.querySelector(".navbar-toggle");
        if (toggleBtn) toggleBtn.setAttribute("aria-expanded", "false");
      }

      // Sidebar Toggle HR & Admin Drawer Mobile
      const sidebarToggle = e.target.closest("[data-toggle-sidebar], .sidebar-toggle");
      if (sidebarToggle) {
        const sidebar = document.querySelector(".rec-sidebar, .admin-sidebar");
        const backdrop = document.querySelector(".sidebar-backdrop");
        if (sidebar) sidebar.classList.toggle("is-open");
        if (backdrop) backdrop.classList.toggle("is-open");
        return;
      }

      // Sidebar Backdrop / Close Drawer Mobile
      const sidebarClose = e.target.closest("[data-close-sidebar], .sidebar-backdrop, .sidebar-close-btn");
      if (sidebarClose) {
        const sidebar = document.querySelector(".rec-sidebar, .admin-sidebar");
        const backdrop = document.querySelector(".sidebar-backdrop");
        if (sidebar) sidebar.classList.remove("is-open");
        if (backdrop) backdrop.classList.remove("is-open");
        return;
      }
    });

    // Close mobile menus on Escape key
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") {
        const openNav = document.querySelector(".navbar.is-open");
        if (openNav) {
          openNav.classList.remove("is-open");
          const toggle = openNav.querySelector(".navbar-toggle");
          if (toggle) toggle.setAttribute("aria-expanded", "false");
        }
        const openSidebar = document.querySelector(".rec-sidebar.is-open, .admin-sidebar.is-open");
        if (openSidebar) {
          openSidebar.classList.remove("is-open");
          const backdrop = document.querySelector(".sidebar-backdrop");
          if (backdrop) backdrop.classList.remove("is-open");
        }
      }
    });
  });

  function formatDate(iso) {
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return "";
    return d.toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
  }

  return { el, clear, safeUrl, toast, formatDate, modal };
})();

window.UI = UI;

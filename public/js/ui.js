// ui.js - helper DOM bersama. Semua render memakai createElement/textContent

const UI = (() => {

  // el("span", { class: "tag" }, ["PHP"]) -> <span class="tag">PHP</span>
  function el(tag, props = {}, children = []) {
    const node = document.createElement(tag);
    for (const [key, value] of Object.entries(props)) {
      if (value === undefined || value === null) continue;
      if (key === "class") node.className = value;
      else if (key === "text") node.textContent = value;
      else if (key.startsWith("on") && typeof value === "function") {
        node.addEventListener(key.slice(2).toLowerCase(), value);
      }
      // HANYA untuk markup statis tepercaya (mis. ikon SVG). Jangan isi dengan data pengguna/API.
      else if (key === "innerHTML") node.innerHTML = value;
      else if (key === "checked" || key === "disabled" || key === "selected") {
        node[key] = Boolean(value);
        if (value) node.setAttribute(key, "");
      }
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

  function toast(message, kind) {
    const box = el("div", { class: kind === "error" ? "toast toast--error" : "toast", role: "status", text: message });
    document.body.append(box);
    setTimeout(() => box.remove(), 2600);
  }

  // modal({ title, body, submitLabel, onSubmit }) -> dialog dengan focus trap.
  function modal({ title, body = [], submitLabel = "Save", cancelLabel = "Cancel", onSubmit }) {
    const previouslyFocused = document.activeElement;

    const formId = "modal-form-" + Math.random().toString(36).slice(2, 9);
    const form = el("form", { id: formId, class: "modal-form", novalidate: "novalidate" }, body);
    const cancelBtn = el("button", { type: "button", class: "btn btn-secondary", text: cancelLabel, onClick: () => close() });
    const submitBtn = el("button", { type: "submit", class: "btn btn-accent", text: submitLabel, form: formId });
    submitBtn.addEventListener("click", (e) => {
      e.preventDefault();
      if (typeof form.requestSubmit === "function") {
        form.requestSubmit();
      } else {
        form.dispatchEvent(new Event("submit", { cancelable: true, bubbles: true }));
      }
    });

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

    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      if (onSubmit) {
        submitBtn.disabled = true;
        try {
          // onSubmit async: tunggu sampai selesai supaya kegagalan tulis ke
          // server bisa menahan modal tetap terbuka (return false).
          if (await onSubmit(close, form) === false) return;
        } finally {
          submitBtn.disabled = false;
        }
      }
      close();
    });

    document.addEventListener("keydown", onKeydown);
    document.body.append(overlay);
    (focusables()[0] || dialog).focus();
    return close;
  }


  // Tabel data menjadi kartu bertumpuk di ponsel. CSS memakai
  // td[data-label] sebagai judul kolom; label disalin dari <thead> dan
  // diperbarui lewat observer karena baris dirender ulang oleh page script.
  function labelTable(table) {
    const headers = Array.from(table.tHead?.rows[0]?.cells || []).map((th) => th.textContent.trim());
    if (headers.length === 0) return;

    table.querySelectorAll("tbody").forEach((tbody) => {
      const applyLabels = () => {
        for (const row of tbody.rows) {
          Array.from(row.cells).forEach((cell, index) => {
            if (cell.colSpan > 1 || !headers[index]) {
              cell.removeAttribute("data-label");
              return;
            }
            cell.dataset.label = headers[index];
          });
        }
      };

      applyLabels();
      if (tbody.dataset.sbLabels === "true") return;
      tbody.dataset.sbLabels = "true";
      new MutationObserver(applyLabels).observe(tbody, { childList: true });
    });
  }

  function initTableLabels() {
    document.querySelectorAll("table").forEach(labelTable);
  }

  document.addEventListener("DOMContentLoaded", () => {
    initTableLabels();

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

  // ---- Loading state halaman ----
  // Region bertanda [data-loading] diisi skeleton sampai halaman menulis
  // konten asli, empty state, atau pesan error. Form memakai overlay karena
  // nilainya diisi lewat properti input, bukan mutasi DOM.
  const SKELETON_ATTR = "data-sb-skeleton";
  const regionObservers = new WeakMap();
  let progressBar = null;
  let progressShowTimer = null;
  let progressHideTimer = null;
  let settleTimer = null;
  let activeRequests = 0;
  let initialSettled = false;

  function skeletonLine(width, extraClass = "") {
    return el("span", {
      class: "sb-skeleton-line" + (extraClass ? " " + extraClass : ""),
      style: width ? `width: ${width}` : null,
      "data-sb-skeleton": "",
      "aria-hidden": "true",
    });
  }

  function skeletonCard() {
    return el("div", { class: "sb-skeleton-card", "data-sb-skeleton": "", "aria-hidden": "true" }, [
      skeletonLine("48%"),
      skeletonLine("36%", "sb-skeleton-line--value"),
      skeletonLine("72%"),
    ]);
  }

  function skeletonRow() {
    return el("div", { class: "sb-skeleton-row", "data-sb-skeleton": "", "aria-hidden": "true" }, [
      el("span", { class: "sb-skeleton-avatar", "aria-hidden": "true" }),
      el("span", { class: "sb-skeleton-stack" }, [
        skeletonLine("34%"),
        skeletonLine("62%"),
      ]),
    ]);
  }

  function skeletonTableRow(colspan) {
    return el("tr", { class: "sb-skeleton-tr", "data-sb-skeleton": "", "aria-hidden": "true" }, [
      el("td", { colspan: String(colspan) }, [
        el("div", { class: "sb-skeleton-row sb-skeleton-row--table" }, [
          el("span", { class: "sb-skeleton-avatar", "aria-hidden": "true" }),
          el("span", { class: "sb-skeleton-stack" }, [
            skeletonLine("26%"),
            skeletonLine("54%"),
          ]),
        ]),
      ]),
    ]);
  }

  function skeletonTextBlock() {
    return el("div", { class: "sb-skeleton-text", "data-sb-skeleton": "", "aria-hidden": "true" }, [
      skeletonLine("32%", "sb-skeleton-line--title"),
      skeletonLine("100%"),
      skeletonLine("88%"),
      skeletonLine("58%"),
    ]);
  }

  function hasMeaningfulContent(region) {
    for (const node of region.childNodes) {
      if (node.nodeType === Node.TEXT_NODE && node.textContent.trim()) return true;
      if (node.nodeType === Node.ELEMENT_NODE && !node.hasAttribute(SKELETON_ATTR)) return true;
    }
    return false;
  }

  function skeletonCount(region, fallback) {
    const value = Number.parseInt(region.getAttribute("data-loading-count") || "", 10);
    return Number.isFinite(value) && value > 0 ? Math.min(value, 8) : fallback;
  }

  function buildSkeletons(region) {
    const variant = region.getAttribute("data-loading");
    if (variant === "cards") {
      return Array.from({ length: skeletonCount(region, 4) }, () => skeletonCard());
    }
    if (variant === "table") {
      const colspan = Math.max(1, Number.parseInt(region.getAttribute("data-loading-colspan") || "4", 10));
      return Array.from({ length: skeletonCount(region, 4) }, () => skeletonTableRow(colspan));
    }
    if (variant === "text") return [skeletonTextBlock()];
    return Array.from({ length: skeletonCount(region, 3) }, () => skeletonRow());
  }

  function finishRegion(region) {
    const observer = regionObservers.get(region);
    if (observer) {
      observer.disconnect();
      regionObservers.delete(region);
    }
    region.querySelectorAll("[data-sb-skeleton]").forEach((node) => node.remove());
    region.classList.remove("sb-is-loading");
    region.removeAttribute("aria-busy");
  }

  function initFormSkeleton(form) {
    if (!form.querySelector("input, select, textarea")) return;
    form.classList.add("sb-form-loading");
    form.setAttribute("aria-busy", "true");
    if (getComputedStyle(form).position === "static") form.style.position = "relative";
    form.append(
      el("div", { class: "sb-form-skeleton", "data-sb-skeleton": "", "aria-hidden": "true" }, [
        ...Array.from({ length: 3 }, () =>
          el("div", { class: "sb-skeleton-field" }, [
            skeletonLine("28%"),
            skeletonLine("100%"),
          ])
        ),
      ])
    );
  }

  function finishFormSkeleton(form) {
    form.querySelectorAll(".sb-form-skeleton").forEach((node) => node.remove());
    form.classList.remove("sb-form-loading");
    form.removeAttribute("aria-busy");
  }

  function initLoadingRegion(region) {
    if (region.dataset.sbLoadingInit === "true") return;
    region.dataset.sbLoadingInit = "true";

    if (region.getAttribute("data-loading") === "form") {
      initFormSkeleton(region);
      return;
    }
    if (hasMeaningfulContent(region)) return;

    region.append(...buildSkeletons(region));
    region.classList.add("sb-is-loading");
    region.setAttribute("aria-busy", "true");

    const observer = new MutationObserver(() => {
      if (hasMeaningfulContent(region)) finishRegion(region);
    });
    observer.observe(region, { childList: true, subtree: true, characterData: true });
    regionObservers.set(region, observer);
  }

  function settleInitialLoading() {
    initialSettled = true;
    document.querySelectorAll("[data-loading]").forEach((region) => {
      if (region.getAttribute("data-loading") === "form") finishFormSkeleton(region);
      else finishRegion(region);
    });
    // Placeholder skeleton statis di teks (mis. "Memuat...") tidak punya
    // observer sendiri, jadi dibersihkan bersama gelombang request pertama.
    document.querySelectorAll("[data-sb-skeleton]").forEach((node) => node.remove());
  }

  function showProgress() {
    if (!progressBar) {
      progressBar = el("div", { class: "sb-progress", "aria-hidden": "true" }, [
        el("span", { class: "sb-progress-bar" }),
      ]);
      document.body.append(progressBar);
    }
    progressBar.classList.add("is-active");
  }

  function hideProgress() {
    if (progressBar) progressBar.classList.remove("is-active");
  }

  function onRequestStart() {
    activeRequests += 1;
    clearTimeout(progressHideTimer);
    clearTimeout(settleTimer);
    if (activeRequests === 1) {
      clearTimeout(progressShowTimer);
      progressShowTimer = setTimeout(showProgress, 120);
    }
  }

  function onRequestEnd() {
    activeRequests = Math.max(0, activeRequests - 1);
    if (activeRequests !== 0) return;

    clearTimeout(progressShowTimer);
    progressHideTimer = setTimeout(hideProgress, 160);
    if (!initialSettled) {
      settleTimer = setTimeout(settleInitialLoading, 260);
    }
  }

  function initLoadingUI() {
    window.addEventListener("sb:api:start", onRequestStart);
    window.addEventListener("sb:api:end", onRequestEnd);
    document.querySelectorAll("[data-loading]").forEach(initLoadingRegion);
  }

  initLoadingUI();

  return { el, clear, safeUrl, toast, formatDate, modal };
})();

window.UI = UI;

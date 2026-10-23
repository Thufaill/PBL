// landing.js — interaksi minimal untuk index.php (search + smooth scroll).

(function () {
  "use strict";

  const baseUrl = (typeof window !== "undefined" && window.APP_BASE_URL !== undefined)
    ? (window.APP_BASE_URL === "/" ? "" : window.APP_BASE_URL.replace(/\/+$/, ""))
    : "";

  // Search form: arahkan ke mahasiswa/skills.php dengan query.
  const searchForm = document.getElementById("hero-search");
  if (searchForm) {
    searchForm.addEventListener("submit", function (e) {
      e.preventDefault();
      const input = searchForm.querySelector('input[type="search"], input[name="q"]');
      const q = input ? input.value.trim() : "";
      window.location.href = `${baseUrl}/mahasiswa/skills.php` + (q ? "#q=" + encodeURIComponent(q) : "");
    });
  }

  // Smooth scroll untuk anchor internal (hormati prefers-reduced-motion)
  const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (!reduce) {
    document.querySelectorAll('a[href^="#"]').forEach(function (a) {
      a.addEventListener("click", function (e) {
        const id = a.getAttribute("href");
        if (!id || id.length < 2) return;
        const target = document.querySelector(id);
        if (!target) return;
        e.preventDefault();
        target.scrollIntoView({ behavior: "smooth", block: "start" });
      });
    });
  }
})();

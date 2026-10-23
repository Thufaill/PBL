// admin-nav.js — native <details> accordion untuk sidebar admin.
// Auto-buka grup yang berisi halaman aktif; fallback untuk chip-bar mobile.
document.addEventListener("DOMContentLoaded", () => {
  const page = document.body.dataset.nav; // "dashboard" | "users" | ... | undefined

  document.querySelectorAll(".admin-nav-group").forEach(group => {
    const links = group.querySelectorAll(".admin-nav-item");
    links.forEach(a => {
      if (!page && a.classList.contains("active")) group.open = true;
      if (page && a.dataset.nav === page) group.open = true;
    });
  });
 
  // Mobile chip-bar: pastikan semua <a> terlihat (tekan sekali).
  const mobileNav = document.querySelector(".admin-sidebar");
  // Tidak perlu interaksi tambahan; <details> native sudah dapat dipindah di scroll.
});

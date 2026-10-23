// register.js - validasi sisi klien saja.
// Pengiriman dilakukan oleh <form method="POST"> biasa ke register.php (PHP yang memverifikasi
// CSRF, memvalidasi ulang, dan menyimpan ke database).

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("register-form");
  if (!form) return;

  form.addEventListener("submit", (e) => {
    const name = form.name.value.trim();
    const email = form.email.value.trim();
    const password = form.password.value;

    if (!name || !email || !password) {
      e.preventDefault();
      UI.toast("All fields are required");
      return;
    }

    if (password.length < 8) {
      e.preventDefault();
      UI.toast("Password must be at least 8 characters");
    }
  });
});

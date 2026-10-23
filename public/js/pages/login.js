// login.js - validasi sisi klien saja.
// Pengiriman dilakukan oleh <form method="POST"> biasa ke login.php (PHP yang memverifikasi
// CSRF dan kredensial), sehingga tidak ada preventDefault/fetch di sini.

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("login-form");
  if (!form) return;

  form.addEventListener("submit", (e) => {
    const email = form.email.value.trim();
    const password = form.password.value;

    if (!email || !password) {
      e.preventDefault();
      UI.toast("Email and password required");
    }
  });
});

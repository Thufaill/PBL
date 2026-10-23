<?php
// public/admin/settings.php
// Pengaturan Sistem · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('admin');

$page_title = 'Pengaturan Sistem · SkillBridge';
$role = 'admin';
$path_prefix = '../';
$admin_page = 'settings';
$body_class = 'admin-body';
$body_attrs = 'data-admin-page="settings"';
$page_script = 'admin-settings.js';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/admin_sidebar.php';
?>

    <!-- ===== MAIN ===== -->
    <div class="admin-workspace">
      <?php require_once __DIR__ . '/../../includes/layouts/admin_header.php'; ?>

      <main class="admin-page-content">
      <div class="admin-page-heading">
        <div>
          <p class="admin-eyebrow">Konfigurasi</p>
          <h1 class="admin-page-title">Pengaturan Sistem</h1>
          <p class="admin-page-desc">Pengaturan akun administrator dan konfigurasi platform SkillBridge.</p>
        </div>
      </div>

      <!-- Profil administrator -->
      <div class="admin-card">
        <div class="admin-card-head">
          <div>
            <p class="admin-card-title">Profil Administrator</p>
            <p class="admin-card-sub">Informasi akun Admin yang sedang aktif.</p>
          </div>
        </div>
        <form id="form-admin-profile" data-loading="form">
          <div class="field">
            <label for="set-name">Nama lengkap</label>
            <input type="text" id="set-name" name="name" autocomplete="name" />
          </div>
          <div class="field">
            <label for="set-email">Email</label>
            <input type="email" id="set-email" name="email" autocomplete="email" />
          </div>
          <div class="field">
            <label for="set-current-password">Password saat ini</label>
            <input type="password" id="set-current-password" name="current_password" autocomplete="current-password" placeholder="Hanya perlu saat mengganti password" />
          </div>
          <div class="field">
            <label for="set-password">Password baru</label>
            <input type="password" id="set-password" name="password" autocomplete="new-password" placeholder="Biarkan kosong jika tidak diubah" />
          </div>
        </form>
        <div class="row-end" style="display:flex;justify-content:flex-end">
          <button class="btn btn-primary" type="submit" form="form-admin-profile">Simpan Perubahan</button>
        </div>
      </div>

      <!-- Konfigurasi platform -->
      <div class="admin-card">
        <div class="admin-card-head">
          <div>
            <p class="admin-card-title">Konfigurasi Platform</p>
            <p class="admin-card-sub">Preferensi umum yang berlaku untuk seluruh pengguna.</p>
          </div>
        </div>
        <form id="form-platform-config" data-loading="form">
          <div class="field">
            <label for="set-sitename">Nama platform</label>
            <input type="text" id="set-sitename" name="site_name" autocomplete="off" />
          </div>
          <div class="field">
            <label for="set-maxevidence">Batas maksimum evidence per pengguna</label>
            <input type="number" id="set-maxevidence" name="max_evidence" min="1" max="100" />
          </div>
          <div class="field">
            <label for="set-visibility">Visibilitas portofolio default</label>
            <select id="set-visibility" name="default_visibility">
              <option value="Public">Public</option>
              <option value="Private">Private</option>
            </select>
          </div>
          <div class="field" style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
            <input type="checkbox" id="set-registration" name="registration_open" style="width:auto" />
            <label for="set-registration" style="margin:0">Buka pendaftaran pengguna baru</label>
          </div>
        </form>
        <div style="display:flex;justify-content:flex-end">
          <button class="btn btn-primary" type="submit" form="form-platform-config">Simpan Konfigurasi</button>
        </div>
      </div>

      <!-- Info keamanan -->
      <div class="admin-welcome-strip">
        <div class="admin-welcome-icon">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"
            stroke-linecap="round" stroke-linejoin="round">
            <path d="M10 2L3 5.5v5c0 4 3 7.5 7 8.5 4-1 7-4.5 7-8.5v-5L10 2z" />
            <path d="M7 10l2.5 2.5L14 8" />
          </svg>
        </div>
        <div class="admin-welcome-text">
          <strong>Catatan keamanan</strong>
          <span>Pembatasan peran di sisi klien hanya untuk prototipe. Otorisasi wajib ditegakkan di server.</span>
        </div>
      </div>
    </main>

<?php require_once __DIR__ . '/../../includes/layouts/admin_footer.php'; ?>
<?php
// public/hr/settings.php
// Pengaturan Akun Recruiter · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('hr');

$page_title = 'Pengaturan Akun Recruiter · SkillBridge';
$role = 'hr';
$path_prefix = '../';
$rec_page = 'settings';
$body_class = 'rec-body';
$body_attrs = 'data-rec-page="settings"';
$page_script = 'recruiter-settings.js';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/hr_sidebar.php';
?>

    <!-- ===== MAIN ===== -->
    <div class="rec-workspace">
      <?php require_once __DIR__ . '/../../includes/layouts/hr_header.php'; ?>

      <main class="rec-page-content">
        <div>
          <div class="rec-page-context">
            <p class="rec-breadcrumb">A &ndash; RECRUITER / PENGATURAN SISTEM</p>
            <div class="rec-date">
              <svg width="15" height="15" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="14" height="12.5" rx="1.5"/><path d="M3 8.5h14M7 3v3M13 3v3"/></svg>
              <span id="rec-today">18 Oktober 2026</span>
            </div>
          </div>
          <h1 class="rec-page-title">Pengaturan Akun &amp; Profil Perusahaan</h1>
          <p class="rec-page-desc">Perbarui identitas perusahaan dan informasi yang ditampilkan kepada kandidat di SkillBridge.</p>
        </div>

        <div class="rec-tabs" role="tablist" aria-label="Pengaturan">
          <button type="button" class="rec-tab active" data-tab="company" role="tab" aria-selected="true">Profil Perusahaan</button>
          <button type="button" class="rec-tab" data-tab="email" role="tab" aria-selected="false">Notifikasi Email</button>
          <button type="button" class="rec-tab" data-tab="security" role="tab" aria-selected="false">Keamanan &amp; Password</button>
          <button type="button" class="rec-tab" data-tab="api" role="tab" aria-selected="false">Integrasi API</button>
        </div>

        <section id="tab-company" class="rec-tab-panel active" role="tabpanel">
          <div class="rec-card">
            <div class="rec-card-head">
              <div>
                <h2 class="rec-card-title">Profil Perusahaan</h2>
                <p class="rec-card-sub">Informasi resmi PT Tech Indonesia untuk mendukung proses rekrutmen.</p>
              </div>
              <span class="rec-badge-verified">Terverifikasi</span>
            </div>

            <div class="rec-logo-row">
              <div class="rec-logo-preview" aria-hidden="true">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="13" rx="1.5"/><path d="M8 8V5a4 4 0 0 1 8 0v3M9 13h.01M15 13h.01"/></svg>
              </div>
              <div class="rec-logo-controls">
                <span class="rec-logo-label">Logo Perusahaan</span>
                <div class="rec-logo-actions">
                  <button type="button" class="rec-cand-btn" id="btn-upload-logo" aria-label="Unggah logo perusahaan">Unggah Logo</button>
                  <span class="rec-logo-filename" id="company-logo-name">tech-indonesia.svg</span>
                </div>
                <p class="rec-logo-hint">PNG, JPG, atau SVG. Maksimal 2 MB · Disarankan 256 × 256 px.</p>
              </div>
            </div>

            <form id="company-form" class="rec-field-grid">
              <div class="rec-field">
                <label for="company-name">Nama Perusahaan</label>
                <input id="company-name" type="text" value="PT Tech Indonesia" />
              </div>
              <div class="rec-field">
                <label for="company-email">Domain Email Resmi</label>
                <input id="company-email" type="text" value="company.com" />
              </div>
              <div class="rec-field full">
                <label for="company-location">Alamat &amp; Lokasi</label>
                <input id="company-location" type="text" value="Jakarta Selatan, Indonesia" />
              </div>
              <div class="rec-field full">
                <label for="company-description">Deskripsi Perusahaan</label>
                <textarea id="company-description" rows="4">PT Tech Indonesia adalah perusahaan teknologi yang mengembangkan solusi digital untuk membantu bisnis bertumbuh. Berbasis di Jakarta Selatan, kami membangun produk web, platform data, dan pengalaman digital yang berpusat pada pengguna. Kami membuka peluang bagi talenta di bidang engineering, desain, dan analisis data untuk berkolaborasi serta berkembang bersama tim kami.</textarea>
              </div>
            </form>
          </div>
        </section>

        <section id="tab-email" class="rec-tab-panel" role="tabpanel">
          <div class="rec-card">
            <h2 class="rec-card-title">Notifikasi Email</h2>
            <p class="rec-card-sub">Pengaturan notifikasi email akan tersedia pada pembaruan berikutnya.</p>
          </div>
        </section>

        <section id="tab-security" class="rec-tab-panel" role="tabpanel">
          <div class="rec-card">
            <h2 class="rec-card-title">Keamanan &amp; Password</h2>
            <p class="rec-card-sub">Pengaturan keamanan dan password akan tersedia pada pembaruan berikutnya.</p>
          </div>
        </section>

        <section id="tab-api" class="rec-tab-panel" role="tabpanel">
          <div class="rec-card">
            <h2 class="rec-card-title">Integrasi API</h2>
            <p class="rec-card-sub">Pengaturan integrasi API akan tersedia pada pembaruan berikutnya.</p>
          </div>
        </section>

        <div class="rec-save-bar">
          <p class="rec-save-note">Perubahan profil akan ditampilkan pada informasi perusahaan Anda.</p>
          <button type="button" class="rec-cand-btn rec-cand-btn--primary" id="btn-save-settings">Simpan Pengaturan</button>
        </div>
      </main>

<?php require_once __DIR__ . '/../../includes/layouts/hr_footer.php'; ?>
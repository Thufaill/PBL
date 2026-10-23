<?php
// public/mahasiswa/profile.php
// Halaman My Profile · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('mahasiswa');

$page_title = 'My Profile · SkillBridge';
$role = 'mahasiswa';
$path_prefix = '../';
$nav_active = 'profile';
$body_attrs = 'data-nav="profile"';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/mahasiswa_header.php';
?>


    <main class="container">
      <!-- Page Heading -->
      <div class="page-head">
        <div class="page-head-left">
          <p class="eyebrow">Your Account</p>
          <h1 style="font-size: 34px; letter-spacing: -0.34px; line-height: 40px">My Profile</h1>
          <p>Manage your personal information and portfolio details.</p>
        </div>
        <button class="btn btn-accent btn-44" type="button" id="btn-edit-profile">Edit Profile</button>
      </div>

      <!-- Profile Information: 2 columns -->
      <div class="profile-info-grid">
        <!-- Left: Personal Information -->
        <article class="card" style="padding: 24px; display: grid; gap: 20px">
          <h3 style="font-size: 18px; font-weight: 700">Personal Information</h3>
          <div class="profile-student-row">
            <!-- Avatar + identity -->
            <div class="profile-identity">
              <div class="profile-avatar-lg" id="profile-avatar">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <circle cx="12" cy="8" r="5"/>
                  <path d="M20 21a8 8 0 0 0-16 0"/>
                </svg>
              </div>
              <div class="profile-identity-text">
                <p class="profile-name" id="profile-display-name">Jhon Doe</p>
                <p class="profile-role">Student</p>
                <button class="btn btn-sm profile-change-photo" type="button" id="btn-change-photo">Change Photo</button>
              </div>
            </div>
            <!-- Detail fields -->
            <div class="profile-details">
              <div class="profile-detail-row">
                <div class="profile-detail">
                  <p class="profile-detail-label">Full Name</p>
                  <p class="profile-detail-value" id="detail-fullname">Jhon Doe</p>
                </div>
                <div class="profile-detail">
                  <p class="profile-detail-label">Email Address</p>
                  <p class="profile-detail-value" id="detail-email">jhondoe@gmail.com</p>
                </div>
              </div>
              <div class="profile-detail-row">
                <div class="profile-detail">
                  <p class="profile-detail-label">Institution</p>
                  <p class="profile-detail-value" id="detail-institution">Politeknik Negeri Malang</p>
                </div>
                <div class="profile-detail">
                  <p class="profile-detail-label">Study Program</p>
                  <p class="profile-detail-value" id="detail-program">Information Technology</p>
                </div>
              </div>
              <div class="profile-detail-row">
                <div class="profile-detail">
                  <p class="profile-detail-label">Semester</p>
                  <p class="profile-detail-value" id="detail-semester">3</p>
                </div>
                <div class="profile-detail">
                  <p class="profile-detail-label">Location</p>
                  <p class="profile-detail-value" id="detail-location">Malang, Indonesia</p>
                </div>
              </div>
            </div>
          </div>
        </article>

        <!-- Right: Account Details -->
        <div class="profile-sidebar">
          <article class="card" style="padding: 24px; display: grid; gap: 16px">
            <h3 style="font-size: 18px; font-weight: 700">Account Details</h3>
            <div class="account-detail-row">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <path d="m9 12 2 2 4-4"/>
              </svg>
              <div class="account-detail-copy">
                <p class="profile-detail-label">Account Status</p>
                <span class="badge badge-growing" style="font-size: 11px; padding: 5px 10px; opacity: 0.9">Active</span>
              </div>
            </div>
            <div class="account-detail-row">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <path d="M16 2v4M8 2v4M3 10h18"/>
              </svg>
              <div class="account-detail-copy">
                <p class="profile-detail-label">Member Since</p>
                <p class="profile-detail-value" id="detail-member-since">January 2026</p>
              </div>
            </div>
            <div class="account-detail-row">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/>
                <path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
              </svg>
              <div class="account-detail-copy">
                <p class="profile-detail-label">Portfolio Visibility</p>
                <p class="profile-detail-value" id="detail-visibility">Public</p>
              </div>
            </div>
          </article>
        </div>
      </div>

      <!-- Portfolio Overview -->
      <section class="profile-overview">
        <h3 style="font-size: 18px; font-weight: 700">Portfolio Overview</h3>
        <div class="profile-summary-row">
          <div class="profile-summary-card">
            <p class="profile-summary-label">Total Skills</p>
            <p class="profile-summary-value" id="summary-skills">0</p>
          </div>
          <div class="profile-summary-card">
            <p class="profile-summary-label">Total Evidence</p>
            <p class="profile-summary-value" id="summary-evidence">0 items</p>
          </div>
          <div class="profile-summary-card">
            <p class="profile-summary-label">Career Target</p>
            <p class="profile-summary-value" id="summary-career">—</p>
          </div>
        </div>
      </section>

      <!-- Account Settings -->
      <article class="card profile-settings">
        <h3 style="font-size: 18px; font-weight: 700">Account Settings</h3>
        <div class="settings-list">
          <button class="settings-row" type="button" id="btn-edit-personal">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="12" cy="8" r="5"/>
              <path d="M20 21a8 8 0 0 0-16 0"/>
              <path d="m16 16 2 2 4-4"/>
            </svg>
            <div class="settings-row-copy">
              <p class="settings-row-title">Edit Personal Information</p>
              <p class="settings-row-desc">Update your name, institution, and study details.</p>
            </div>
            <svg class="settings-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--text-light)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="m9 18 6-6-6-6"/>
            </svg>
          </button>
          <button class="settings-row settings-row-bordered" type="button" id="btn-change-password">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <rect x="3" y="11" width="18" height="11" rx="2"/>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <div class="settings-row-copy">
              <p class="settings-row-title">Change Password</p>
              <p class="settings-row-desc">Keep your account secure with a new password.</p>
            </div>
            <svg class="settings-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--text-light)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="m9 18 6-6-6-6"/>
            </svg>
          </button>
          <button class="settings-row settings-row-bordered" type="button" id="btn-visibility">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
            <div class="settings-row-copy">
              <p class="settings-row-title">Portfolio Visibility</p>
              <p class="settings-row-desc">Choose who can view your student portfolio.</p>
            </div>
            <svg class="settings-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--text-light)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="m9 18 6-6-6-6"/>
            </svg>
          </button>
        </div>
      </article>
    </main>

    

<?php require_once __DIR__ . '/../../includes/layouts/mahasiswa_footer.php'; ?>

    <script src="<?= e(asset('js/ui.js')) ?>"></script>
    <script src="<?= e(asset('js/api.js')) ?>"></script>
    <script src="<?= e(asset('js/pages/profile.js')) ?>"></script>
  </body>
</html>
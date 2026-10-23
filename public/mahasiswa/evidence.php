<?php
// public/mahasiswa/evidence.php
// Halaman Evidence · SkillBridge
require_once __DIR__ . '/../../includes/auth/auth.php';
check_auth('mahasiswa');

$page_title = 'Evidence · SkillBridge';
$role = 'mahasiswa';
$path_prefix = '../';
$nav_active = 'evidence';
$body_attrs = 'data-nav="evidence"';

require_once __DIR__ . '/../../includes/layouts/head.php';
require_once __DIR__ . '/../../includes/layouts/mahasiswa_header.php';
?>


    <main class="container">
      <div class="page-head">
        <div class="page-head-left">
          <p class="eyebrow">Your career companion</p>
          <h1>Evidence</h1>
          <p style="margin-top: 8px; font-size: 15px; line-height: 1.45; color: var(--text-muted)">
            Keep your strongest work visible, your skills clear, and your next step simple.
          </p>
        </div>
        <button type="button" class="btn btn-accent btn-44" style="gap: 8px" id="btn-add-evidence">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M3.333 8h9.334M8 3.333v9.334"/>
          </svg>
          Add Evidence
        </button>
      </div>

      <!-- Evidence Summary Card -->
      <article class="card-lg" style="display: grid; gap: 24px">
        <div style="display: flex; justify-content: space-between; align-items: center">
          <h3>Evidence summary</h3>
          <p style="font-size: 15px; color: var(--text-muted); font-weight: 600" id="count-label">0 recent items</p>
        </div>

        <!-- Grid: 2x2 evidence cards -->
        <div id="evidence-grid" class="evidence-cards-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px"></div>
      </article>
    </main>

    <!-- Add Evidence Modal -->
    <div id="modal-add-evidence" style="display: none; position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 24px">
      <div style="max-width: 640px; width: 100%; max-height: 90vh; background: #FFFFFF; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); position: relative; overflow: hidden" onclick="event.stopPropagation()">
        <div style="max-height: 90vh; overflow-y: auto; padding: 32px">
        <!-- Close Button -->
        <button type="button" id="btn-close-modal" style="position: absolute; top: 24px; right: 24px; background: none; border: none; cursor: pointer; padding: 4px; opacity: 0.5; transition: opacity 0.2s" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.5'">
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="#64748B" stroke-width="2" stroke-linecap="round">
            <path d="M5 5l10 10M15 5l-10 10"/>
          </svg>
        </button>

        <!-- Header -->
        <div style="margin-bottom: 32px">
          <p style="font-size: 11px; font-weight: 600; letter-spacing: 0.5px; color: #5B6B7C; margin-bottom: 8px">BUKTI KOMPETENSI</p>
          <h2 style="font-size: 24px; font-weight: 700; color: #0F172A; margin-bottom: 8px">Ceritakan yang sudah kamu kerjakan</h2>
          <p style="font-size: 13px; line-height: 1.5; color: #64748B">Gunakan contoh fiktif. Tautan dan lampiran di sini hanya referensi demo; tidak diunggah atau diambil dari layanan lain.</p>
        </div>

        <form id="form-add-evidence" style="display: grid; gap: 24px">
          <!-- Jenis Bukti -->
          <div>
            <label style="display: block; font-size: 14px; font-weight: 600; color: #0F172A; margin-bottom: 12px">Jenis bukti</label>
            <div id="type-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px">
              <button type="button" class="type-option" data-value="cv" style="padding: 14px; border: 2px solid #6366F1; background: #F3F0FF; border-radius: 8px; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 8px; transition: all 0.2s">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="#6366F1" stroke-width="1.5" stroke-linecap="round"><path d="M5 2h10M4 18h12a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1z"/><circle cx="10" cy="7" r="2" fill="#6366F1"/><path d="M7 11h6M7 13.5h4"/></svg>
                <span style="font-size: 13px; font-weight: 600; color: #6366F1">CV</span>
              </button>
              <button type="button" class="type-option" data-value="github" style="padding: 14px; border: 2px solid #E2E8F0; background: #FFFFFF; border-radius: 8px; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 8px; transition: all 0.2s">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="#475569"><path d="M10 0C4.477 0 0 4.477 0 10c0 4.42 2.865 8.166 6.839 9.489.5.092.682-.217.682-.482 0-.237-.008-.866-.013-1.7-2.782.603-3.369-1.342-3.369-1.342-.454-1.155-1.11-1.462-1.11-1.462-.908-.62.069-.608.069-.608 1.003.07 1.531 1.03 1.531 1.03.892 1.529 2.341 1.087 2.91.831.092-.646.35-1.086.636-1.336-2.22-.253-4.555-1.11-4.555-4.943 0-1.091.39-1.984 1.029-2.683-.103-.253-.446-1.27.098-2.647 0 0 .84-.269 2.75 1.025A9.578 9.578 0 0 1 10 4.836c.85.004 1.705.114 2.504.336 1.909-1.294 2.747-1.025 2.747-1.025.546 1.377.203 2.394.1 2.647.64.699 1.028 1.592 1.028 2.683 0 3.842-2.339 4.687-4.566 4.935.359.309.678.919.678 1.852 0 1.336-.012 2.415-.012 2.743 0 .267.18.578.688.48C17.137 18.163 20 14.418 20 10c0-5.523-4.477-10-10-10z"/></svg>
                <span style="font-size: 13px; font-weight: 600; color: #475569">GitHub</span>
              </button>
              <button type="button" class="type-option" data-value="portfolio" style="padding: 14px; border: 2px solid #E2E8F0; background: #FFFFFF; border-radius: 8px; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 8px; transition: all 0.2s">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="#475569" stroke-width="1.5"><circle cx="10" cy="10" r="9"/><path d="M1 11h18M10 10c2 0 3-4 3-9M10 10c-2 0-3-4-3-9"/></svg>
                <span style="font-size: 13px; font-weight: 600; color: #475569">Portofolio</span>
              </button>
              <button type="button" class="type-option" data-value="project" style="padding: 14px; border: 2px solid #E2E8F0; background: #FFFFFF; border-radius: 8px; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 8px; transition: all 0.2s">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="#475569" stroke-width="1.5"><path d="M3 7h14M3 7V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v2M3 7v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7"/></svg>
                <span style="font-size: 13px; font-weight: 600; color: #475569">Proyek</span>
              </button>
              <button type="button" class="type-option" data-value="certificate" style="padding: 14px; border: 2px solid #E2E8F0; background: #FFFFFF; border-radius: 8px; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 8px; transition: all 0.2s">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="#475569" stroke-width="1.5"><circle cx="10" cy="7" r="5"/><path d="M7 14v4l3-2 3 2v-4"/></svg>
                <span style="font-size: 13px; font-weight: 600; color: #475569">Sertifikasi</span>
              </button>
              <button type="button" class="type-option" data-value="assessment" style="padding: 14px; border: 2px solid #E2E8F0; background: #FFFFFF; border-radius: 8px; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 8px; transition: all 0.2s">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="#475569" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 2h8l4 4v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1z"/><path d="M7 10l2 2 4-4"/></svg>
                <span style="font-size: 13px; font-weight: 600; color: #475569">Asesmen</span>
              </button>
            </div>
            <input type="hidden" id="input-type" name="type" value="cv" required>
          </div>

          <!-- AI Demo Banner -->
          <div id="demo-banner" style="padding: 14px 16px; border: 2px dashed #A5B4FC; background: #F8F7FF; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; transition: all 0.2s" onmouseover="this.style.background='#F3F0FF'" onmouseout="this.style.background='#F8F7FF'">
            <div style="display: flex; align-items: center; gap: 10px">
              <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M9 1l2 6h6l-5 4 2 6-5-4-5 4 2-6-5-4h6z" fill="#818CF8"/></svg>
              <span style="font-size: 14px; font-weight: 600; color: #6366F1">Isi dengan contoh CV</span>
            </div>
            <span style="padding: 4px 10px; background: #818CF8; color: white; font-size: 11px; font-weight: 700; border-radius: 6px">Demo</span>
          </div>

          <!-- Judul Bukti -->
          <div>
            <label for="input-title" style="display: block; font-size: 14px; font-weight: 600; color: #0F172A; margin-bottom: 8px">Judul bukti</label>
            <input type="text" id="input-title" name="title" required style="width: 100%; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 8px; font-family: inherit; font-size: 15px; color: #0F172A; background: white" placeholder="Contoh: Proyek dashboard inventaris">
          </div>

          <!-- Nama Lampiran -->
          <div>
            <label for="input-attachment" style="display: block; font-size: 14px; font-weight: 600; color: #0F172A; margin-bottom: 8px">Nama lampiran demo</label>
            <input type="text" id="input-attachment" name="attachment" style="width: 100%; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 8px; font-family: inherit; font-size: 15px; color: #0F172A; background: white" placeholder="Contoh: Dokumen_Demo.pdf">
            <p style="margin-top: 6px; font-size: 12px; color: #64748B">Cukup nama atau referensi fiktif; tidak perlu file asli.</p>
          </div>

          <!-- Ringkasan Kontribusi -->
          <div>
            <label for="input-summary" style="display: block; font-size: 14px; font-weight: 600; color: #0F172A; margin-bottom: 8px">Ringkasan kontribusi</label>
            <textarea id="input-summary" name="summary" rows="4" style="width: 100%; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 8px; font-family: inherit; font-size: 15px; color: #0F172A; background: white; resize: vertical" placeholder="Jelaskan apa yang kamu kerjakan dan hasilnya."></textarea>
          </div>

          <!-- Skills Multi-select -->
          <div>
            <label style="display: block; font-size: 14px; font-weight: 600; color: #0F172A; margin-bottom: 4px">Skill yang didukung <span style="font-weight: 400; color: #64748B">· pilih 1–6</span></label>
            <div id="skills-container" style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px"></div>
            <input type="hidden" id="input-skills" name="skills">
          </div>

          <!-- Level Dropdown -->
          <div>
            <label for="input-level" style="display: block; font-size: 14px; font-weight: 600; color: #0F172A; margin-bottom: 8px">Level yang ditunjukkan bukti</label>
            <select id="input-level" name="level" required style="width: 100%; padding: 10px 14px; border: 1px solid #E2E8F0; border-radius: 8px; font-family: inherit; font-size: 15px; color: #0F172A; background: white; appearance: none; background-image: url('data:image/svg+xml;charset=UTF-8,%3csvg width=%2714%27 height=%278%27 viewBox=%270 0 14 8%27 fill=%27none%27 xmlns=%27http://www.w3.org/2000/svg%27%3e%3cpath d=%27M1 1l6 6 6-6%27 stroke=%27%2364748B%27 stroke-width=%272%27 stroke-linecap=%27round%27/%3e%3c/svg%3e'); background-repeat: no-repeat; background-position: right 14px center">
              <option value="basic">Dasar — memahami konsep</option>
              <option value="intermediate">Menengah — dapat mengaplikasikan</option>
              <option value="advanced">Lanjut — dapat mengajar orang lain</option>
            </select>
            <div style="display: flex; align-items: center; gap: 6px; margin-top: 6px">
              <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6" stroke="#64748B" stroke-width="1.5"/><path d="M7 4v3M7 9.5v.5" stroke="#64748B" stroke-width="1.5" stroke-linecap="round"/></svg>
              <p style="font-size: 12px; color: #64748B">Level ditentukan untuk simulasi. Bukti tidak diverifikasi otomatis.</p>
            </div>
          </div>

          <!-- Footer Actions -->
          <div style="border-top: 1px solid #E2E8F0; padding-top: 24px; display: flex; justify-content: flex-end; gap: 12px">
            <button type="button" id="btn-cancel" style="padding: 10px 24px; border: 1px solid #CBD5E1; background: white; color: #1E293B; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background='white'">Batal</button>
            <button type="submit" style="padding: 10px 24px; border: none; background: #FF007A; color: white; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.2s" onmouseover="this.style.background='#EC4899'" onmouseout="this.style.background='#FF007A'">Simpan bukti demo</button>
          </div>
        </form>
        </div>
      </div>
    </div>

    

<?php require_once __DIR__ . '/../../includes/layouts/mahasiswa_footer.php'; ?>

    <script src="<?= e(asset('js/ui.js')) ?>"></script>
    <script src="<?= e(asset('js/api.js')) ?>"></script>
    <script src="<?= e(asset('js/pages/evidence.js')) ?>"></script>
  </body>
</html>
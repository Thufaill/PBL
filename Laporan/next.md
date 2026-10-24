> **Catatan sinkronisasi 2026-10-24:** Role dan modul HR telah dihapus total dari aplikasi dan database. Fitur pengganti adalah pencarian profil publik (navbar mahasiswa/header admin) dan halaman profil publik berbasis token. Narasi historis di bawah dipertahankan sebagai catatan proses, bukan keadaan kode saat ini.

# Kritik Menyeluruh SkillBridge dan Rencana Perbaikan Bertahap

**Tanggal review:** 2026-10-23
**Commit yang direview:** `17455bb` ("Setup DB"), branch `main`, working tree bersih
**Ruang lingkup:** seluruh repo PHP native + vanilla JS + PostgreSQL (`public/`, `includes/`, `db/`, `docker/`, `tools/`, `docs/`, `Laporan/`)

## 1. Metode dan Batasan

Dokumen ini adalah review statis. Yang benar-benar dikerjakan:

- Membaca kode sumber PHP, JS, CSS, SQL, Docker, dan seluruh dokumen proyek.
- Memverifikasi setiap klaim dengan `grep`/`sed`/`wc` pada baris yang dikutip di dokumen ini. Semua rujukan `file:baris` sudah dicek ulang pada commit `17455bb`; tidak ada satu pun angka baris yang dikira-kira.
- Menghitung sendiri kontras warna dari nilai hex di CSS dengan rumus WCAG relative luminance.
- Memeriksa klaim negatif ("tidak ada X") dengan perintah eksplisit, bukan dengan asumsi. Hasil perintah dicatat di Lampiran.

Yang **tidak** dijalankan, dan karena itu tidak diklaim:

- **not run:** aplikasi tidak di-boot; tidak ada server PHP/Docker yang dijalankan.
- **not run:** tidak ada koneksi ke PostgreSQL/Supabase; tidak ada satu pun query yang dieksekusi.
- **not run:** tidak ada browser, tidak ada pengujian rendering, tidak ada screenshot.
- **not run:** tidak ada automated detector, axe, maupun Lighthouse. Skor di bawah adalah penilaian reviewer terhadap sumber, **bukan** skor mesin.
- **not run:** tidak ada tes otomatis karena memang tidak ada file tes di repo.
- **not run:** `grill-me` tidak dapat dipakai. Berkasnya di `~/.agents/skills/grill-me/SKILL.md` hanya menyuruh memanggil skill `grilling`, dan skill itu tidak terpasang. Gantinya, Bagian 8 berisi set pertanyaan adversarial yang dijalankan langsung terhadap kode, dengan jawaban dan vonis lulus/gagal.
- Kerangka `impeccable` (`reference/critique.md`, `reference/audit.md`) dipakai hanya sebagai struktur pertanyaan review UI/UX dan audit teknis. Segmen yang butuh browser atau detektor sengaja dilewati, bukan dilaporkan sebagai lulus.
- `site-audit` tidak dipakai karena skill itu dirancang untuk static site, sedangkan proyek ini PHP dinamis dengan database.
- Satu audit sebelumnya sudah ada di repo: `Laporan/PEMETAAN_PROYEK.md`, dengan tabel kesenjangan G1 sampai G15. Dokumen ini memakai ulang ID itu bila temuannya masih valid, menambahkan yang belum tercatat, dan menandai yang sudah basi. Pemetaan lengkapnya ada di Bagian 12.3, jadi tidak ada kerja ganda dan tidak ada klaim penemuan palsu.

Konsekuensi batasan ini: temuan kelas "aplikasi error saat diklik" dibuktikan dari pembacaan kode, bukan dari runtime. Untuk temuan semacam ini, bagian Perbaikan selalu menyertakan cara mereproduksi yang bisa dijalankan tim dalam beberapa menit.

## 2. Vonis Eksekutif

SkillBridge hari ini adalah **prototipe UI yang meyakinkan dengan satu modul asli di belakangnya**, yaitu autentikasi. Registrasi dan login benar-benar menulis dan membaca PostgreSQL. Setelah user melewati pintu itu, seluruh sisanya — keterampilan, evidence, target karier, skill gap, profil, admin, HR — berjalan di `localStorage` browser dan file JSON statis di `public/data/`. Tidak ada folder `public/api/`. Artinya produk ini bukan aplikasi multi-user; ia adalah demo satu-browser yang tidak menyimpan apa pun di server.

Karena itu, laporan akademik yang menyatakan fitur telah diimplementasikan tidak didukung kode. `Laporan/LaporanPBL.md` baris 1 masih berjudul "REVISI PROPOSAL", Bab 8 dan Bab 9 kosong (baris 339 dan 341), dan Kriteria Keberhasilan nomor 2 sampai 5 (baris 171-175) menuntut fitur CV yang tidak ada dalam bentuk apa pun: tidak ada halaman, tidak ada `$_FILES`, tidak ada `move_uploaded_file` di seluruh repo.

Perlu dibedakan dua jenis blocker, karena keduanya menuntut respons berbeda.

**Demo blocker** — kegagalan yang muncul saat dosen mengetuk antarmuka:

1. Menyimpan evidence selalu gagal dengan `TypeError` karena nama fungsi tidak cocok.
2. Navbar mahasiswa disuntikkan ke seluruh 8 halaman admin, merusak tata letak admin.
3. Pencarian di landing page dan tautan "Read More" tidak melakukan apa pun.
4. Dashboard admin menampilkan angka yang ditulis keras di kode, bukan dari data.

**Production blocker** — kegagalan yang tidak muncul di layar tetapi menghapus data atau membocorkan informasi:

1. Semua data domain milik pengguna hilang saat `localStorage` dibersihkan atau saat pindah browser, dan dua akun di satu browser saling mewarisi data.
2. Empat file JSON berisi nama dan email dapat diambil tanpa login apa pun.
3. `APP_DEMO=1` memberi akses penuh ke role apa pun dan menerima `password123`, dan halaman login menampilkan tombol pintas untuk itu.
4. Skema database bersifat destruktif dan tidak punya jalur migrasi.

Lima temuan teratas, berurutan dari yang paling merusak: **P0-2** (tidak ada persistensi domain di server, akar dari hampir semua masalah lain), **P0-7** (fitur inti proposal tidak ada, sehingga klaim akademik tidak dapat dipertahankan), **P0-8** (laporan masih proposal), **P0-1** (fitur yang dipamerkan rusak total), **P0-5** (PII mock terbuka tanpa autentikasi).

Vonis singkat: proyek ini punya fondasi autentikasi yang layak dan desain UI yang rapi, tetapi klaim "aplikasi web berbasis PHP dan PostgreSQL" baru terpenuhi sekitar seperempat. Sisa kerjanya jelas dan terukur, bukan misterius — Roadmap di Bagian 10 menaksir 17 sampai 26 hari-orang.

## 3. Scorecard

Skor 1 sampai 5. 5 = siap produksi, 3 = berfungsi dengan celah yang diketahui, 1 = tidak ada.

| Dimensi | Skor | Alasan dan bukti |
|---|---|---|
| Autentikasi dan sesi | 4 | Prepared statement, `password_verify` dengan dummy hash untuk timing, `session_regenerate_id(true)`, cookie hardened. `includes/auth/auth.php:125-137`, `includes/auth/session.php:10-19`. Kurang: `secure` dari `X-Forwarded-Proto` yang bisa dipalsukan (baris 8), logout lewat GET tanpa token. |
| Persistensi domain | 1 | Tidak ada `public/api/`. Semua tulis-baca domain lewat `localStorage` dan JSON. `public/js/api.js:8-13`, `137-142`, `236-242`. |
| Kelengkapan fitur vs proposal | 2 | Alur mahasiswa ada secara visual; CV tidak ada sama sekali (`public/mahasiswa/` berisi 6 file, tanpa `cv.php`); CRUD admin dummy; CRUD evidence error. |
| Keamanan | 2 | CSRF hanya di login dan register (`grep csrf_verify` menghasilkan 3 baris termasuk definisi); tanpa rate limit, tanpa security header (keduanya 0 hasil), enumerasi email di `public/register.php:68`, PII publik di `public/data/`, `APP_DEMO` bypass di `includes/auth/auth.php:66-88`. |
| Kualitas kode frontend | 3 | Helper rapi (`UI.el`, `safeUrl`, `modal` dengan focus trap), satu gateway data. Kurang: `innerHTML` escape hatch `public/js/ui.js:28`, `toast` mengabaikan argumen error (baris 52-56), handler duplikat `public/js/pages/admin-users.js:92` dan `102`. |
| UX dan aksesibilitas | 2 | Modal evidence tanpa `role="dialog"` (`public/mahasiswa/evidence.php:48-56`), sidebar tanpa `aria-expanded` (`public/js/ui.js:194-201`), kontras tombol aksen 3.53:1 (`public/css/style.css:316-320`), `lang="id"` dengan copy Inggris (`includes/layouts/head.php:15`). |
| Performa | 2 | 6.090 baris CSS tanpa minifikasi dan tanpa cache busting, fetch berulang (`public/js/api.js:115`, `44`), font blocking (`includes/layouts/head.php:23-26`). |
| Dokumentasi dan laporan | 2 | `README.md` UTF-16LE dan instruksinya merujuk file yang sudah pindah; `docs/PRD.md:90-101` mendeskripsikan skema yang tidak ada; `Laporan/LaporanPBL.md` masih proposal. `Laporan/PEMETAAN_PROYEK.md` justru bagus tapi basi. |
| Deployment | 3 | Docker memisahkan docroot ke `public/`, `.env` di-ignore. Kurang: proses root, tanpa healthcheck, `PORT` tak tervalidasi masuk `sed` (`docker/entrypoint.sh:5-6`). |

Total 21 dari 45. Profil nilainya jelas: satu modul solid, tujuh modul tertinggal.

## 4. P0 — Blocker

Delapan temuan ini harus selesai sebelum proyek layak didemokan dan sebelum laporan menyebut fitur apa pun sebagai selesai.

### P0-1 — Menyimpan evidence selalu gagal

**Gejala.** Pengguna mengisi form "Tambah Bukti", menekan Simpan, dan selalu melihat toast error.

**Bukti.** `public/js/pages/evidence.js:171` memanggil `await API.createEvidence(payload);`. Gateway data tidak punya fungsi itu; ia mengekspos `addEvidence` (`public/js/api.js:98` definisi, `public/js/api.js:367` ekspor). `createEvidence` bernilai `undefined`, sehingga pemanggilan melempar `TypeError: API.createEvidence is not a function`, ditangkap blok `catch` di baris 175.

**Dampak.** Fitur inti nomor 8 Kriteria Keberhasilan (`Laporan/LaporanPBL.md:183`) tidak dapat didemokan sama sekali, dan ini adalah layar yang paling mungkin diketuk dosen karena tombolnya dibuat menonjol di dashboard dan di navbar. Bug juga tidak menyingkap dirinya saat review kode biasa, karena namanya masuk akal.

**Klasifikasi.** Demo blocker, sekaligus production blocker setelah P0-2 selesai.

**Cakupan penuh kelas bug ini.** Audit kontrak dilakukan menyeluruh, bukan pada satu baris saja: dari 31 fungsi yang diekspor gateway (`public/js/api.js`), **satu-satunya** panggilan `API.x` di seluruh `public/js/pages/*.js` dan `public/js/*.js` yang tidak punya ekspor adalah `createEvidence`. Jadi tidak ada bug sejenis yang tersembunyi di halaman lain. Arah sebaliknya justru perlu dibersihkan: lima fungsi diekspor dan tidak pernah dipanggil siapa pun, yaitu `addEvidence`, `csrfToken`, `getAdminContent`, `removeEvidence`, `removeSkill`. Dari lima itu, `addEvidence` adalah fungsi yang seharusnya dipanggil `evidence.js`; empat lainnya adalah jalur yang benar-benar menganggur dan dibutuhkan setelah Fase 1, yaitu CSRF (`csrfToken`), konten admin (`getAdminContent`), dan penghapusan skill serta evidence (`removeSkill`, `removeEvidence`).

**Perbaikan.** Samakan kontrak: ubah pemanggil di `evidence.js:171` menjadi `API.addEvidence(payload)`, atau tambahkan alias `createEvidence` di ekspor `api.js`. Pilih satu arah penamaan untuk seluruh gateway, lalu sapukan ke semua pemanggil. Tambahkan satu pemeriksaan statis sederhana (daftar nama fungsi yang diekspor dibandingkan nama yang dipanggil `API.` di `public/js/pages/`) sebagai langkah CI, supaya kelas bug ini tidak berulang.

**Estimasi.** 15 menit untuk perbaikan, 1 sampai 2 jam untuk pemeriksaan kontrak dan sapuan seluruh pemanggil.

**Kriteria selesai.** Submit form evidence di `mahasiswa/evidence.php` menghasilkan item baru di daftar; tidak ada `TypeError` di konsol; pemeriksaan kontrak berjalan hijau di CI.

### P0-2 — Tidak ada persistensi domain di server

**Gejala.** Aplikasi tampak menyimpan data, tetapi server tidak pernah menulis apa pun selain akun.

**Bukti.** Yang menyentuh database: `public/register.php:40-54` (INSERT `users` dan `student_profiles`), `includes/auth/auth.php:125-129` (SELECT saat login), `tools/create_admin.php`. Selebihnya berada di satu gateway klien yang menulis ke `localStorage` (`public/js/api.js:8-13` untuk kunci mahasiswa, `137-142` admin, `236-242` recruiter) dan membaca JSON statis (`public/js/api.js:26`, `32`, `166`). `ls public/api` menghasilkan "No such file or directory". `README.md` baris 57-58 (setelah didecode dari UTF-16) mengakui datanya berasal dari JSON plus `localStorage` dan `public/api/*.php` belum dibuat. Skema `db/schema.sql` sudah menyiapkan tabel `user_skills`, `evidence`, `evidence_skills`, `student_profiles`, `career_skill_requirements` dan view `v_skill_gap` (baris 101-161) yang tidak pernah dibaca kode PHP.

**Dampak.** Ini akar masalah. Selama domain tidak punya endpoint: data user hilang saat browser ganti atau cache dibersihkan; dua akun di satu browser saling menimpa; tidak ada dasar pengujian fungsional yang sah; laporan tidak bisa mengklaim fitur berjalan; dan seluruh temuan keamanan domain (otorisasi per baris, CSRF tulis, validasi server) tidak punya tempat untuk dipasang.

**Klasifikasi.** Production blocker, dan tanpa ini hasil kerja tim tidak dapat dinilai secara jujur.

**Perbaikan.** Bangun modul API kecil yang cocok dengan kontrak yang sudah ditulis di komentar `api.js` (`api/skills.php`, `api/evidence.php`, `api/target.php`, `api/gap.php`, `api/profile.php`), masing-masing memanggil `check_auth('mahasiswa')`, memverifikasi CSRF, dan memakai prepared statement dengan `user_id` dari sesi — bukan dari body request. Pindahkan `api.js` dari JSON ke endpoint ini dengan tetap mempertahankan bentuk data yang sekarang, sehingga halaman tidak perlu ditulis ulang. Mulai dari `user_skills` dan `evidence` karena keduanya dipakai tiga halaman.

**Estimasi.** 5 sampai 8 hari-orang untuk modul mahasiswa lengkap (lihat Fase 1).

**Kriteria selesai.** Login dengan akun A di browser 1 dan akun A di browser 2 menunjukkan daftar skill, evidence, dan target karier yang identik; menghapus `localStorage` tidak menghilangkan data apa pun; `SELECT` langsung di DB menunjukkan baris yang sama dengan yang ditampilkan UI.

### P0-3 — Data lokal bersifat global per browser dan tidak dibersihkan saat logout

**Gejala.** Akun kedua yang login di browser yang sama melihat, dan ikut menulis, data akun pertama.

**Bukti.** Kunci `localStorage` tidak mengandung identitas user: `sb.mySkills`, `sb.myEvidence`, `sb.targetCareer`, `sb.customSkills` (`public/js/api.js:8-13`), `sb.admin.*` (`137-142`), `sb.rec.*` (`236-242`). `public/logout.php:1-7` hanya memanggil `logout()` dan me-reset halaman login; tidak ada satu pun baris di repo yang memanggil `localStorage.removeItem` atau `.clear()`.

**Dampak.** Kebocoran data antar-akun di perangkat bersama, misalnya lab kampus atau laptop dosen saat uji coba. Selain itu state admin dan recruiter ikut menempel, sehingga setelah mencoba role admin, akun mahasiswa berikutnya bisa menampilkan angka dan konten admin.

**Klasifikasi.** Production blocker; untuk demo, ini blocker begitu reviewer membuka dua akun seperti yang biasa dilakukan dosen penguji.

**Perbaikan.** Dua langkah. Interim (hari ini): beri namespace kunci dengan id user, misalnya `sb.u{ID}.mySkills`, dan bersihkan kunci aktif saat halaman logout berjalan; simpan id user di `window` dari PHP agar klien tahu namespace yang benar. Final: hilangkan penyimpanan domain di klien sepenuhnya lewat P0-2, dan jadikan `localStorage` hanya untuk preferensi UI non-sensitif seperti tema.

**Estimasi.** Interim 2 sampai 3 jam. Final tergabung di Fase 1.

**Kriteria selesai.** Dua akun dibuka bergantian di satu browser tanpa saling menampilkan skill, evidence, atau target karier; logout menghapus jejak domain di klien; uji manual dua akun didokumentasikan di log pengujian.

### P0-4 — Navbar mahasiswa disuntikkan ke halaman admin

**Gejala.** Semua halaman admin menampilkan header navigasi portal mahasiswa ("Jhon Doe", pill Dashboard/My Skills/Evidence) di atas sidebar admin, dan nama pengguna yang salah.

**Bukti.** Delapan halaman admin menulis `data-nav` pada body, contoh `public/admin/users.php:12`: `$body_attrs = 'data-admin-page="users" data-nav="users"';`. `public/js/ui.js:171` men-trigger injeksi dengan `if (document.body.dataset.nav !== undefined) renderNavbar();`. Penjaga satu-satunya di `renderNavbar()` adalah `if (document.querySelector("header.navbar")) return;` (`public/js/ui.js:124`), sedangkan header admin memakai class berbeda (`includes/layouts/admin_header.php:10` memakai `class="admin-header"`, `includes/layouts/hr_header.php:9` memakai `class="rec-header"`). Label navbar juga ditulis keras: `public/js/ui.js:152-153` menampilkan "Jhon Doe" dan `jhondoe@gmail.com`.

**Dampak.** Antarmuka admin tampak rusak dan tidak profesional di layar pertama yang dilihat reviewer, identitas di dalamnya bohong, dan ada dua sistem navigasi bersaing di satu halaman. Ini juga menunjukkan pemicu injeksi berdasarkan atribut, bukan berdasarkan peran atau layout, sehingga bug sejenis akan muncul lagi di halaman baru.

**Klasifikasi.** Demo blocker.

**Perbaikan.** Pindahkan keputusan ke server: halaman admin dan HR tidak perlu menerima `data-nav` sama sekali, atau `renderNavbar()` hanya jalan bila body punya penanda layout publik/mahasiswa, misalnya `data-layout="mahasiswa"`, yang diset layout terkait. Hapus total nama dan email yang ditulis keras dan baca dari `$user` sesi seperti yang sudah benar dilakukan `includes/layouts/mahasiswa_header.php:47`.

**Estimasi.** 1 sampai 2 jam.

**Kriteria selesai.** Tidak ada `header.navbar` di 8 halaman admin dan 6 halaman HR; navbar mahasiswa tetap muncul di 6 halaman mahasiswa; nama di header berasal dari sesi.

### P0-5 — Data pseudo-PII dapat diambil tanpa login

**Gejala.** URL JSON di `public/data/` terbuka dari browser mana pun tanpa sesi.

**Bukti.** Docroot diarahkan ke `public/` (`Dockerfile:13`). Tidak ada `public/.htaccess` (`ls public/.htaccess` gagal). `AllowOverride None` di `Dockerfile:16` membuat `.htaccess` di root repo tidak berlaku di dalam container, dan aturannya (`RewriteRule ^includes|db|tools|docker|docs`) hanya menolong bila docroot adalah root repo. Konten yang terlayani: `public/data/admin-users.json` memuat 10 pasangan nama dan email, `public/data/recruiter-candidates.json` 4, `public/data/admin-jobs.json` 4. Perlu dicatat `recruiter-candidates.json` memuat `geraldi@mail.com`, yang identik dengan salah satu nama anggota tim — mock data yang menyerupai identitas nyata justru lebih buruk, karena mudah disalahpahami sebagai data sungguhan dan tetap tercatat sebagai PII.

**Dampak.** Reviewer atau siapa pun yang mengetahui URL bisa mengambil daftar nama-email tanpa autentikasi. Untuk laporan yang membahas privasi dan basis data, ini temuan yang memalukan; secara nyata juga membuat "hanya admin yang bisa melihat daftar pengguna" menjadi klaim palsu.

**Klasifikasi.** Production blocker sekaligus risiko akademik.

**Perbaikan.** Keluarkan file seed dari docroot ke `db/seed/` atau `tools/fixtures/`, lalu sajikan lewat endpoint berautorisasi, atau cukup hapus field email dan ganti dengan `user{id}@contoh.test`. Untuk transisi cepat sebelum Fase 1: hapus `email` dari JSON, dan tambahkan `.htaccess` di `public/data` yang menolak akses langsung (atau matikan serving file statis itu lewat konfigurasi Apache yang tidak bergantung `AllowOverride`).

**Estimasi.** 1 sampai 2 jam.

**Kriteria selesai.** `curl` tanpa sesi ke keempat URL mengembalikan 403/404; tidak ada nilai email nyata atau menyerupai nyata di repo publik; hasil curl direkam di log pengujian.

### P0-6 — Mode demo memberi akses penuh ke role apa pun

**Gejala.** Satu variabel lingkungan mengubah aplikasi menjadi tanpa-auth.

**Bukti.** `includes/auth/auth.php:66-88`: ketika `demo_mode()` benar (baris 15-17 mendeteksinya dari `APP_DEMO=1`), `check_auth()` membuat sesi demo untuk role apa pun yang diminta dan bahkan mengganti sesi yang sudah ada bila peran berbeda (baris 72-73), lalu `return true` tanpa validasi. `attempt_login()` juga menerima empat email demo dengan password tetap `password123` (baris 157-168). `public/login.php:78-94` merender panel "Akses Cepat Demo Role" berisi tombol yang mengisi form dengan kredensial itu. `.env` saat ini berisi `APP_DEMO=0` (aman), jadi risikonya murni salah konfigurasi saat deploy atau saat penilaian.

**Dampak.** Bila flag nyala di server yang dinilai, seluruh kontrol akses lenyap dan siapa pun masuk sebagai admin. Tombol demo yang terlihat di halaman login juga membuat reviewer menyimpulkan fitur login tidak benar-benar diuji.

**Klasifikasi.** Production blocker; untuk demo, ini blocker kepercayaan.

**Perbaikan.** Pertahankan `APP_DEMO=0` sebagai satu-satunya nilai yang dikenal untuk build yang dinilai. Fail-closed: `demo_mode()` hanya boleh aktif bila flag bernilai `1` **dan** request berasal dari `127.0.0.1`/`::1` dan `PHP_SAPI` bukan CLI produksi, atau cukup batasi lewat `.env.example` plus pemeriksaan di `check_auth()` yang menolak flag saat `APP_ENV=production`. Tambahkan satu baris peringatan di README dan satu langkah di CI yang gagal bila `APP_DEMO=1` ikut terbawa ke artefak rilis.

**Estimasi.** 2 sampai 4 jam.

**Kriteria selesai.** Dengan `APP_DEMO=1`, akses dari alamat non-lokal tetap menuntut login nyata; panel demo tidak dirender pada build penilaian; grep di artefak rilis memastikan flag mati.

### P0-7 — Fitur CV tidak ada, padahal itu inti proposal

**Gejala.** Tidak ada satu pun layar unggah CV, ekstraksi kata kunci, atau review/konfirmasi.

**Bukti.** `ls public/mahasiswa` menghasilkan `career.php`, `dashboard.php`, `evidence.php`, `gap.php`, `profile.php`, `skills.php` — tidak ada `cv.php` maupun `cv_review.php` yang diminta `docs/PRD.md:68`. `grep -rn '\$_FILES|move_uploaded_file' --include='*.php'` di seluruh repo menghasilkan 0. `Laporan/LaporanPBL.md:171-175` adalah Kriteria Keberhasilan 2 sampai 5 yang menuntut unggah CV, identifikasi keterampilan, dan review-konfirmasi; Ringkasan Eksekutif dan Latar Belakang proposal seluruhnya dibangun di atas gagasan CV sebagai sumber awal.

**Dampak.** Fitur pembeda produk hilang. Yang tersisa adalah CRUD skill manual yang bisa dilakukan siapa pun dengan formulir biasa, sehingga tesis proyek tidak berdiri. Secara akademik, ini beda antara "melaksanakan proposal" dan "membuat aplikasi lain lalu menuliskan proposal yang lama".

**Klasifikasi.** Demo blocker dan akademik blocker.

**Perbaikan.** Implementasi minimal yang sesuai ruang lingkup, tanpa AI: (1) `mahasiswa/cv.php` — form unggah dengan `enctype="multipart/form-data"`, token CSRF tersembunyi, validasi tipe (PDF dan TXT), batas ukuran, simpan di luar docroot dengan nama acak; (2) untuk PDF, ekstrak teks dengan pustaka yang diizinkan aturan proyek, dan bila itu terlalu berat, terima TXT sebagai jalur wajib sementara PDF diberi pesan jelas; (3) pencocokan kata kunci terhadap master `skills` dengan daftar sinonim; (4) `mahasiswa/cv_review.php` — tampilkan kandidat, izinkan tambah, koreksi, hapus, lalu konfirmasi simpan ke `user_skills` dengan `source='cv_extracted'` dan `status='confirmed'`. Skema `db/schema.sql:117-126` sudah punya kolom `source` dan `status` persis untuk alur ini; hanya belum ada kodenya.

**Estimasi.** 5 sampai 7 hari-orang (Fase 2). Alternatif descope resmi memakan 1 hari tapi mengubah klaim proposal dan perlu persetujuan dosen.

**Kriteria selesai.** Upload CV nyata menghasilkan daftar keterampilan kandidat; konfirmasi menyimpan baris ke `user_skills` dengan `source='cv_extracted'`; file unggahan tersimpan di luar docroot dan tidak dapat diakses via URL; Kriteria Keberhasilan 2 sampai 5 dapat didemokan dan difoto.

### P0-8 — Laporan masih proposal dan tidak punya bukti implementasi

**Gejala.** Dokumen penilaian utama tidak menggambarkan produk yang dibuat.

**Bukti.** `Laporan/LaporanPBL.md:1` masih "REVISI PROPOSAL" dan judul baris 3 memakai nama lama "Skill Bridge" sementara produk bernama SkillBridge. Bab 8 "Rencana Mitra Industri (Opsional)" (baris 339) dan Bab 9 "Lampiran" (baris 341) hanya berisi heading tanpa isi. Tidak ada bab Implementasi, tidak ada bab Pengujian, tidak ada Metodologi, tidak ada Kesimpulan. Isi 430 KB file itu didominasi dua gambar base64; teks nyata sekitar 29 KB. `Laporan/Notion.md:93-100` masih mencatat Roadmap fase Define, Design, Build, Test, Present sebagai "Belum mulai".

**Dampak.** Dosen tidak punya jejak untuk menilai pekerjaan. Lebih serius, dokumen itu bisa dibaca sebagai klaim yang tidak didukung kode: kriteria keberhasilan 2 sampai 9 ditulis sebagai kemampuan sistem, sedangkan P0-2 dan P0-7 membuktikan sebaliknya.

**Klasifikasi.** Akademik blocker.

**Perbaikan.** Ubah judul menjadi "Laporan Implementasi dan Pengujian". Isi Bab Implementasi dengan arsitektur nyata (layout per peran, modul auth, modul API), Bab Pengujian dengan tabel kasus uji dan hasil (lihat Bagian 11), dan Bab Kesimpulan dengan keterbatasan yang jujur. Setiap kriteria keberhasilan diberi baris status: terlaksana, terlaksana sebagian, atau tidak terlaksana beserta alasannya.

**Estimasi.** 3 sampai 4 hari-orang, dan tidak bisa dikerjakan sebelum Fase 1 dan 2 karena isinya adalah hasil.

**Kriteria selesai.** Setiap klaim fungsional di laporan menunjuk satu endpoint atau halaman nyata plus satu kasus uji; Bab 8, 9, dan bab baru terisi; status 10 kriteria keberhasilan ditulis apa adanya.

## 5. P1 — Major

Temuan ini tidak menjatuhkan demo pada klik pertama, tetapi membuat klaim produk tidak dapat dipertahankan dan menyimpan kegagalan yang akan muncul saat pengguna nyata.

| ID | Temuan | Bukti | Perbaikan |
|---|---|---|---|
| P1-1 | CRUD admin dan HR palsu, metrik ditulis keras. `admin.js` memanggil `API.getAdminStats()` lalu membuang hasilnya; kartu menampilkan 1248 pengguna, 12 admin, 48 skill, 8 karier, 64 relasi. Aktivitas admin direkayasa. Tombol admin hanya memunculkan toast "(dummy)". | `public/js/pages/admin.js:9` dan `13-19`; `public/js/pages/admin-careers.js:53` dan `84`; `public/js/pages/admin-mapping.js:65` dan `92`; `public/js/pages/admin-skills.js:59` dan `98`; `public/js/pages/admin-users.js:79-126`; `public/js/pages/recruiter.js:46` nilai `"15"`. | Endpoint admin nyata di Fase 2. Sebelum itu, tempel label "DATA DEMO" terlihat di setiap kartu dan hapus klaim "mengelola data utama" dari laporan. Jangan pernah memakai angka hardcoded di layar yang diklaim sebagai statistik. |
| P1-2 | Hitungan evidence per skill selalu nol, dan `evidence.json` diabaikan. `seedEvidence()` menulis array kosong alih-alih memuat seed, sementara payload form menyimpan skill di `metadata.skills` tetapi penghitung membaca `ev.skill_ids`. | `public/js/api.js:336-339`; `public/js/api.js:341-348`; `public/js/pages/evidence.js:158-167`; bandingkan `db/schema.sql:23` yang sudah punya tabel relasi `evidence_skills`. | Tetapkan satu kontrak `skill_ids` di gateway dan di payload; muat seed sekali saat kunci kosong; gunakan `evidence_skills` saat backend jadi. Label Strong dan Growing di `skills.php` saat ini tidak pernah benar. |
| P1-3 | Skema destruktif tanpa migrasi, dan PRD merujuk skema lain. `schema.sql` dibuka dengan `DROP TABLE ... CASCADE` dan `DROP TYPE ... CASCADE`, tidak ada folder migrasi, dan bentuk enum berbeda dengan PRD. | `db/schema.sql:21-26`; `db/schema.sql:29-31` enum `evidence_source_type` berisi `cv, project, portfolio, certificate`; `docs/PRD.md:90-101` menyebut `db_pbl.sql`, `users.target_career_id`, `career_skill_requirements.required_level`, `user_skills.skill_confidence`, `assessment_questions`, `companies`, `company_recruiters` — tidak satu pun ada di skema. | Pecah menjadi `schema.sql` (DDL idempotent tanpa DROP) plus `migrations/NNN_*.sql` dan `seed.sql` terpisah; jalankan `schema.sql` dua kali untuk membuktikan aman. Rekonsiliasi `docs/PRD.md` dengan skema nyata sebelum laporan ditulis. |
| P1-4 | Proteksi permintaan baru ada di dua tempat. Logout adalah GET tanpa token, tidak ada rate limit, tidak ada security header, dan register membuka enumerasi email. | `grep csrf_verify` hanya 3 baris termasuk definisi (`includes/auth/csrf.php:32`, `public/login.php:30`, `public/register.php:20`); `public/logout.php:5-6`; `public/register.php:67-68` pesan "Email ini sudah terdaftar. Silakan login."; grep `X-Frame-Options`, `Content-Security-Policy`, `Strict-Transport-Security`, dan `rate_limit` menghasilkan 0. | Guard CSRF di semua endpoint tulis (otomatis setelah P0-2), logout jadi POST dengan token atau tombol keluar di menu, pesan generik "email atau kata sandi salah" saat `23505`, `sleep`/`session`-based throttle login, dan header dasar di `head.php` atau konfigurasi Apache. |
| P1-5 | Kegagalan jaringan dan data tidak pernah ditangani; pengguna dibiarkan melihat halaman kosong. | `public/js/api.js:26`, `32`, `44`, `166`, `307`, `319` memakai `fetch` tanpa pemeriksaan `res.ok`; skrip halaman memakai await tingkat tinggi di IIFE: `public/js/pages/evidence.js:5`, `public/js/pages/gap.js:22`, `public/js/pages/skills.js:32`; `dashboard.js:5-8` memakai `Promise.all` tanpa `catch`. | Satukan satu `request()` di gateway yang memeriksa `res.ok` dan melempar error typed; tambahkan state loading, kosong, dan gagal di setiap daftar; bungkus IIFE halaman dengan `catch` yang memanggil `UI.toast` dan merender pesan. |
| P1-6 | Lubang aksesibilitas pada jalur utama. Modal evidence adalah `div` tanpa `role="dialog"`, `aria-modal`, atau label, padahal `ui.js` sudah punya `modal()` dengan focus trap dan halaman ini tidak memakainya; toggle sidebar tidak menyentuh `aria-expanded`; label kontras gagal AA; bahasa dokumen bertentangan dengan bahasa isi. | `public/mahasiswa/evidence.php:48-56` dan `104-146` buka-tutup lewat `style.display`; `public/js/ui.js:194-201` sidebar; kontras `.btn-accent` putih di atas `#ec4899` = 3.53:1 terhadap ambang 4.5:1 (`public/css/style.css:316-320`, dihitung dari nilai hex dengan rumus luminansi WCAG); `includes/layouts/head.php:15` `lang="id"` sementara judul dan copy Inggris (`public/login.php:69-70`). | Pakai `UI.modal()` untuk evidence, `inert` atau `aria-hidden` pada latar, set `aria-expanded` di toggle sidebar, darken token aksen sampai AA, dan pilih satu bahasa secara konsisten di seluruh UI plus `lang` yang cocok. |
| P1-7 | README tidak dapat dipakai, dan tidak ada tes atau CI sama sekali. | `file README.md` = "Unicode text, UTF-16, little-endian text"; instruksinya masih menunjuk `/public/dashboard.html` yang tidak ada; `ls -d tests composer.json phpunit.xml .github` semuanya "No such file or directory". | Konversi ke UTF-8, tulis ulang langkah setup dari nol (env, DB, import skema, `tools/create_admin.php`, Docker), lalu tambah satu suite smoke: cek syntax `php -l`, cek kontrak ekspor API, dan endpoint auth. Pasang CI GitHub Actions minimal yang menjalankan ketiganya. |
| P1-8 | Scope dan peran bertentangan antar dokumen. Proposal dan `docs/PRD.md` memuat peran HR, tetapi tidak ada cara membuat akun HR, tidak ada tabel perusahaan atau lowongan, dan register selalu menyisipkan peran `user`. | `db/schema.sql:29` enum punya `'hr'`; `public/register.php:48` `'role' => app_role_to_db_role('mahasiswa')`; `grep` hanya menemukan `'hr'` di `public/hr/*.php:5` dan `:8`; `tools/create_admin.php` hanya membuat admin; tidak ada tabel `companies`, `vacancies`, `applications` di skema; `Laporan/LaporanPBL.md` menyatakan rekrutmen di luar ruang lingkup. | Putuskan (lihat Bagian 9): kunci dua peran untuk submission, arsipkan `public/hr/` dan `public/js/pages/recruiter*.js` sebagai eksperimen dengan README yang mengatakan begitu, dan hapus klaim HR dari PRD, Notion, dan laporan. |

## 6. P2 — Minor dan Polish

Tidak memblokir penilaian, tetapi akan terlihat saat pengujian dan memakan waktu bila dibiarkan menumpuk.

| ID | Temuan | Bukti | Perbaikan |
|---|---|---|---|
| P2-1 | Tautan mati di landing dan di kartu evidence. Pencarian mengarah ke hash yang tidak ada konsumennya; "Read More" menunjuk anchor `#insights` tanpa halaman artikel; tombol "Open" memakai `|| "#"` ketika `file_url` tidak valid. | `public/js/landing.js:17` menuju `mahasiswa/skills.php#q=...`, sedangkan grep `location.hash` di `public/js` hanya menemukannya di `recruiter-team.js:241`; `public/index.php:689`, `735`, `766`; `public/js/pages/evidence.js:222`; `public/js/pages/gap.js:79` tombol beraksen memanggil `UI.toast("Feature coming soon")`. | Implementasikan pencarian nyata di `skills.php` (baca `q` dari hash atau jadikan query string dan render hasil di server), atau hilangkan form pencarian. Buat halaman artikel atau hapus tombol "Read More". Sembunyikan tombol "Open" bila URL tidak lolos `safeUrl()`. |
| P2-2 | Berat dan berulang tanpa perlu: 6.090 baris CSS dilayani mentah, `asset()` tidak menempelkan versi, dan data sama di-fetch berkali-kali dalam satu render. | `wc -l public/css/*.css` = `style.css` 3153, `recruiter.css` 1984, `admin.css` 953; `includes/config/app.php:23-25` tanpa cache busting; `includes/layouts/head.php:23-26` font blocking tanpa `font-display: swap` lokal; `public/js/api.js:115` `getSkillGap()` mengulang fetch careers dan skills yang sudah dimuat halaman; `public/js/api.js:44` fetch skills lagi; `api.js:304-311` dan `316-323` menelah seluruh koleksi tiap kali; 170 blok `<svg>` inline di `public/` dan `includes/`. | Tambah `?v=` dari `filemtime`, muat katalog sekali per sesi dan bagikan, aktifkan `font-display: swap` atau serve font lokal, dan untuk mode tulis saja jangan ambil koleksi yang tidak ditampilkan. |
| P2-3 | Salinan kode dan API tak terpakai membuat gateway menipu. | `public/js/pages/admin-users.js:92` dan `102` memasang listener yang sama dua kali; `public/js/api.js:85` `removeSkill` dan `107` `removeEvidence` tidak dipakai UI (komentar di `84` dan `106`); `public/js/api.js:98` `addEvidence` diekspor di `367` tetapi tidak punya pemanggil, karena UI memanggil nama lain (P0-1); `public/js/api.js:160` `getAdminContent` tidak pernah dipanggil; `csrfToken()` di `api.js:352-355` didefinisikan dan diekspor (`370`) tapi tidak pernah dipanggil; `public/js/ui.js:52-56` `toast(message)` menerima satu argumen sehingga `UI.toast("...","error")` di `evidence.js:153` dan `176` kehilangan maksudnya; `public/js/ui.js:28` `innerHTML` sebagai escape hatch. | Hapus duplikasi listener dan fungsi mati atau aktifkan, tambahkan varian `toastError()`, dan audit setiap `innerHTML:` yang dipanggil dengan data user — saat ini pemakaiannya untuk SVG statis (`ui.js:163`, `gap.js:81`) dan itu perlu komentar plus daftar putih. |
| P2-4 | Sentuh-layar dan gaya menitikberatkan tambalan. | `grep -c '!important'` = 15 di `style.css`, 4 di `recruiter.css`, 4 di `admin.css`; `public/css/style.css:3112-3116` menimpa `div[style*=...]` — artinya gaya inline dibajak lewat selector; tombol ikon admin `public/css/admin.css:747` dan `.rec-pager-link` `public/css/recruiter.css:1251-1259` tanpa tinggi minimum; `public/css/recruiter.css:927` `height: 34px` di bawah 44px; tidak ada `prefers-color-scheme` sama sekali. | Hapus gaya inline dari markup (penyebab `style.css:3112-3116`) dan ganti dengan kelas; minimum 44x44 untuk target sentuh; token untuk warna; satu media query gelap jika dibutuhkan laporan. |
| P2-5 | Container berjalan sebagai root tanpa healthcheck, dan `PORT` masuk ke `sed` tanpa validasi. | `Dockerfile` tanpa `USER` maupun `HEALTHCHECK`, docroot di `Dockerfile:13`, `AllowOverride None` di `16`; `docker/entrypoint.sh:4-6` `PORT="${PORT:-80}"` lalu disisipkan ke dua perintah `sed`. | Validasi `PORT` sebagai angka (`case` atau `expr`), `chown`+`USER www-data`, `HEALTHCHECK CMD` ke `/index.php`, dan `EXPOSE` mengikuti `PORT`. |
| P2-6 | Dokumen lama yang menyesatkan. `PEMETAAN_PROYEK.md` adalah audit terbaik di repo tetapi menunjuk file yang tidak ada. | `Laporan/PEMETAAN_PROYEK.md:159-160`, `231`, `544` merujuk `db/seed.sql` dan `db/optional_hr_role.sql` (keduanya tidak ada; seed saat ini inline di `db/schema.sql:172-229`) dan menyebut peran `hr` butuh migrasi padahal enum sudah memuatnya (`db/schema.sql:29`); `Laporan/Notion.md:93-100` masih "Belum mulai" untuk Define sampai Present. | Tambah baris tanggal dan commit di kepala setiap dokumen, perbaiki rujukan, tandai temuan yang sudah ditutup, dan hapus yang salah. Dokumen tanpa tanggal tidak bisa dipercaya di sidang. |
| P2-7 | Tidak ada `LICENSE`, `.editorconfig`, `CHANGELOG`, `CONTRIBUTING`. | Tidak muncul di root repo. | Tambah `LICENSE` (proyek kuliah tetap perlu pernyataan hak cipta), `.editorconfig` untuk konsistensi, dan `CHANGELOG` singkat yang mencatat fase pengerjaan; dokumen ini membantu nilai dokumentasi. |
| P2-8 | Halaman profil adalah mockup dengan data palsu, bukan layar akun nyata. | `public/js/pages/profile.js:14-23` default "Jhon Doe", `jhondoe@gmail.com`, "Politeknik Negeri Malang", memberSince "January 2026" — tampil untuk akun apa pun; ganti password hanya memunculkan toast tanpa permintaan apa pun (`profile.js:179-181`, komentar mengakui "dummy mode"); panjang minimal 6 karakter, berbeda dengan aturan 8 di register (`public/register.php:28-32`); foto hanya menjadi data URL di `localStorage` (`profile.js:233-258`); visibilitas profil menulis `sb.portfolioVisibility` padahal kolom `is_public` dan `public_token` sudah ada (`db/schema.sql:84-92`). | Baca profil dari sesi dan `student_profiles`; endpoint POST password nyata dengan verifikasi sandi lama dan aturan panjang yang sama seperti register; upload foto ke luar docroot dengan validasi MIME; visibilitas ke kolom DB. |

## 7. Yang Sudah Benar

Kritik tanpa apresiasi tidak kredibel. Sembilan hal ini bagus dan harus dipertahankan saat perbaikan.

1. **Autentikasi bukan sandiwara.** `includes/auth/auth.php:125-134` memakai prepared statement dan menjalankan `password_verify` terhadap dummy hash ketika user tidak ditemukan, sehingga waktu respons tidak membocorkan keberadaan email. Ini teknik yang jarang muncul di proyek kuliah.
2. **Sesi di-hardening dengan benar.** `includes/auth/session.php:10-19` menyetel `use_strict_mode`, `use_only_cookies`, `httponly`, dan `samesite=Lax`; `session_regenerate_id(true)` dipanggil saat login dan saat sesi demo (`auth.php:137`, `165`).
3. **Pemisahan layout per peran di server.** `includes/layouts/` punya header, sidebar, dan footer untuk publik, mahasiswa, HR, dan admin, dipilih lewat `$role` di `head.php:29-33`. Arsitektur ini benar dan tinggal dimanfaatkan untuk memperbaiki P0-4.
4. **Escaping konsisten di PHP dan di URL.** `e()` memakai `ENT_QUOTES` dan UTF-8 (`includes/config/app.php:27-29`) dan dipakai di tempat-tempat yang rentan (`head.php:19`, `20`, `39` untuk class), dan `safeUrl()` (`public/js/ui.js:43-50`) menolak protokol selain http dan https, jadi `javascript:` tidak bisa masuk lewat `file_url`.
5. **View `v_skill_gap` sudah siap.** `db/schema.sql:147-161` menghitung skill yang dimiliki versus yang kurang lewat satu query, dengan filter `status = 'confirmed'`. Ketika P0-2 selesai, halaman `gap.php` cukup membaca view ini; logika gap tidak perlu ditulis ulang.
6. **Skema sudah memikirkan alur CV.** Kolom `source` dan `status` di `user_skills` (`db/schema.sql:117-126`) persis cocok dengan alur "kandidat dari CV lalu dikonfirmasi pengguna" di proposal. Rancangan datanya lebih maju daripada kodenya.
7. **Boundary docroot benar.** `Dockerfile:13` mengarahkan docroot ke `public/` dan komentar di `Dockerfile:2` menunjukkan kesadaran bahwa `includes/`, `db/`, `docs/`, `tools/` tidak boleh terlayani. `.dockerignore` mengecualikan `.git`, `.env`, dan `docs/`.
8. **Kontrak API ditulis di komentar sebelum implementasi.** Setiap fungsi di `api.js` diberi anotasi bentuk endpoint tujuannya, misalnya `// POST api/evidence.php { source_type, title, description, file_url, skill_ids[] }` di `api.js:97`. Ini membuat Fase 1 menjadi pekerjaan pemindahan, bukan penemuan.
9. **Batas non-fungsional dijaga.** Tidak ada persentase kecocokan, tidak ada progress bar skor, dan `gap.php` disajikan sebagai dua daftar, sesuai larangan di `docs/PRD.md` bagian batasan dan Kriteria Keberhasilan nomor 7 (`Laporan/LaporanPBL.md:181`). Disiplin scope ini penting dan tidak boleh dikorbankan saat fitur ditambah.

## 8. Grill Me

Dua puluh pertanyaan adversarial, jawaban menurut kode yang ada, dan vonis. Pertanyaan-pertanyaan ini adalah yang paling mungkin diajukan penguji.

1. **Dua akun mahasiswa dibuka di browser yang sama. Terpisah tidak datanya?** Tidak. Kunci `localStorage` tidak memuat id user (`api.js:8-13`) dan logout tidak membersihkannya (`logout.php:1-7`). **GAGAL.**
2. **Browser ditutup dan dibuka lagi di perangkat lain. Data yang sama muncul?** Tidak. Tidak ada apa pun di server selain baris `users` dan `student_profiles` kosong (`register.php:40-54`). **GAGAL.**
3. **Sebutkan satu baris data domain yang ditulis ke PostgreSQL.** Tidak ada. Domain hanya ditulis ke `localStorage` dan dibaca dari JSON. **GAGAL.**
4. **Mana bukti Kriteria Keberhasilan 2 sampai 5 tentang CV?** Tidak ada. `ls public/mahasiswa` tanpa `cv.php`, dan grep `$_FILES` serta `move_uploaded_file` menghasilkan 0 di seluruh repo. **GAGAL.**
5. **Apa yang terjadi kalau `APP_DEMO=1` ikut ter-deploy?** `check_auth()` mengembalikan `true` untuk role apa pun (`auth.php:66-88`) dan login menerima `password123` (baris 164). Panel pintasan muncul di layar login (`login.php:78-94`). **GAGAL** untuk produksi, **LULUS** saat ini hanya karena `.env` berisi `APP_DEMO=0`, dan itu bukan proteksi kode.
6. **Siapa yang bisa membuat akun HR?** Tidak ada satu orang pun. Register selalu menyisipkan peran `user` (`register.php:48`), `tools/create_admin.php` hanya membuat admin, dan tidak ada layar pembuatan pengguna baru. Enam halaman HR tidak dapat dimasuki lewat alur nyata. **GAGAL.**
7. **Admin benar-benar bisa mengubah data master?** Tidak. Tombol Edit dan Simpan hanya memunculkan toast "(dummy)" (`admin-skills.js:59`, `98`; `admin-careers.js:53`, `84`; `admin-mapping.js:65`, `92`). **GAGAL.**
8. **Password pengguna benar-benar berubah?** Tidak. `profile.js:180` langsung memanggil `UI.toast("Password updated")` tanpa satu permintaan pun, dengan komentar yang mengakui mode dummy (baris 179). **GAGAL.**
9. **Evidence bisa disimpan?** Tidak. `evidence.js:171` memanggil fungsi yang tidak ada. **GAGAL.**
10. **Angka di dashboard admin berasal dari data?** Tidak. `admin.js:9` mengambil stats lalu membuangnya; `13-19` menampilkannya "1248 pengguna", "48 skill", "8 karier", "64 relasi" sebagai konstanta. **GAGAL.**
11. **`/data/admin-users.json` bisa dibuka tanpa login?** Bisa. Docroot `public/` (`Dockerfile:13`), tidak ada `public/.htaccess`, dan `AllowOverride None` (`Dockerfile:16`). **GAGAL.**
12. **Logout menghapus data lokal?** Tidak ada satu pun `localStorage.removeItem` atau `.clear()` di repo. **GAGAL.**
13. **Tautan "Read More" membuka artikel?** Tidak, hanya anchor `#insights` (`public/index.php:689`, `735`, `766`). **GAGAL.**
14. **Pencarian di landing berfungsi?** Tidak; ia mengarah ke `skills.php#q=...` (`landing.js:17`) dan tidak ada skrip halaman yang membaca hash itu, kecuali `recruiter-team.js:241` yang tidak berkaitan. **GAGAL.**
15. **Alur utama bisa dijalankan dengan keyboard dan screen reader saja?** Sebagian. `UI.modal()` punya focus trap (`ui.js:58-120`), tetapi modal evidence bukan modal itu (`evidence.php:48-56`, buka-tutup via `style.display`), toggle sidebar tidak menyetel `aria-expanded` (`ui.js:194-201`), dan tombol ikon admin sebagian mengandalkan `aria-label` yang perlu diverifikasi per layar. **GAGAL** untuk jalur evidence.
16. **Layout utuh di 375 px?** Ada 13 media query di `style.css` dan 5 di masing-masing `recruiter.css` dan `admin.css`, jadi kerangka responsifnya ada; tetapi `style.css:3112-3116` harus menimpa gaya inline agar grid turun ke satu kolom, dan target sentuh 34 px (`recruiter.css:927`) terlalu kecil. **GAGAL sebagian.** Perlu diverifikasi dengan browser sebelum laporan menyebut responsif sebagai fitur selesai.
17. **Adakah tes yang membuktikan satu pun klaim?** Tidak ada. `tests`, `phpunit.xml`, `composer.json`, `.github` semuanya tidak ada. **GAGAL.**
18. **`schema.sql` bisa dijalankan ulang tanpa kehilangan data?** Bisa dijalankan ulang, tapi menghapus seluruh data: `db/schema.sql:21-26` adalah `DROP ... CASCADE`. **GAGAL.**
19. **Kalau HR di luar scope, kenapa ada 6 halaman HR, 5 JSON, dan satu peran di enum?** Itu sisa eksplorasi yang tidak pernah diputus, dan sekarang menjadi risiko karena laporan dan PRD membahasnya seolah nyata. **Keputusan diperlukan**, lihat Bagian 9.
20. **README bisa diikuti mahasiswa lain dari nol sampai aplikasi jalan?** Tidak. File UTF-16LE sehingga tampil rusak di editor dan terminal biasa, dan instruksinya menunjuk `/public/dashboard.html` yang tidak ada. **GAGAL.**

Ringkasan vonis: **0 dari 20 pertanyaan lulus tanpa catatan**. Empat pertanyaan lulus sebagian (nomor 5, 15, 16, dan 18 soal aspek "bisa dijalankan ulang"), dan sisanya gagal. Angka ini sejalan dengan Scorecard: proyek kuat di gerbang masuk dan lemah di seluruh isi rumah.

## 9. Keputusan Scope

Empat keputusan harus diambil dan ditulis, bukan disimpulkan dari ada tidaknya file.

**Keputusan A — Konsol HR keluar dari scope submission.**
Rekomendasi: pindahkan `public/hr/` (6 file) dan `public/js/pages/recruiter*.js` ke folder `experiments/`, atau biarkan ada tetapi dengan banner "Fitur eksperimen, tidak termasuk scope penilaian" dan tanpa klaim apa pun di laporan.
Alasan: proposal (`Laporan/LaporanPBL.md`) secara eksplisit menempatkan rekrutmen, perusahaan, dan lowongan di luar ruang lingkup; tidak ada jalur membuat akun HR (P1-8); tidak ada tabel `companies`, `vacancies`, atau `applications`. Mempertahankannya berarti mempertahankan enam layar palsu.
Konsekuensi: `docs/PRD.md` 5.3 dan bagian skema harus dicoret, dan Scorecard proyek menjadi lebih jujur.
Pemilik: Ketua Tim, dengan persetujuan Dosen Pengampu. Tenggat: sebelum Fase 2 dimulai.

**Keputusan B — Fitur CV diimplementasikan minimal, bukan dihapus.**
Rekomendasi: jalankan versi di P0-7 (unggah PDF dan TXT, validasi, kata kunci dengan daftar sinonim, layar review dan konfirmasi).
Alasan: CV adalah satu-satunya pembeda produk dari CRUD skill biasa dan menjadi dasar Kriteria Keberhasilan 2 sampai 5. Menurunkan scope CV berarti menulis ulang proposal dan kehilangan tesis proyek.
Konsekuensi: 5 sampai 7 hari-orang, dan butuh keputusan soal ekstraksi teks PDF yang boleh dipakai mengingat aturan "tanpa framework eksternal". Bila ekstraksi PDF ternyata melanggar aturan itu, TXT menjadi jalur wajib dan PDF diberi pesan yang jujur; jangan berpura-pura mendukung PDF.
Pemilik: Pengembang Utama. Tenggat: akhir Fase 2.

**Keputusan C — Mode demo hanya untuk rekaman lokal, tidak untuk build yang dinilai.**
Rekomendasi: penilaian dilakukan dengan `APP_DEMO=0` dan akun nyata yang dibuat lewat `register.php` plus `tools/create_admin.php`; panel pintasan demo tidak dirender; `demo_mode()` diperketat seperti di P0-6.
Alasan: satu flag mengubah seluruh sistem autorisasi menjadi `return true`. Tidak ada cara bagi penguji untuk memastikan mana yang mereka nilai.
Konsekuensi: demo butuh tiga akun nyata yang disiapkan sebelum presentasi, dan daftar kredensial itu masuk lampiran laporan, bukan ke kode.
Pemilik: QA dan Pengembang Utama. Tenggat: awal Fase 0.

**Keputusan D — Target deployment diumumkan dan diverifikasi, tidak dikira-kira.**
Rekomendasi: pilih satu (Docker lokal untuk penilaian, atau platform eksternal dengan HTTPS), lalu catat hasil verifikasi nyata di laporan.
Alasan: konfigurasi saat ini mengizinkan `secure` cookie dari `X-Forwarded-Proto` yang bisa dipalsukan (`session.php:8`) dan `PORT` yang disisipkan ke `sed` (`entrypoint.sh:5-6`); dua-duanya hanya aman bila jalur deploy-nya diketahui.
Konsekuensi: perlu satu langkah deploy dan satu uji smoke di environment itu.
Pemilik: Pengembang Utama. Tenggat: Fase 3.

## 10. Roadmap Bertahap

Prinsip urutan: **buat demo tidak memalukan, lalu buat produk nyata, lalu buat klaimnya bisa dibuktikan.** Setiap fase punya exit criteria yang bisa diverifikasi orang lain.

**Fase 0 — Bersihkan demo (1 sampai 2 hari-orang).**
Isi: P0-1 (kontrak API evidence), P0-3 interim (namespace `localStorage` per user plus bersih saat logout), P0-4 (navbar admin), P0-5 (keluarkan PII dari docroot), P0-6 (perketat dan matikan demo), P1-1 sebagian (label "DATA DEMO" dan hapus angka palsu), P2-1 (tautan mati).
Dependensi: tidak ada. Semua bisa dikerjakan paralel.
Exit criteria: skrip demo lima langkah dijalankan tanpa satu pun error konsol; dua akun di satu browser tidak saling menampakkan data; `curl` ke JSON tanpa sesi ditolak; tidak ada angka hardcoded di layar yang diklaim statistik.
Owner: `[TBD]`

**Fase 1 — Server menjadi sumber kebenaran (5 sampai 8 hari-orang).**
Isi: endpoint `public/api/` untuk `skills`, `evidence`, `evidence/{id}`, `target`, `gap`, `profile`; otorisasi per baris dari sesi; CSRF di setiap tulis; adapter `api.js` dialihkan dari JSON ke endpoint; P1-2 (kontrak `skill_ids` dan seed dimuat dari DB); P1-3 (skema idempotent plus `migrations/` dan `seed.sql` terpisah); P1-5 (state loading, kosong, error di gateway).
Dependensi: Fase 0 untuk kontrak dan namespace; `db/schema.sql` yang sudah ada sebagai dasar.
Exit criteria: data sama di dua browser berbeda; `SELECT` di DB cocok dengan yang ditampilkan; menghapus `localStorage` tidak menghilangkan data; `v_skill_gap` (`schema.sql:147-161`) menjadi sumber `gap.php`.
Owner: `[TBD]`

**Fase 2 — Fitur inti dan keputusan scope (5 sampai 7 hari-orang).**
Isi: P0-7 CV minimal (unggah, ekstraksi kata kunci, review, konfirmasi ke `user_skills` dengan `source='cv_extracted'` dan `status='confirmed'`); CRUD admin nyata untuk skills, careers, mapping, users; P2-8 password dan profil nyata; Keputusan A dieksekusi (HR diarsipkan atau diberi label); P1-8 rekonsiliasi peran.
Dependensi: Fase 1, karena CRUD butuh endpoint dan tabel.
Exit criteria: Kriteria Keberhasilan 1 sampai 9 dapat didemokan satu per satu; hasil ekstraksi CV dapat dikoreksi lalu disimpan; tidak ada satu pun tombol "dummy" tersisa di admin.
Owner: `[TBD]`

**Fase 3 — Hardening, aksesibilitas, dan tes (3 sampai 5 hari-orang).**
Isi: P1-4 (CSRF menyeluruh, logout POST, rate limit, pesan generik, security header), P1-6 (modal ber-ARIA, `aria-expanded`, kontras, bahasa konsisten), P1-7 (tes smoke plus CI), P2-2 (cache busting dan minifikasi), P2-3 (hapus duplikasi dan fungsi mati), P2-4 (target sentuh dan gaya inline), P2-5 (Docker non-root, healthcheck, validasi `PORT`), P2-7 (LICENSE, `.editorconfig`, `CHANGELOG`).
Dependensi: Fase 1 dan 2, karena proteksi ditulis di endpoint yang baru ada setelah fase itu.
Exit criteria: daftar periksa keamanan di Bagian 11 tercentang semua; CI hijau; `axe` pada empat halaman utama menghasilkan nol temuan kritis (perintah ini dijalankan, bukan diasumsikan); verifikasi responsif di 375 px dengan screenshot yang direkam.
Owner: `[TBD]`

**Fase 4 — Laporan dan konsolidasi dokumen (3 sampai 4 hari-orang).**
Isi: P0-8 tulis ulang `Laporan/LaporanPBL.md` menjadi laporan implementasi dan pengujian; P1-7 README UTF-8; P2-6 perbaiki `PEMETAAN_PROYEK.md`, `Notion.md`, `docs/PRD.md`; isi Bagian 11 dari log nyata.
Dependensi: Fase 1 sampai 3 selesai, karena isinya adalah hasil.
Exit criteria: setiap klaim fungsional di laporan menunjuk endpoint atau halaman nyata plus satu kasus uji; tidak ada dokumen yang merujuk file tidak ada; tanggal dan commit tercantum di setiap dokumen.
Owner: `[TBD]`

Total 17 sampai 26 hari-orang. Bila waktu tersisa kurang dari itu, potong dengan urutan ini dulu: polish P2, kemudian fitur admin yang tidak dibutuhkan skenario demo, kemudian hardening non-Kritis. **Jangan** potong CV, persistensi server, atau PII, karena ketiganya adalah alasan proyek ini ada.

## 11. Checklist Laporan Akademik

Bagian yang wajib ada di `Laporan/LaporanPBL.md` versi final, dan bukti yang harus menemani setiap klaim.

**Struktur.** Judul produk yang benar dan konsisten dengan kode ("SkillBridge", bukan "Skill Bridge"). Bab: Pendahuluan, Tinjauan Pustaka, Metodologi, Analisis dan Perancangan, Implementasi, Pengujian, Pembahasan dan Keterbatasan, Kesimpulan, Lampiran. Saat ini tidak ada Metodologi, Implementasi, Pengujian, Pembahasan, maupun Kesimpulan; Bab 8 dan 9 kosong (`LaporanPBL.md:339`, `341`).

**Rekonsiliasi Kriteria Keberhasilan.** Sepuluh kriteria di `LaporanPBL.md:169-187` harus berubah menjadi tabel dengan kolom: kriteria, status (terlaksana, sebagian, tidak), bukti (endpoint atau halaman), dan hasil uji. Tanpa kolom status, laporan ini mengklaim hal yang tidak ada.

| Kriteria | Status saat ini | Bukti yang harus diisi |
|---|---|---|
| 1 registrasi, login, kelola profil | Sebagian | Login dan register nyata (`register.php:40-54`, `auth.php:125-137`); profil masih mock (`profile.js:14-23`) |
| 2 unggah CV | Tidak terlaksana | Belum ada kode; perlu `cv.php` plus hasil `ls` |
| 3 identifikasi skill dari CV | Tidak terlaksana | Perlu catatan ekstraksi dan daftar sinonim |
| 4 review dan konfirmasi | Tidak terlaksana | Perlu `cv_review.php` plus screenshot layar konfirmasi |
| 5 kelola skill dan pilih karier | Sebagian | UI ada, tidak persisten (`api.js:54-56`, `85`) |
| 6 bandingkan skill dengan karier | Sebagian | View `v_skill_gap` ada (`schema.sql:147-161`), belum dipakai |
| 7 tampilkan dua daftar tanpa skor | Terlaksana secara tampilan | `gap.php` dua daftar; perlu tes yang membuktikan tidak ada persentase |
| 8 kelola evidence | Tidak | Error di `evidence.js:171`; DB dan endpoint belum ada |
| 9 admin kelola data utama | Tidak | Semua handler "(dummy)" (`admin-skills.js:98`) |
| 10 pengujian fungsional | Tidak | Tidak ada tes sama sekali |

**Bukti pengujian yang harus diproduksi di Fase 3.** Log kasus uji fungsional minimal lima belas baris: registrasi, login salah sandi, login email tidak ada, login akun admin, upload CV, koreksi kandidat CV, tambah skill, hapus skill, tambah evidence, hapus evidence, pilih karier, hitung gap, admin tambah skill, admin ubah relasi, ganti password. Kolomnya: ID, langkah, hasil diharapkan, hasil nyata, tanggal, pelaksana. Screenshot wajib untuk empat layar: landing, dashboard mahasiswa, hasil CV review, skill gap, dan satu layar admin.

**Bukti basis data.** Diagram ER dari `db/schema.sql`, satu tangkapan layar hasil `\dt` atau setara, dan satu bukti query `v_skill_gap` untuk satu user nyata. Sebutkan secara jujur bahwa `user_skills.source` dan `status` dipakai untuk jejak CV, karena itu jembatan antara rancangan dan fitur.

**Bukti keamanan.** Daftar periksa dengan hasil nyata, bukan niatan: CSRF di semua endpoint tulis, otorisasi per baris, logout POST, rate limit, tidak ada PII tanpa autentikasi (sertakan hasil `curl` yang ditolak), `APP_DEMO=0`, tidak ada kredensial di repo, `password_hash` dan `password_verify` dengan catatan mitigasi waktu.

**Rekonsiliasi antar dokumen.** Empat dokumen saling bertentangan dan semuanya masuk lampiran. `docs/PRD.md:90-101` mendeskripsikan `db_pbl.sql` dengan `assessment_questions`, `required_level`, `skill_confidence`, `companies` yang tidak ada di `db/schema.sql` dan enum `evidence_source_type` yang berbeda; `CLAUDE.md` menormakan `scripts.php` yang tidak pernah di-require oleh siapa pun (grep hanya menemukan komentar di filenya sendiri), `$role_css` dan `$page_js` mati, `middleware.php` punya `require_login()` dan `require_role()` yang tidak dipanggil satu kali pun sementara dua puluh halaman terlindungi memanggil `check_auth()` di baris 5, dan menyebut `cv.php` serta `cv_review.php` yang belum ada; `Laporan/PEMETAAN_PROYEK.md:159-160`, `231`, `544` merujuk `db/seed.sql` dan `db/optional_hr_role.sql` yang tidak ada; `Laporan/Notion.md:93-100` dan tabel perannya belum disesuaikan dengan keputusan scope final; `README.md` UTF-16LE dengan instruksi usang. Aturan praktis: dokumen yang menyebut file atau kolom tidak ada harus diperbaiki atau ditarik, bukan dibiarkan.

## 12. Lampiran Bukti

### 12.1 Perintah verifikasi dan hasilnya

Semua dijalankan di root repo pada commit `17455bb`. Kolom terakhir menyatakan status verifikasi.

| ID | Perintah | Hasil yang membuktikan | Temuan |
|---|---|---|---|
| V-1 | `ls public/api` | `No such file or directory` | P0-2 |
| V-2 | `ls public/mahasiswa` | hanya `career.php dashboard.php evidence.php gap.php profile.php skills.php` | P0-7 |
| V-3 | `grep -rn '\$_FILES\|move_uploaded_file' --include='*.php' . \| wc -l` | `0` | P0-7 |
| V-4 | `grep -rn 'csrf_verify' --include='*.php' .` | 3 baris: definisi `includes/auth/csrf.php:32`, `public/login.php:30`, `public/register.php:20` | P1-4 |
| V-5 | `grep -rn 'X-Frame-Options\|Content-Security-Policy\|Strict-Transport\|rate_limit' --include='*.php' .` | kosong | P1-4 |
| V-6 | `ls -d tests composer.json phpunit.xml .github` | keempatnya "No such file or directory" | P1-7 |
| V-7 | `file README.md` | `Unicode text, UTF-16, little-endian text` | P1-7 |
| V-8 | `ls public/.htaccess` | `No such file or directory`; `.htaccess` root hanya berlaku bila docroot = root repo | P0-5 |
| V-9 | `sed -n '13p;16p' Dockerfile` | docroot `public/` dan `AllowOverride None` | P0-5 |
| V-10 | `python3` hitung luminansi `#ec4899` vs `#ffffff` | rasio 3.53:1, ambang AA teks normal 4.5:1 | P1-6 |
| V-11 | `wc -l public/css/*.css` | 3153 + 1984 + 953 = 6090 | P2-2 |
| V-12 | `grep -c '!important' public/css/*.css` | 15, 4, 4 | P2-4 |
| V-13 | `grep -rc '<svg' public includes \| awk` jumlah total | 170 blok SVG inline | P2-2 |
| V-14 | `grep -rn 'location.hash' public/js` | satu saja, di `recruiter-team.js:241`; `landing.js:17` menghasilkan hash tak terpakai | P2-1 |
| V-15 | `grep -rn 'data-nav' public/admin public/hr public/mahasiswa` | 8 halaman admin dan 6 halaman mahasiswa menyetelnya; halaman HR tidak | P0-4 |
| V-16 | `grep -n 'header.navbar' public/js/ui.js` dan `class="admin-header"` | `ui.js:124` penjaga pada `header.navbar`, admin memakai `admin-header` | P0-4 |
| V-17 | `grep -n 'require_login\|require_role' --include='*.php' -r .` | hanya definisi di `includes/auth/middleware.php:10` dan `16` | Bagian 11 |
| V-18 | `grep -rn 'Jhon' public includes` | 7 tempat, termasuk `ui.js:152`, `profile.js:15`, `auth.php:97` | P2-8, P0-4 |
| V-19 | `grep -c '"email"' public/data/admin-users.json` dll | 10, 4, 4 pasangan nama-email terlayani publik | P0-5 |
| V-20 | `sed -n '21,31p' db/schema.sql` | `DROP TABLE ... CASCADE`, `DROP TYPE ... CASCADE`, enum `user_role` memuat `hr` | P1-3, P1-8 |
| V-21 | `grep -n 'APP_DEMO\|password123' .env includes/auth/auth.php public/login.php` | `.env` berisi `APP_DEMO=0`; `auth.php:16-17`, `164`; `login.php:83-85` | P0-6 |
| V-22 | `wc -c Laporan/*.md` | `LaporanPBL.md` 430152 byte dengan dua gambar base64; teks nyata sekitar 29 KB | P0-8 |
| V-23 | `git status --porcelain` | kosong sebelum review; setelah penulisan dokumen ini hanya `Laporan/next.md` | metode |
| V-24 | audit kontrak: bandingkan daftar ekspor di blok `return {` pada `public/js/api.js` dengan seluruh pola `API.<nama>` di `public/js/` | 31 ekspor; panggilan tanpa ekspor tepat satu, `createEvidence`; ekspor tak terpakai: `addEvidence`, `csrfToken`, `getAdminContent`, `removeEvidence`, `removeSkill` | P0-1, P2-3 |
| V-25 | `grep -rn 'localStorage.removeItem\|localStorage.clear' public includes` | tanpa hasil (exit 1) | P0-3 |

### 12.2 Reproduksi bug dalam lima menit

Empat yang paling penting, masing-masing di bawah satu menit.

1. **P0-1.** Buka `public/js/pages/evidence.js` baris 171, lalu `public/js/api.js` baris 367. Nama `createEvidence` tidak ada di daftar ekspor. Tidak perlu menjalankan apa pun. Alternatif runtime: buka halaman evidence, isi form, tekan Simpan, konsol menampilkan `TypeError`.
2. **P0-4.** Buka `public/admin/users.php` baris 12 dan `public/js/ui.js` baris 171. Body admin memuat `data-nav`, penjaga hanya menolak bila sudah ada `header.navbar`, dan `includes/layouts/admin_header.php:10` memakai class `admin-header`. Hasil: navbar mahasiswa muncul di layar admin.
3. **P0-5.** `curl -i http://localhost/data/admin-users.json` tanpa cookie saat container berjalan. Hasil: 200 dengan isi JSON berisi email. (Perintah ini adalah satu-satunya yang butuh server; belum dijalankan dalam review ini, dan itu dicatat di Bagian 1.)
4. **P0-6.** `APP_DEMO=1` lalu `curl -i http://localhost/admin/index.php` tanpa sesi. Hasil: redirect tidak terjadi karena `check_auth()` mengembalikan `true`. Sama seperti nomor 3, belum dieksekusi dalam review ini.

### 12.3 Status temuan lama di PEMETAAN_PROYEK.md

Dokumen itu memuat tabel kesenjangan G1 sampai G15 (`Laporan/PEMETAAN_PROYEK.md` baris 642-661) dan daftar risiko (baris 665-669). Jujur saja: sebagian besar temuan P0 di dokumen ini **bukan penemuan baru**. Pemetaannya:

- **Sudah ada di sana, tetap valid, dan bukti barisnya diperbarui di sini:** G1 = P0-7 (CV). G3 = P0-2 (domain di JSON plus `localStorage`, tanpa `public/api/`). G5 = P0-1 (`TypeError` pada kontrak evidence). G6 = P0-4 (`data-nav` memicu navbar mahasiswa di admin). G7 = P1-1 sebagian (`admin.js:9-25`, angka ditulis keras). G8 = P2-8 (profil, password, foto tanpa backend). G10 dan G11 = P1-8 (peran dan dokumen bertentangan). G12 = dokumentasi konvensi mati (`scripts.php`, `$page_js`, `$role_css`) yang di dokumen ini masuk Bagian 11. G13 = P1-7 dan P2-1 (README UTF-16, hash `#q` tak dibaca). G14 = P0-8 (laporan masih proposal, Bab 8 dan 9 kosong). G15 = P2-6 (status Notion).
- **Sudah disebut di sana tanpa nomor gap:** bypass `APP_DEMO` didokumentasikan di `Laporan/PEMETAAN_PROYEK.md:282` dengan rujukan `includes/auth/auth.php:66-88`, sama dengan yang dipakai P0-6 di sini.
- **Baru dari review ini dan belum pernah dicatat di sana:** P0-3 (kunci `localStorage` tanpa namespace user dan `logout.php` tidak membersihkannya, jadi dua akun di satu browser saling mewarisi), P0-5 (JSON bernaam-email terlayani tanpa autentikasi, diperparah `AllowOverride None` di `Dockerfile:16` dan tidak adanya `public/.htaccess`), P1-2 (kontrak `skill_ids` versus `metadata.skills` dan `seedEvidence()` yang mengosongkan data), P1-3 (`DROP ... CASCADE` tanpa migrasi plus konflik enum `evidence_source_type` dengan PRD), P1-4 (cakupan CSRF hanya dua handler, logout GET, tanpa rate limit, tanpa security header, enumerasi email di `register.php:68`), P1-5 (`fetch` tanpa `res.ok` di enam tempat), P1-6 (aksesibilitas modal, `aria-expanded`, kontras 3.53:1, `lang` versus bahasa isi), P2-2 sampai P2-5 dan P2-7 (performa, duplikasi, target sentuh, Docker, LICENSE).
- **Usang dan harus dikoreksi di dokumen itu:** rujukan ke `db/seed.sql` dan `db/optional_hr_role.sql` (baris 159-160, 231, 544) padahal seed kini inline di `db/schema.sql:172-229` dan file itu tidak ada; G10 menyimpulkan peran `hr` menuntut migrasi manual padahal `db/schema.sql:29` sudah memuat `'hr'` dalam enum. Masalah sebenarnya bukan enum, melainkan tidak ada jalur pembuatan akun HR (`public/register.php:48`, `tools/create_admin.php`).
- **Satu temuan di sana yang sengaja tidak saya pindahkan ke daftar ini karena butuh keputusan, bukan kode:** **G9** — label **Strong** (≥2 evidence) dan **Growing** di `skills.php` (`public/js/api.js:66-73`) adalah penilaian tidak langsung, dan bisa dibaca bertentangan dengan batasan laporan yang melarang penilaian tingkat penguasaan. Putuskan: ganti dengan pernyataan netral seperti "2 evidence terkait", atau tulis alasannya secara eksplisit di laporan.

Dokumen ini tidak mengulang narasi `PEMETAAN_PROYEK.md`. Yang dibaca di atas adalah rujukan ID, dengan bukti baris yang diverifikasi ulang. Nilai tambah review ini terutama pada temuan keamanan dan aksesibilitas (P0-3, P0-5, P1-3 sampai P1-6) yang tidak tersentuh audit sebelumnya.

### 12.4 Yang tidak dapat disimpulkan dari review ini

Tiga hal tidak bisa dinyatakan benar atau salah tanpa menjalankan aplikasi, dan laporan akhir harus menyebutnya sebagai belum diverifikasi sampai seseorang melakukannya: tampilan nyata pada 375 px, hasil alat aksesibilitas otomatis, dan perilaku saat `schema.sql` dijalankan terhadap Supabase termasuk kompatibilitas view `v_skill_gap` dengan pooler. Selama tiga hal ini belum dijalankan, jangan ditulis sebagai lulus di bab pengujian.

---

**Cara memakai dokumen ini.** Kerjakan Fase 0 dan 1 bahkan bila waktu mepet; keduanya menaikkan nilai paling besar per jam yang dihabiskan. Untuk setiap temuan, pasangan buktinya adalah baris kode, bukan opini, jadi tim bisa membantah dengan menunjukkan kode — dan bila bantahan itu benar, perbaiki dokumen ini alih-alih memaklumkan bugnya.

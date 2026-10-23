# DESIGN.md — SkillBridge Architectural Modernist System

Dokumen ini adalah spesifikasi desain (*Single Source of Truth*) untuk sistem antarmuka **SkillBridge**, mengadopsi arketipe **The Architectural Modernist** (Linear / Stripe / Vercel style) dari metodologi **Impeccable**.

---

## 1. Persona & Karakter Visual

- **Tone of Voice**: Percaya diri, presisi, intelektual, berwibawa, dan tenang (*calm precision*).
- **Inspirasi**: Linear.app, Vercel Dashboard, Stripe Docs & Dashboard.
- **Filosofi**: Setiap piksel, margin, dan border memiliki tujuan fungsional. Eliminasi seluruh dekorasi semu ("AI slop", gradient ungu-pink acak, border ganda yang bertumpuk).

---

## 2. Palet Token Warna (:root)

### 2.1 Netral (Cool Architectural Slate)
- `--bg-canvas`: `#f8fafc` (Slate 50 — canvas latar sejuk, anti-silau)
- `--surface`: `#ffffff` (Pure White untuk elevated cards & data surfaces)
- `--surface-subtle`: `#f1f5f9` (Slate 100 — inset cards, input wells, hover rows)
- `--surface-elevated`: `#ffffff`
- `--text-primary`: `#090d16` (Deep Obsidian Slate — kontras tajam, rasio > 14:1)
- `--text-secondary`: `#334155` (Slate 700 — deskripsi dan sub-heading)
- `--text-muted`: `#475569` (Slate 600 — lolos WCAG AA 5.5:1 pada latar putih)
- `--text-light`: `#64748b` (Slate 500 — metadata, tanggal, label mikro)
- `--text-inverse`: `#ffffff`

### 2.2 Border & Hairline Rules
- `--border`: `#e2e8f0` (Slate 200 — hairline 1px tegas dan bersih)
- `--border-subtle`: `#f1f5f9`
- `--border-hover`: `#cbd5e1` (Slate 300)
- `--border-focus`: `#2563eb` (Cobalt 600)

### 2.3 Brand & Semantik Fungsional
- `--primary`: `#2563eb` (Royal Cobalt 600)
- `--primary-hover`: `#1d4ed8` (Cobalt 700)
- `--primary-dark`: `#0f172a` (Slate 900)
- `--primary-subtle`: `#eff6ff` (Cobalt 50)
- `--accent`: `#f43f5e` (Rose Coral 500 — aksen aksi sekunder)
- `--accent-hover`: `#e11d48`
- `--accent-sparkle`: `#f59e0b` (Warm Amber 500 — indikator progress & milestone)

### 2.4 Semantic Badges
- **Success / Verified / Strong**:
  - Background: `#ecfdf5` (Emerald 50)
  - Border: `#a7f3d0` (Emerald 200)
  - Text: `#047857` (Emerald 700)
- **Warning / Reviewing / Growing**:
  - Background: `#fffbeb` (Amber 50)
  - Border: `#fde68a` (Amber 200)
  - Text: `#b45309` (Amber 700)
- **Info / New / Catalog**:
  - Background: `#eff6ff` (Blue 50)
  - Border: `#bfdbfe` (Blue 200)
  - Text: `#1d4ed8` (Blue 700)
- **Danger / Error / Shortfall**:
  - Background: `#fef2f2` (Red 50)
  - Border: `#fecaca` (Red 200)
  - Text: `#b91c1c` (Red 700)

---

## 3. Tipografi & Skala Hirarki

- **Font Family**: `"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, system-ui, sans-serif;`
- **Typographic Scale**:
  - **Hero Display / H1**: `38px` (`2.375rem`), `font-weight: 800`, `letter-spacing: -0.035em`, `line-height: 1.15`
  - **Section Title / H2**: `28px` (`1.75rem`), `font-weight: 700`, `letter-spacing: -0.025em`, `line-height: 1.2`
  - **Card Heading / H3**: `18px` (`1.125rem`), `font-weight: 600`, `letter-spacing: -0.015em`, `line-height: 1.3`
  - **Body Text**: `15px` (`0.9375rem`), `font-weight: 400 / 500`, `line-height: 1.6`
  - **Eyebrow / Category Label**: `11px`, `font-weight: 700`, `letter-spacing: 0.1em`, `text-transform: uppercase`, `color: var(--primary)`
  - **Metadata / Tabular Figures**: `font-variant-numeric: tabular-nums;`, `font-size: 13px`

---

## 4. Spacing, Elevasi & Sudut Geometris

### 4.1 Harmonic Radii
- `--radius-xs`: `4px` (Chips mikro, badge kecil)
- `--radius-sm`: `6px` (Tombol standar, input form, icon boxes)
- `--radius-md`: `10px` (Cards standar, dropdowns)
- `--radius-lg`: `14px` (Large cards, banner modals)
- `--radius-xl`: `18px` (Hero containers, large panels)
- `--radius-round`: `9999px` (Pills, user avatars)

### 4.2 Linear-Grade Elevation System
- `--shadow-xs`: `0 1px 2px 0 rgba(15, 23, 42, 0.04), 0 0 0 1px rgba(15, 23, 42, 0.03);`
- `--shadow-sm`: `0 2px 6px -1px rgba(15, 23, 42, 0.06), 0 0 0 1px rgba(15, 23, 42, 0.04);`
- `--shadow-md`: `0 8px 18px -3px rgba(15, 23, 42, 0.08), 0 2px 6px -2px rgba(15, 23, 42, 0.04);`
- `--shadow-lg`: `0 16px 32px -4px rgba(15, 23, 42, 0.10), 0 4px 8px -2px rgba(15, 23, 42, 0.04);`
- `--shadow-focus`: `0 0 0 3px rgba(37, 99, 235, 0.25);`
- `--shadow-btn`: `0 1px 2px rgba(0, 0, 0, 0.05), inset 0 1px 0 rgba(255, 255, 255, 0.15);`

### 4.3 Transitions & Dynamics
- `--ease-spring`: `cubic-bezier(0.4, 0, 0.2, 1)`
- Interactive buttons & cards: Hover terangkat `-2px` hingga `-3px`, active depression `scale(0.98)`

# TaniMart — Backend Laravel + Website Admin

Dokumen ini adalah **sumber kebenaran (source of truth)** untuk AI/developer yang bekerja di
proyek ini. **Jangan mengarang/meng-halusinasi** hal yang belum tercantum di sini — bila ada
yang berubah, **perbarui dokumen ini** bersamaan dengan perubahannya.

---

## 1. Ringkasan Proyek

TaniMart terdiri dari dua bagian yang berbagi **satu proyek Laravel dan satu database**:

| Bagian | Teknologi | Lokasi |
|---|---|---|
| Backend API (untuk Android) | Laravel 13 + Sanctum | `routes/api.php` |
| Website Admin (untuk browser) | Laravel Blade + Tailwind v4 + Vite, sesi login | `routes/web.php` |
| Aplikasi Android | Kotlin + Jetpack Compose + Retrofit | `/home/arjis/AndroidStudioProjects/TaniMart` |

Versi: **Laravel 13.34.0**, PHP **^8.3** (server: PHP 8.4), MySQL.

Dependensi penting: `barryvdh/laravel-dompdf` ^3.1 (untuk laporan PDF penjualan seller).

---

## 2. Perintah Penting

```bash
cd /opt/lampp/htdocs/TaniMart

# Server dev (WAJIB port 8000; Android & website admin menunjuk ke sini)
php artisan serve --host=0.0.0.0 --port=8000

# Build aset website admin (Tailwind v4 + Vite)
npm run build      # atau: npm run dev

# Cek route
php artisan route:list --path=admin
php artisan route:list --path=api
```

- **IP LAN mesin berubah-ubah** → cek dengan `hostname -I`. Bila IP berubah, perbarui:
  - `.env` → `APP_URL=http://<IP_LAN>:8000`
  - Android → `local.properties` → `API_BASE_URL=http://<IP_LAN>:8000/api/` lalu
    `./gradlew :app:generateDebugBuildConfig`

---

## 3. Akun & Role

Sumber kebenaran role = **pivot `user_roles`** (tabel dari migrasi
`2026_10_17_083947_create_roles_and_user_roles_tables.php`). Kolom `users.role` masih ada
hanya untuk kompatibilitas lama.

Role: `customer`, `seller`, **`admin`**.

- Pembeli **bisa menjadi penjual** — role **ditambahkan**, bukan diganti
  (saat pengajuan toko disetujui, role `seller` di-attach).
- `User::hasRole(string)`, `User::roleNames(): array` tersedia di `app/Models/User.php`.

Akun uji yang ada di database:

| Email | Password | Catatan |
|---|---|---|
| `admin@tanimart.test` | `AdminTaniMart123!` | Admin website (`AdminSeeder`) |
| `testandroid@example.com` | `password123` | Reset manual saat uji |
| `kamu@gmail.com` | `password123` | Reset manual saat uji |

---

## 4. Fakta Backend API (sudah ada — jangan mengarang yang lain)

Middleware: `auth:sanctum` untuk seluruh endpoint ber-token, `seller` (`SellerMiddleware`)
untuk rute penjual, dan **rute admin API hanya mengecek `$request->user()->hasRole('admin')`**.

**Autentikasi** (`AuthController`)
- `POST /api/register` → buat user + attach role `customer` + token
- `POST /api/login` → token + `roles[]`
- `POST /api/logout` · `GET /api/user` (mengembalikan `roles[]`)

**Produk** (`ProductController`)
- `GET /api/products` → **maksimal 10 produk aktif, urutan acak** (`inRandomOrder()->take(10)`)
  — dipakai halaman Beranda Android
- `GET /api/products/{product}`
- `POST/PATCH/DELETE /api/products/{product}` → middleware `seller`
- `GET /api/seller/products` → semua produk seller (tanpa batas, `latest()`)

**Lain-lain yang sudah jalan**: alamat (`/api/addresses*`), keranjang (`/api/cart*`),
checkout & pesanan (`/api/orders*`), pembayaran (`/api/orders/{order}/payment*`),
akun (`/api/account*`), toko seller (`/api/seller/shop`),
penjualan seller (`/api/seller/sales/summary|chart|report` — PDF via dompdf),
gambar produk (`/api/products/{product}/images*`).

**Pengajuan penjual** (`SellerApplicationController`)
- `POST /api/seller-applications` → user mengajukan toko (menolak bila sudah punya toko
  atau masih ada pengajuan `pending`)
- `GET /api/seller-applications/mine` → status pengajuan milik user
- `GET /api/admin/seller-applications?status_pengajuan=pending` → daftar (filter opsional)
- `POST /api/admin/seller-applications/{id}/approve` → **dalam transaksi + `lockForUpdate`**:
  cek status masih `pending`, buat `Shop` (slug unik, status `aktif`), set
  `status_pengajuan = approved` + `reviewed_by` + `reviewed_at`, attach role `seller`
- `POST /api/admin/seller-applications/{id}/reject` → butuh `catatan_admin` (wajib),
  set `status_pengajuan = rejected`

Status pengajuan: `pending` | `approved` | `rejected`.

---

## 5. Fakta Website Admin (sudah ada — jangan mengarang yang lain)

**Akses**: `http://<IP_LAN>:8000/admin/login` (bukan `/login` — itu 404).
Sesi login (`Auth::attempt`), dilindungi middleware `admin`
(`app/Http/Middleware/EnsureAdmin.php`, alias terdaftar di `bootstrap/app.php`).

**Route web** (`routes/web.php`, prefix `admin.`):

| Route | Controller | Status tampilan |
|---|---|---|
| `/admin/login` · `POST /admin/login` | `AdminAuthController` | ✅ selesai (M3) |
| `/admin` | closure redirect → `admin.dashboard` | ✅ |
| `/admin/dashboard` | `AdminDashboardController` | ✅ 4 kartu statistik + pengajuan pending + pesanan terbaru |
| `/admin/users` | `AdminUserController` | ✅ tabel M3 (role dari pivot, `orders_count`, toko) |
| `/admin/seller-applications` | `AdminSellerApplicationController` | ✅ filter chip + dialog Setujui/Tolak |
| `POST .../seller-applications/{id}/approve` · `/reject` | `AdminSellerApplicationController` | ✅ via `SellerApplicationService` |
| `/admin/shops` | `AdminShopController` | ✅ tabel M3 |
| `/admin/products` | `AdminProductController` | ✅ tabel M3 |
| `/admin/orders` | `AdminOrderController` | ✅ tabel M3 |
| `/admin/analytics` | `AdminAnalyticsController` | ✅ kartu ringkasan + grafik batang 6 bulan + status + produk terlaris |
| `POST /admin/logout` | `AdminAuthController` | ✅ |

**Desain saat ini**: **Material Design 3** (lihat bagian 7) — navigation drawer 256px
(permanen desktop, overlay + scrim mobile via checkbox `peer`) + top app bar 64px,
palet dari seed `#fee6a8`, font Roboto + Material Symbols Outlined **self-hosted**
(`bunny()` di `vite.config.js` — internet hanya dibutuhkan saat `npm run build`,
saat runtime file font diambil lokal), Tailwind v4 via `@tailwindcss/vite`,
dark mode kelas `.dark` + `localStorage`.

**Penting — pemuatan font**: `@vite` TIDAK ikut menyertakan `fonts.css`.
`<head>` layout (`admin/layouts/app.blade.php`) dan halaman login WAJIB memanggil
`{{ app(\Illuminate\Foundation\Vite::class)->fonts() }}` (membaca
`public/build/fonts-manifest.json`) untuk me-render `@font-face` + preload.
**Bila baris ini dihapus**: ikon Material Symbols gagal termuat → ligatur tampil
sebagai teks Inggris (mis. item sidebar jadi "dashboard | Dasbor" — teks Inggris
di kiri, label Indonesia di kanan). Label sidebar seragam bahasa Indonesia:
Dasbor, Pengajuan Penjual, Pengguna, Toko, Produk, Pesanan, Analitik, Keluar.

**Fakta render view**:
- Badge status dipakai via `@include('admin.partials.badge', ['tone' => ..., 'text' => ...])`
  dan chip filter via `@include('admin.partials.chip', ['active' => ..., 'href' => ..., 'text' => ...])`
  (partial **biasa** tanpa `@props`, di `resources/views/admin/partials/`).
- `resources/views/admin/components/` (9 komponen `<x-admin.*>`) terdaftar lewat
  `Blade::anonymousComponentPath(resource_path('views/admin/components'), 'admin')`
  di `AppServiceProvider::boot()` — tersedia untuk halaman baru, tetapi 7 halaman
  yang ada sekarang memakai partial di atas.
- Guest route ter-auth diarahkan ke `route('admin.login')` lewat
  `$middleware->redirectGuestsTo()` di `bootstrap/app.php`
  (tanpa ini → error `Route [login] not defined` / 500 setelah logout).
- Bug lama `ucfirst(users)` sudah tidak ada — semua view ditulis ulang.

---

## 6. Peran Website Admin

Selain **memantau aktivitas** (dashboard, pengguna, toko, produk, pesanan, analitik),
website admin juga **berwenang menyetujui/menolak pendaftaran penjualan**
(pengajuan toko) melalui halaman `Pengajuan Penjual`.

Catatan penting: endpoint API admin memakai token Sanctum, sedangkan
website admin memakai sesi. **Jangan memanggil API sendiri dari controller web.**
Logika `approve`/`reject` **sudah dipindah** ke service bersama
`app/Services/SellerApplicationService.php` dan dipakai oleh **kedua** controller
(`Api\SellerApplicationController` untuk API dan
`Admin\AdminSellerApplicationController` untuk web) supaya tidak ada duplikasi logika.
Isi service: `approve()` → transaksi + `lockForUpdate` + cek status pending/belum punya
toko + buat `Shop` (slug unik, status `aktif`) + set `reviewed_by/reviewed_at` +
attach role `seller`; `reject()` → `catatan_admin` wajib + set status rejected.
**Ubah logika di service ini saja, jangan di controller mana pun.**

---

## 7. Desain Material Design 3 (Material You) — ✅ Fase A–E SELESAI

**Status**: seluruh fase di bawah sudah **diimplementasi dan teruji** (lihat catatan
per fase). Bila ada perubahan desain, perbarui bagian ini bersama kodenya.

**Pendekatan**: **token M3 + Tailwind v4**
(tanpa Material Web Components — ringan dan sesuai stack yang sudah ada).

### Fase A — Token & Fondasi ✅ selesai
1. `resources/css/app.css`
   - Seed warna **`#fee6a8`** (kuning muda/amber) → palet tonal M3.
     - Seed ini sangat terang (HCT tone ~92, chroma rendah) sehingga palet hasil
       Material You cenderung pastel/soft. Untuk menjaga kontras (WCAG), pemakaian:
       - `primary` (tone ~40) & `on-primary` untuk teks/ikon di atas surface terang.
       - `primary-container` (tone ~90) untuk latar chip/kartu aksen.
       - Elemen teks utama tetap memakai `surface` + `on-surface`, BUKAN primary.
     - **Sudah dihasilkan**: 72 token (36 role × skema light + dark) dari
       `@material/material-color-utilities` (`Scheme.light()`/`Scheme.dark()`), disimpan
       statis di `:root` (light) dan `.dark` (dark) — tidak dihitung runtime.
       Untuk regenerasi dari seed lain: skrip JS kecil yang di-bundle `rolldown`
       (karena paket itu pure-ESM dengan impor tanpa ekstensi; Node tidak bisa
       menjalankannya langsung).
   - Variabel sistem lengkap: primary/secondary/tertiary (+container & on-),
     error(+container), surface & surface-container-low/lowest/high/highest/dim/bright,
     on-surface, on-surface-variant, outline/outline-variant, inverse-*, shadow, scrim.
   - Mapping ke Tailwind v4 via `@theme inline` (mis. `bg-primary`, `text-on-surface`,
     `border-outline`, `rounded-lg` = shape M3 16px, `shadow-elev-1..3`).
   - Shape scale: radius 8/12/16/28 (dialog 28) + full. Elevation level 1–3 sebagai token shadow.
   - Motion: duration 150/250ms + easing standar M3; `@custom-variant dark`
     (`&:where(.dark, .dark *)`) — kelas, bukan media-query (sudah diverifikasi di CSS build).
2. `vite.config.js`: font **Roboto** + **Material Symbols Outlined** via `bunny()`
   (self-host ke `public/build/assets/` saat build — runtime tidak butuh internet).
   `@font-face`-nya dirender lewat `{{ app(\Illuminate\Foundation\Vite::class)->fonts() }}`
   di `<head>` layout & login (lihat bagian 5 — jangan dihapus).
3. Komponen Blade di `resources/views/admin/components/` (9 file):
   `button`, `card`, `field`, `chip`, `table`, `badge`, `dialog`, `snackbar`, `icon`
   — terdaftar sebagai `<x-admin.*>` via `Blade::anonymousComponentPath`.
   **Yang dipakai 7 halaman saat ini**: partial tanpa `@props` di
   `resources/views/admin/partials/` (`badge`, `chip`) lewat `@include`
   (lebih sederhana & tanpa masalah resolusi komponen).
4. Layout `resources/views/admin/layouts/app.blade.php`:
   - **Top app bar** 64px: tombol menu (mobile), judul halaman, toggle tema gelap/terang
     (belum ada pencarian/akun di top bar).
   - **Navigation drawer** 256px: permanen di desktop, overlay + scrim di mobile,
     item aktif memakai indicator bulat M3 (`.md-nav-item.active` → `secondary-container`).

### Fase B — Login & Dashboard ✅ selesai
5. `admin/auth/login.blade.php`: card radius 28, text field M3, tombol filled.
6. `admin/dashboard/index.blade.php`: kartu statistik dengan ikon di container berwarna
   (primary/secondary/tertiary/error container), grid responsif; ditambah panel
   pengajuan menunggu tinjauan (tautan ke halaman review) + tabel pesanan terbaru.

### Fase C — Halaman Data + Persetujuan Penjual ✅ selesai
7. Keenam halaman terisi data riil (bukan placeholder) dengan layout M3
   (data table + pagination) dan controller di-eager-load relasinya
   (`User::with('roles')`, `Shop::with('user')->withCount('products')`, dst).
   Bug `ucfirst(users)` ikut hilang karena semua view ditulis ulang.
8. **Halaman `Pengajuan Penjual`** ✅:
   - Tabel daftar pengajuan (pemohon + email, nama toko + deskripsi, telepon, tanggal,
     badge status + catatan admin bila ada) + filter chip status
     (`pending`/`approved`/`rejected`) via query `?status=`.
   - Baris `pending`: tombol **Setujui** (dialog konfirmasi native `<dialog>`) →
     buat toko & attach role seller; tombol **Tolak** (dialog dengan textarea
     `catatan_admin` **wajib**).
   - Hasil aksi → redirect `back()` + flash `snackbar_success`/`snackbar_error`
     yang dirender sebagai snackbar M3 di layout (otomatis hilang 4 detik).
9. Refactor logika approve/reject ke `app/Services/SellerApplicationService.php`
   (lihat bagian 6) — **sudah dilakukan**; dipakai API & web, diuji via HTTP.
10. Bug `ucfirst(users)` — **sudah tidak ada** (view ditulis ulang).

### Fase D — Dark Mode & Motion ✅ selesai
11. Palet M3 dark lengkap (surface `#1e1b16` — hasil generate dari seed, bukan nilai
    contoh `#1C1B1F`), toggle di top app bar, preferensi di `localStorage`
    (`tanimart-admin-theme`), `@custom-variant dark` Tailwind v4 terverifikasi di CSS
    build sebagai kelas `:where(.dark, .dark *)` (bukan `prefers-color-scheme`).
12. Transisi hover/focus ring, ripple ringan via CSS (`.md-ripple`), `focus-visible`
    outline M3.

### Fase E — Build & Verifikasi ✅ selesai
13. `npm run build` sukses (font self-host ke `public/build/assets/`). Teruji via HTTP:
    login → logout → semua route admin **200** (guest → 302 ke `/admin/login`),
    filter `?status=` semua 200, approve & reject lewat web **berhasil**
    (toko dibuat + role seller ter-attach; `catatan_admin` tersimpan) lalu data uji
    dikembalikan ke kondisi awal; API tanpa token tetap **401** (bukan 500).
14. Internet hanya dibutuhkan saat `npm run build` (mengunduh font Bunny);
    runtime memakai file font lokal → offline pun tampil benar
    (fallback font sistem bila file build dihapus, tidak fatal).

**Larangan**: perubahan desain web **tidak boleh** menyentuh API Android, dan Android
**dilarang** mengubah `app/src/main/res/` serta `ui/theme/`.

---

## 8. Aturan Anti-Halusinasi untuk AI

Sebelum menulis kode, baca bagian ini:

1. Route/endpoint yang ada **hanya** yang tercantum di bagian 4 & 5 — cek
   `routes/api.php` / `routes/web.php` langsung, jangan menebak.
2. Kolom tabel: cek migrasi di `database/migrations/` (nama kolom memakai **bahasa
   Indonesia**, contoh: `nama_produk`, `harga`, `stok`, `status_pengajuan`,
   `catatan_admin`).
3. Halaman admin yang masih placeholder wajib disebut placeholder (bagian 5) sampai
   benar-benar diisi.
4. Base URL Android dibaca dari `local.properties` (`API_BASE_URL`) via `buildConfigField`
   (`app/build.gradle.kts`) — **jangan menambah base URL hardcoded** di kode Android.
5. Endpoint daftar produk beranda = **maksimal 10 acak**; daftar produk seller = **semua, latest**.
6. Status pengajuan penjual hanya `pending`/`approved`/`rejected`.
7. Dokumen ini bagian dari repo — **perbarui saat ada perubahan fakta**.

---

## 9. Lisensi

Framework Laravel berlisensi MIT (lihat `LICENSE`). Konten proyek TaniMart milik proyek ini.

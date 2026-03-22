# Dapur Pak Slamet

Aplikasi admin berbasis web untuk **mengelola entri makan kantin**: data pelanggan (kode unik / barcode), tempat kerja, pencatatan makan per scan, status lunas/belum lunas, filter laporan, dan export Excel/CSV.

Panel admin dibangun dengan **Laravel** + **Filament** (Livewire).

---

## Stack teknologi

| Layer | Teknologi |
|--------|-----------|
| Bahasa | **PHP** 8.3+ |
| Framework | **Laravel** 13.x |
| Panel admin | **Filament** 5.x |
| UI dinamis | **Livewire** 4.x |
| Frontend build | **Vite** 8.x |
| CSS | **Tailwind CSS** 4.x |
| Database | MySQL / MariaDB (disarankan; sesuaikan di `.env`) |

### Versi utama (sesuai `composer.lock` terkini)

| Paket | Versi terpasang |
|--------|------------------|
| `laravel/framework` | ^13.0 (contoh lock: **13.1.1**) |
| `filament/filament` | ^5.0 (contoh lock: **5.4.1**) |
| `livewire/livewire` | ^4.1 (contoh lock: **4.2.1**) |
| `laravel/tinker` | ^3.0 |

> Versi pasti bisa berubah setelah `composer update`; cek dengan `composer show` atau lihat `composer.lock`.

### PHP — ekstensi & requirement

- **PHP**: `^8.3` (lihat `composer.json`)
- Ekstensi umum Laravel: `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`
- Untuk MySQL: ekstensi `pdo_mysql`

### Composer

- **Composer** 2.2+ (disarankan versi terbaru 2.x)
- Instalasi dependency:

```bash
composer install
```

### Node.js & npm

Digunakan untuk asset frontend (Vite, Tailwind):

- **Node.js** 18+ atau 20+ (disarankan LTS)
- **npm** (bundel dengan Node) atau **pnpm** / **yarn** jika Anda mengubah workflow

```bash
npm install
npm run build    # production
npm run dev      # development + HMR
```

---

## Persyaratan lingkungan

| Alat | Catatan |
|------|---------|
| PHP | 8.3 atau lebih baru |
| Composer | 2.x |
| Node + npm | untuk build frontend |
| Database | MySQL/MariaDB (atau sesuai driver di `.env`) |

---

## Instalasi cepat

1. **Clone / salin proyek**, lalu di root proyek:

```bash
cp .env.example .env
php artisan key:generate
```

2. **Atur `.env`** — minimal: `APP_URL`, koneksi database (`DB_*`), `SESSION_DRIVER`, `QUEUE_CONNECTION` (jika pakai queue).

3. **Dependency & migrasi** (atau pakai skrip Composer):

```bash
composer install
npm install && npm run build
php artisan migrate --force
```

Atau satu perintah (jika skrip `setup` tersedia di `composer.json`):

```bash
composer run setup
```

4. **Buat user admin** — sesuai cara Anda (Seeder, `php artisan tinker`, atau registrasi jika diaktifkan). Filament memakai guard default `web` / panel `admin`.

5. **Akses panel** — default path Filament di proyek ini: **`/admin`** (lihat `AdminPanelProvider`: `->path('admin')`).

---

## Perintah pengembangan

| Perintah | Keterangan |
|----------|------------|
| `composer run dev` | Menjalankan server, queue listener, log, dan Vite secara bersamaan (lihat `composer.json`) |
| `php artisan serve` | Hanya server Laravel |
| `npm run dev` | Vite dev server |
| `composer run lint` | Laravel Pint (format kode) |
| `composer run test` | PHPUnit / tes Laravel |

---

## Fitur aplikasi (ringkas)

- **Tempat kerja** & **Pelanggan** (CRUD) — kode unik untuk scan/barcode.
- **Entry Data Makanan** — scan/ketik kode → isi harga → simpan (waktu makan `now()`).
- **Entry Makanan (daftar)** — tabel entri, filter di atas tabel, bulk ubah status lunas, export **XLSX/CSV** (unduh langsung).
- Snapshot data pelanggan di `meal_entries` untuk laporan/ekspor yang stabil meski master data berubah.

---

## Struktur penting

```
app/
  Filament/
    Concerns/          # Trait form scan (dipakai halaman + list)
    Exports/           # Export tabel ke file
    Pages/             # Halaman Filament (mis. Entry Data Makanan)
    Resources/         # Resource Filament (Pelanggan, Tempat Kerja, Entry Makanan)
  Models/
database/migrations/
resources/views/filament/
```

---

## Lisensi & nama paket Composer

- Proyek ini masih memakai metadata starter **laravel/blank-livewire-starter-kit** di `composer.json`; Anda bisa mengganti `name`, `description`, dan keyword sesuai kebutuhan rilis.

---

## Referensi

- [Laravel](https://laravel.com/docs)
- [Filament](https://filamentphp.com/docs)
- [Livewire](https://livewire.laravel.com/docs)

---

*README ini dibuat untuk dokumentasi lokal deployment; sesuaikan URL, database, dan proses pembuatan user admin dengan kebijakan lingkungan Anda.*

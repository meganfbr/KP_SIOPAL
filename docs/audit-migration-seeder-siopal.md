# Audit Laporan Migrasi dan Seeder - SIOPAL

**Tanggal Audit:** 1 Juni 2026
**Target:** Memastikan kesiapan project untuk instalasi dari nol (`php artisan migrate:fresh --seed`).

---

## 1. Audit Migrations (Skema Database)

Secara keseluruhan, arsitektur migrasi sudah sangat baik, berurutan, dan tidak ada konflik *foreign key* yang menyebabkan error pada proses instalasi bersih.

| Checklist Migrasi | Status | Catatan |
|---|---|---|
| Urutan aman & bebas error | ✅ | Perintah `migrate:fresh` berjalan lancar hingga migrasi terakhir. |
| Konflik nama tabel/kolom | ✅ | Tidak ditemukan. |
| Kolom tertinggal | ✅ | Semua atribut yang dipakai model/resource sudah ada di migrasi. |
| Tabel Legacy (Tidak Terpakai) | ✅ | Tabel-tabel master hardware, software, dan activity log bersih dan digunakan semua. |
| Kerapihan Relasi (Foreign Key) | ⚠️ | Terdapat constraint `ON DELETE CASCADE` pada `laporan_perbaikans`, `activity_logs`, dan `laporan_perbaikan_logs` terhadap `users.id`. Ini aman untuk instalasi awal, namun sistem telah menggunakan konsep *is_active boolean* untuk mencegah *hard-delete*. |

---

## 2. Audit Seeders (Pengisian Data)

Sistem sudah memiliki banyak file seeder yang sangat lengkap (termasuk hardware, software, jadwal, pc_components, hingga rekap inventaris). Namun, **`DatabaseSeeder.php` belum memanggil semuanya**. 

| Fitur/Data | Status di `DatabaseSeeder` | Catatan |
|---|---|---|
| Role & Permission (Spatie) | ✅ | Dipanggil via `RolePermissionSeeder` & trait `HasLabPermissions`. |
| User Super Admin (`admin`) | ✅ | Otomatis dibuat via `UserSeeder`. |
| User Laboran (D2A - D3N) | ✅ | Otomatis dibuat via `UserSeeder` lengkap dengan *sync roles*. |
| Laboratorium & Klasifikasi | ✅ | Dipanggil via `LaboratoriumSeeder`. |
| Master Data Hardware | ✅ | Dijalankan langsung di dalam `DatabaseSeeder` (`Model::insert`). |
| **Lokasi Gudang** | ✅ | Dipanggil via `DatabaseSeeder.php`. |
| **Inventaris PC (50/lab)** | ✅ | Dipanggil via `DatabaseSeeder.php`. |
| **Relasi PC Components** | ✅ | Dipanggil via `DatabaseSeeder.php` (otomatis disinkronkan paska pembuatan PC). |
| **Master Data Software** | ✅ | Dipanggil via `DatabaseSeeder.php`. |
| **Jadwal & Mata Kuliah** | ✅ | Dipanggil via `DatabaseSeeder.php`. |
| **Rekap Awal** | ✅ | Dipanggil via `DatabaseSeeder.php`. |

---

## 3. Hasil Simulasi Paska Seeding (As-Is)

Setelah semua pemanggilan seeder ditambahkan, inilah kondisi sistem paska seeding:

*   **Login Super Admin:** Bisa digunakan (NPP: `A11.2022.2022` / Pass: `superadmin` ATAU `A11.2022.14079` / `admin`).
*   **Login Laboran:** Bisa digunakan (NPP: `LABd2a.2026` / Pass: `lab-d2a`).
*   **Sidebar Menu:** Muncul rapi sesuai hak akses masing-masing.
*   **Data Laboran:** Status aktif/nonaktif jalan normal.
*   **Data Master Hardware:** Tersedia di tabel hardware (Monitor, CPU, RAM, dll).
*   **Inventaris PC:** Terisi lengkap (50 PC per laboratorium).
*   **Lokasi Gudang:** Muncul dan siap digunakan.
*   **Rekap Inventaris:** Riwayat rekap awal bulan Januari 2026 berhasil terbentuk dengan snapshot komponen lengkap.

---

## 4. Rekomendasi Perbaikan (Action Plan)

Perbaikan pemanggilan seeder telah diterapkan pada `DatabaseSeeder.php`. 

Selain pemanggilan seeder, developer **wajib** melakukan inisialisasi ulang permission menggunakan Filament Shield setelah database di-reset. Hal ini dikarenakan konfigurasi role `super_admin` membutuhkan record permission dinamis fisik di database agar tidak terjadi error `403 Forbidden` saat mengakses halaman resource seperti **Data Laboran**.

---

## 5. Panduan Singkat Deployment / Testing untuk Tim (README)

Jika perbaikan sudah dilakukan, tim Anda cukup menjalankan:

```bash
# 1. Install vendor & node_modules
composer install
npm install && npm run build

# 2. Setup env
cp .env.example .env
php artisan key:generate

# 3. Migrate dan Seed (Semua ter-generate otomatis)
php artisan migrate:fresh --seed

# 4. Generate Permission Filament Shield & Clear Cache (WAJIB)
php artisan shield:generate --all --panel=admin --no-interaction
php artisan optimize:clear
```

### Akun Default Testing:

**[SUPER ADMIN]**
*   **NPP:** `A11.2022.14079`
*   **Pass:** `admin`

*(atau NPP: `A11.2022.2022` / Pass: `superadmin`)*

**[LABORAN D2A]**
*   **NPP:** `LABd2a.2026`
*   **Pass:** `lab-d2a`

*(Berlaku pola yang sama untuk lab D2B sampai D3N)*


# Sistem Informasi Puskeswan Wilayah Sukabumi

Sistem Informasi Manajemen Puskeswan (Pusat Kesehatan Hewan) berbasis web (Single Page Application) dengan backend PHP Native dan Database MySQL.

## Fitur Utama

1.  **Autentikasi**: Login sistem.
2.  **Dashboard**: Ringkasan data pelayanan dan grafik statistik.
3.  **Manajemen User**: Tambah dan hapus pengguna (Admin/Staff).
4.  **Layanan Pengobatan**: Pencatatan data pasien, diagnosa/terapi, dan foto dokumentasi.
5.  **Vaksinasi**: Pencatatan vaksinasi PMK, Rabies, dan LSD dengan foto dokumentasi.
6.  **Monitoring**: Data Kelompok Tani (Poktan) dan BUMDES dengan foto dokumentasi.
7.  **Surveilans**: Pencatatan data surveilans PHMS dengan foto dokumentasi.
8.  **Administrasi Surat**: Surat Masuk, Surat Keluar, dan Surat Keterangan dengan foto dokumentasi.
9.  **Stok Obat**: Manajemen stok obat dan pemakaian.
10. **Kegiatan Lain**: Pencatatan kegiatan rapat, pelatihan, atau koordinasi dengan foto dokumentasi.
11. **Kunjungan Tamu**: Buku tamu digital untuk mencatat kunjungan dengan foto dokumentasi.
12. **Kreasi Konten**: Pencatatan konten media sosial (Instagram, TikTok, dll) dengan judul, link, dan screenshot.
13. **Mobile Friendly**: Tampilan responsif dengan sidebar yang dapat disembunyikan pada perangkat mobile.

## Teknologi

-   **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript (Vanilla)
-   **Backend**: PHP Native
-   **Database**: MySQL
-   **Libraries**: Chart.js (Visualisasi Data), SweetAlert2 (Notifikasi)

## Cara Instalasi

1.  **Persyaratan**:
    -   Web Server (Apache/Nginx) dengan PHP (misal: XAMPP, Laragon).
    -   Database MySQL/MariaDB.
    -   Ekstensi GD Library pada PHP harus aktif (untuk kompresi foto).

2.  **Instalasi**:
    -   Clone repository ini atau letakkan folder project di direktori web server (misal: `htdocs`).
    -   Buat database baru di MySQL dengan nama `puskeswan_db`.
    -   Import file `schema.sql` ke dalam database `puskeswan_db`.
    -   Pastikan folder `uploads` ada dan memiliki izin tulis (write permission).
    -   Konfigurasi koneksi database di file `api/config.php` jika diperlukan (default: user `root`, password kosong).

3.  **Migrasi & Update Database (PENTING)**:
    -   Jika Anda melakukan instalasi baru atau update fitur (seperti penambahan kolom foto):
    -   Akses `http://localhost/mps3/api/migrate_db.php` di browser.
    -   Script ini aman dijalankan berulang kali. Script ini akan:
        1.  Membuat tabel yang belum ada (tanpa menghapus data lama).
        2.  Menambahkan kolom baru (misal: `foto`) ke tabel yang sudah ada jika belum tersedia.
    -   Gunakan script ini setiap kali ada pembaruan struktur database.

4.  **Menjalankan**:
    -   Buka browser dan akses `http://localhost/mps3/` (sesuaikan dengan nama folder).
    -   **Login Default**:
        -   Username: `admin`
        -   Password: `admin123`

---

# 🧹 Modul Pelatihan: Membersihkan Repo dari File yang Tidak Diperlukan

Modul ini dibuat sebagai bahan latihan **menghapus file sampah / sudah tidak terpakai** di dalam repositori.
Tujuannya: repo tetap rapi, ringan, dan tidak mengandung file duplikat, backup, log, atau placeholder hosting.

> **Prinsip emas sebelum menghapus:** *Jangan hapus source code yang masih dipakai aplikasi.*
> Selalu verifikasi dulu (lihat langkah 1 di bawah) sebelum menjalankan `rm`.

## 1. Cara memverifikasi file aman dihapus (wajib)

Sebelum menghapus, pastikan file tersebut benar-benar tidak direferensikan:

```bash
# Cari apakah suatu file masih dipanggil di kode (contoh: upload.php)
grep -rn "upload.php" --include=*.php --include=*.html --include=*.js .

# Bandingkan file yang diduga duplikat
diff -q upload.php api/upload.php      # "SAMA" = duplikat persis
```

- Jika `grep` tidak menemukan referensi DAN/ATAU `diff` bilang **SAMA**, file tersebut aman dihapus.
- Jika `diff` bilang **BEDA**, periksa lebih lanjut — jangan hapus sembarangan.

## 2. Kategori file yang boleh dihapus (sampah)

| Kategori | Ciri file | Contoh |
|---|---|---|
| Backup & versi lama | `*.bak`, `*.orig`, `*.pre-rebuild.bak`, folder `backup/` | `app.js.bak`, `db.js.orig`, `backup/` |
| Log aplikasi | `error_log`, `*.log` | `error_log` |
| Placeholder hosting | halaman default cPanel/DomaiNesia | `default.html`, `cp_errordocument.shtml` |
| File uji / diagnostic | tidak dipanggil frontend | `testdb (1).php`, `test_connect.php`, `check_setup.php` |
| Skrip one-off | hardcoded path (`/opt/...`), bukan bagian app | `fix_foto.py`, `patch-multi-foto.js` |
| Duplikat root vs subfolder | isi sama persis | `upload.php`, `login.php`, `config.php`, `migrate_db.php` (root) |
| Dump DB mentah | berisi data, bukan struktur | `mypuskeswan_db_20260413.sql` (simpan `schema.sql`) |

## 3. Langkah penghapusan (aman)

Gunakan `git status` terlebih dahulu untuk memastikan file **belum ter-track** (jika sudah ter-track, pakai `git rm` agar history ikut rapi):

```bash
# Cek status dulu
git status

# Hapus file sampah (working dir)
rm -f "style-blue.css.bak" "db.js.bak" "app.js.bak" "app.js.orig" "*.pre-rebuild.bak"
rm -rf backup
rm -f "error_log" "default.html" "cp_errordocument.shtml"
rm -f "testdb (1).php" "test_connect.php" "check_setup.php"
rm -f "fix_foto.py" "patch-multi-foto.js"
rm -f "upload.php" "login.php" "config.php" "migrate_db.php"
rm -f "mypuskeswan_db_20260413.sql"
```

> Jika file **sudah masuk git** (ter-track), ganti `rm -f` dengan `git rm <file>` lalu `git commit`.

## 4. Pencegahan: `.gitignore`

Agar sampah tidak kembali masuk ke repo, gunakan file `.gitignore` (sudah disediakan di repo ini):

```gitignore
error_log
*.bak
*.orig
*.pre-rebuild.bak
backup/
default.html
cp_errordocument.shtml
uploads/
fix_*.py
patch-*.js
*_db_*.sql
```

Setelah `.gitignore` ada, file dengan pola di atas otomatis diabaikan `git status`.

## 5. Verifikasi hasil

```bash
git status          # pastikan hanya file yang diinginkan yang muncul
ls -a               # periksa sisa file
```

Repo yang sehat setelah dibersihkan hanya berisi: `api/`, `app.js`, `db.js`, `index.html`, `index.php`, `assets/`, `style.css`, `schema.sql`, `uploads/` (ada `.gitkeep`), `backup.sh`, `README.md`, dan `.gitignore`.

## 6. Latihan untuk peserta

1. Identifikasi 3 file di repo ini yang **masuk kategori sampah** dan jelaskan alasannya.
2. Jalankan perintah `grep`/`diff` untuk membuktikan file tersebut aman dihapus.
3. Hapus file tersebut dan verifikasi dengan `git status`.
4. Tambahkan pola file tersebut ke `.gitignore` agar tidak kembali masuk.

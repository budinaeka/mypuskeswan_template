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


# Nuha Buku Tamu Digital

Plugin WordPress untuk sistem buku tamu digital berbasis QR Code. Dikembangkan oleh **Nuha Labs Indonesia**.

## 📋 Fitur Utama

- ✅ **Registrasi Tamu Online** - Formulir registrasi yang dapat disematkan di halaman mana saja
- ✅ **QR Code Otomatis** - Generate QR Code unik untuk setiap tamu
- ✅ **Scan & Check-In** - Scan QR Code untuk check-in otomatis dengan kamera
- ✅ **Sapaan Personal** - Tampilkan nama tamu saat check-in berhasil
- ✅ **Manajemen Souvenir** - Catat pengambilan souvenir dengan pencegahan klaim ganda
- ✅ **Dashboard Admin** - Statistik real-time dan daftar tamu lengkap
- ✅ **Ekspor Data** - Download data tamu ke CSV/Excel
- ✅ **Elementor Widgets** - Widget drag-and-drop untuk Elementor Page Builder
- ✅ **Responsive Design** - Tampilan optimal di desktop, tablet, dan mobile

## 🔄 Alur Kerja

1. **Tamu Registrasi** → Isi formulir di website → Dapat QR Code
2. **Kedatangan** → Tunjukkan QR Code ke resepsionis
3. **Scan QR** → Resepsionis scan dengan kamera → Sistem otomatis check-in
4. **Sapaan** → Nama tamu tampil di layar dengan pesan selamat datang
5. **Souvenir** → Catat pengambilan souvenir (opsional)
6. **Pulang** → Status check-out (fitur coming soon)

## 📦 Instalasi

### Cara 1: Upload Manual

1. Download folder `nuha-buku-tamu-digital`
2. Upload ke `/wp-content/plugins/` di hosting Anda
3. Aktifkan plugin melalui menu 'Plugins' di WordPress admin
4. Plugin siap digunakan!

### Cara 2: Via ZIP

1. Zip folder `nuha-buku-tamu-digital` menjadi `nuha-buku-tamu-digital.zip`
2. Di WordPress Admin, pergi ke **Plugins > Add New > Upload Plugin**
3. Pilih file ZIP dan klik **Install Now**
4. Aktifkan plugin

## 🚀 Cara Menggunakan

### 1. Setup Awal

Setelah aktivasi, plugin akan otomatis:
- Membuat tabel database `wp_nuha_guests`
- Menambahkan menu "Buku Tamu Nuha" di sidebar admin
- Membuat opsi default

### 2. Tempatkan Formulir Registrasi

**Menggunakan Shortcode:**
```
[nuha_guest_form]
```

Tempel shortcode ini di halaman atau posting mana saja menggunakan Elementor atau Gutenberg.

**Menggunakan Elementor Widget:**
1. Buka halaman dengan Elementor
2. Cari widget "Form Buku Tamu Nuha"
3. Drag & drop ke area yang diinginkan
4. Update/Publish halaman

### 3. Gunakan Fitur Scan QR

1. Login ke WordPress Admin
2. Pergi ke **Buku Tamu Nuha > Scan QR**
3. Izinkan akses kamera browser
4. Arahkan kamera ke QR Code tamu
5. Sistem otomatis check-in dan tampilkan data tamu

### 4. Lihat Data Tamu

1. Pergi ke **Buku Tamu Nuha > Daftar Tamu**
2. Lihat semua tamu yang terdaftar
3. Filter berdasarkan status (Registered, Checked In)
4. Ekspor data (fitur coming soon)

## 🛠️ Struktur File

```
nuha-buku-tamu-digital/
├── nuha-buku-tamu-digital.php    # File utama plugin (semua logika di sini)
├── assets/
│   ├── css/
│   │   ├── admin.css             # Style untuk admin
│   │   └── style.css             # Style untuk frontend
│   └── js/
│       ├── admin.js              # JavaScript admin
│       └── main.js               # JavaScript frontend
└── README.md                     # Dokumentasi ini
```

## 🔐 Keamanan

Plugin ini mengimplementasikan:
- ✅ Nonce verification untuk semua AJAX request
- ✅ Input sanitization dan esc_html untuk output
- ✅ SQL injection prevention dengan prepared statements
- ✅ Capability checks (manage_options) untuk akses admin
- ✅ Single-file architecture untuk mencegah missing file errors

## 🧪 Unit Tests

Untuk menjalankan unit tests (memerlukan WP CLI dan PHPUnit):

```bash
cd wp-content/plugins/nuha-buku-tamu-digital
wp phpunit --testsuite=nuha-buku-tamu
```

## 🤝 Dukungan

Jika mengalami masalah:
1. Pastikan WordPress versi 5.0 atau lebih baru
2. Pastikan PHP versi 7.4 atau lebih baru
3. Cek error log di `wp-content/debug.log`
4. Hubungi support@nuhalabs.id

## 📄 Lisensi

GPL v2 or later

## 👨‍💻 Developer

**Nuha Labs Indonesia**
- Website: https://nuhalabs.id
- Email: support@nuhalabs.id

---

© 2024 Nuha Labs Indonesia. All rights reserved.

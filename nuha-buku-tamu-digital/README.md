# Nuha Buku Tamu Digital

**Plugin WordPress untuk Sistem Buku Tamu Digital Berbasis QR Code**

Dikembangkan oleh **Nuha Labs Indonesia**

---

## 📖 Deskripsi

Nuha Buku Tamu Digital adalah plugin WordPress yang mengubah sistem buku tamu konvensional menjadi digital dengan teknologi QR Code. Plugin ini terintegrasi penuh dengan Elementor dan cocok untuk kantor, gedung pertemuan, hotel, event organizer, dan institusi lainnya.

### ✨ Fitur Utama

- **QR Code Unik**: Setiap tamu mendapatkan QR Code unik saat registrasi
- **Scan & Sapa**: Sistem otomatis mengenali tamu dan menampilkan sapaan di layar
- **Check-In/Check-Out**: Pelacakan status kunjungan secara real-time
- **Manajemen Souvenir**: Pencatatan pengambilan souvenir/cenderamata
- **Dashboard Admin**: Statistik dan manajemen data tamu yang lengkap
- **Ekspor Data**: Download data tamu ke format CSV/Excel
- **Elementor Widgets**: Drag & drop widget untuk formulir dan display
- **Mode Kiosk**: Tampilan optimal untuk tablet/layar sentuh
- **Responsif**: Bekerja sempurna di semua perangkat

---

## 🚀 Instalasi

1. Upload folder `nuha-buku-tamu-digital` ke direktori `/wp-content/plugins/`
2. Aktifkan plugin melalui menu 'Plugins' di WordPress
3. Konfigurasi pengaturan di menu **Buku Tamu > Pengaturan**
4. Tambahkan widget Elementor atau gunakan shortcode di halaman yang diinginkan

---

## 📋 Cara Penggunaan

### 1. Setup Awal
- Buka **Buku Tamu > Pengaturan**
- Atur pesan sapaan (gunakan `{name}` untuk nama tamu)
- Aktifkan/nonaktifkan fitur souvenir

### 2. Menampilkan Formulir Registrasi
**Opsi A - Elementor:**
- Edit halaman dengan Elementor
- Cari widget "Nuha Guest Form"
- Drag & drop ke halaman
- Sesuaikan pengaturan

**Opsi B - Shortcode:**
```
[nuha_guest_form]
```

### 3. Menampilkan Welcome Display (untuk TV/Monitor)
**Opsi A - Elementor:**
- Edit halaman dengan Elementor
- Cari widget "Nuha Welcome Display"
- Drag & drop ke halaman
- Atur interval refresh dan gaya

**Opsi B - Shortcode:**
```
[nuha_welcome_display]
```

### 4. Scan QR Code di Resepsionis
- Buka menu **Buku Tamu > Scan QR**
- Gunakan scanner USB atau ketik kode manual
- Sistem akan menampilkan data tamu
- Klik tombol Check-In, Klaim Souvenir, atau Check-Out sesuai kebutuhan

---

## 🎯 Alur Kerja Sistem

```
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│   Tamu      │────▶│  Registrasi  │────▶│  Dapat QR   │
│   Datang    │     │   Online     │     │   Code      │
└─────────────┘     └──────────────┘     └─────────────┘
                                                │
                                                ▼
┌─────────────┐     ┌──────────────┐     ┌─────────────┐
│   Pulang    │◀────│   Ambil      │◀────│  Check-In   │
│  (Check-Out)│     │  Souvenir    │     │  (Scan QR)  │
└─────────────┘     └──────────────┘     └─────────────┘
```

---

## 🛠️ Technical Requirements

- WordPress 5.8 atau lebih tinggi
- PHP 7.4 atau lebih tinggi
- Database MySQL/MariaDB
- Elementor (opsional, untuk widget)

---

## 📁 Struktur File

```
nuha-buku-tamu-digital/
├── nuha-buku-tamu-digital.php    # File utama plugin
├── includes/
│   ├── class-guest-manager.php   # Manajemen data tamu
│   ├── class-qr-generator.php    # Generator QR Code
│   └── class-souvenir-manager.php # Manajemen souvenir
├── admin/
│   ├── class-admin-menu.php      # Menu admin
│   ├── class-admin-ajax.php      # AJAX handlers
│   └── views/
│       ├── dashboard.php         # Dashboard view
│       ├── guests.php            # Daftar tamu
│       ├── scan.php              # Halaman scan QR
│       └── settings.php          # Pengaturan
├── widgets/
│   ├── class-elementor-guest-form-widget.php
│   └── class-elementor-welcome-display-widget.php
├── assets/
│   ├── css/
│   │   ├── style.css             # Frontend styles
│   │   └── admin.css             # Admin styles
│   └── js/
│       ├── main.js               # Frontend JavaScript
│       └── admin.js              # Admin JavaScript
└── tests/                        # Unit tests (akan ditambahkan)
```

---

## 🔐 Keamanan

- Nonce verification untuk semua AJAX request
- Capability checks untuk akses admin
- SQL injection prevention dengan prepared statements
- XSS protection dengan esc_html dan esc_attr
- Input sanitization pada semua form

---

## 📝 Changelog

### Version 1.0.0
- Rilis awal
- Registrasi tamu dengan QR Code
- Check-In/Check-Out system
- Manajemen souvenir
- Dashboard admin
- Widget Elementor
- Ekspor CSV

---

## 👨‍💻 Developer

**Nuha Labs Indonesia**
- Website: https://nuhalabs.id
- Email: info@nuhalabs.id

---

## 📄 License

GPL v2 or later

---

## 🙏 Terima Kasih

Terima kasih telah menggunakan Nuha Buku Tamu Digital. Plugin ini dikembangkan dengan ❤️ oleh tim Nuha Labs Indonesia.

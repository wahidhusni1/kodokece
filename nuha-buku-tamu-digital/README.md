# Nuha Buku Tamu Digital

Plugin WordPress untuk sistem buku tamu digital berbasis QR Code dengan integrasi Elementor.

## Fitur Utama

- **QR Code System**: Generate QR unik untuk setiap tamu
- **Scan & Sapa**: Sistem otomatis mengenali tamu dan menampilkan sapaan di layar
- **Check-In/Check-Out**: Tracking status kunjungan real-time
- **Manajemen Souvenir**: Pencatatan pengambilan dengan pencegahan klaim ganda
- **Dashboard Admin**: Statistik lengkap (total tamu, hari ini, souvenir)
- **Ekspor CSV**: Download data ke Excel
- **Elementor Widgets**: 2 widget drag-and-drop (Formulir & Display TV)
- **Security**: Nonce verification, input sanitization, SQL injection prevention

## Instalasi

1. Upload folder `nuha-buku-tamu-digital` ke `/wp-content/plugins/`
2. Aktifkan plugin melalui menu 'Plugins' di WordPress
3. Konfigurasi pengaturan di menu "Buku Tamu"

## Cara Penggunaan

### Untuk Admin:
1. Buka menu **Buku Tamu** di dashboard WordPress
2. Lihat statistik dan kelola data tamu
3. Gunakan halaman **Scan QR** untuk check-in/check-out tamu
4. Ekspor data ke CSV jika diperlukan

### Untuk Web Designer (Elementor):
1. Edit halaman dengan Elementor
2. Drag widget **Nuha Guest Form** untuk formulir registrasi
3. Drag widget **Nuha Welcome Display** untuk layar TV sapaan

### Untuk Tamu:
1. Isi formulir registrasi di website
2. Dapatkan QR Code
3. Scan QR di resepsionis untuk check-in
4. Ambil souvenir (jika tersedia)

## Struktur Folder

```
nuha-buku-tamu-digital/
├── nuha-buku-tamu-digital.php      # File utama plugin
├── includes/
│   ├── class-guest-manager.php      # Manajemen tamu
│   ├── class-qr-generator.php       # Generator QR Code
│   └── class-souvenir-manager.php   # Manajemen souvenir
├── admin/
│   ├── class-admin-menu.php         # Menu dashboard
│   ├── class-admin-ajax.php         # AJAX handlers
│   └── views/                       # View templates
├── widgets/
│   ├── class-elementor-guest-form-widget.php
│   └── class-elementor-welcome-display-widget.php
├── assets/
│   ├── css/
│   └── js/
└── tests/
    └── test-nuha-buku-tamu.php      # Unit tests (15+ test cases)
```

## Testing

Plugin ini dilengkapi dengan 15+ unit tests untuk memastikan kualitas kode:

```bash
# Install WordPress Testing Environment
# Copy file tests/test-nuha-buku-tamu.php ke folder tests plugin
# Jalankan:
phpunit
```

### Test Cases:
1. Plugin constants defined
2. Guest Manager instance created
3. Register guest successfully
4. QR Code generator creates valid URL
5. QR Code image URL generated
6. Get guest by QR code
7. Check-in guest
8. Check-out guest
9. Check-in non-existent guest
10. Claim souvenir
11. Prevent double souvenir claim
12. Get guests list
13. Count guests
14. Souvenir stats
15. Search guests

## Requirements

- WordPress 5.8 atau lebih tinggi
- PHP 7.4 atau lebih tinggi
- Elementor (opsional, untuk widget)

## Developer

**Nuha Labs Indonesia**  
Website: https://nuhalabs.id

## License

GPL v2 or later

## Changelog

### Version 1.0.0
- Initial release
- QR Code registration system
- Check-in/Check-out functionality
- Souvenir management
- Elementor integration
- Admin dashboard with statistics
- CSV export
- Unit tests included

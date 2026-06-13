<div class="wrap nuha-btd-scan">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="nuha-scan-container" style="max-width: 600px; margin: 20px auto; text-align: center;">
        <div class="nuha-scan-box" style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2>Scan QR Code Tamu</h2>
            <p>Gunakan scanner QR atau kamera untuk scan kode QR tamu</p>
            
            <div id="nuha-scanner-result" style="margin: 20px 0; display: none;">
                <div class="nuha-guest-info" style="background: #f0f0f1; padding: 20px; border-radius: 5px;">
                    <h3 id="guest-name" style="margin: 0 0 10px; font-size: 24px;"></h3>
                    <p id="guest-company" style="margin: 0 0 10px; color: #666;"></p>
                    <p id="guest-status" style="margin: 0 0 20px; font-weight: bold;"></p>
                    
                    <div class="nuha-scan-actions">
                        <button id="btn-check-in" class="button button-primary button-large">Check-In</button>
                        <button id="btn-check-out" class="button button-secondary button-large">Check-Out</button>
                        <button id="btn-claim-souvenir" class="button button-hero button-large" style="background: #d63638; color: white;">🎁 Klaim Souvenir</button>
                    </div>
                </div>
            </div>

            <div id="nuha-scanner-input" style="margin: 20px 0;">
                <input type="text" id="qr-code-input" placeholder="Masukkan kode QR manual atau scan..." 
                       style="width: 100%; padding: 15px; font-size: 18px; text-align: center;" autofocus>
                <button id="btn-submit-qr" class="button button-primary button-large" style="margin-top: 10px; width: 100%;">
                    Proses QR Code
                </button>
            </div>

            <div id="nuha-message" style="margin-top: 20px; padding: 15px; border-radius: 5px; display: none;"></div>
        </div>

        <div class="nuha-instructions" style="margin-top: 30px; text-align: left; background: #fff; padding: 20px; border-radius: 8px;">
            <h3>Cara Penggunaan:</h3>
            <ol>
                <li>Minta tamu menunjukkan QR Code yang telah diterima</li>
                <li>Scan QR Code menggunakan scanner USB atau ketik kode manual di kolom input</li>
                <li>Sistem akan menampilkan data tamu secara otomatis</li>
                <li>Klik tombol <strong>Check-In</strong> saat tamu tiba</li>
                <li>Klik tombol <strong>Klaim Souvenir</strong> saat tamu mengambil souvenir</li>
                <li>Klik tombol <strong>Check-Out</strong> saat tamu pulang</li>
            </ol>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let currentQrCode = '';

    // Submit QR Code
    $('#btn-submit-qr').on('click', function() {
        processQRCode();
    });

    // Enter key to submit
    $('#qr-code-input').on('keypress', function(e) {
        if (e.which === 13) {
            processQRCode();
        }
    });

    function processQRCode() {
        const qrCode = $('#qr-code-input').val().trim();
        
        if (!qrCode) {
            showMessage('QR Code tidak boleh kosong', 'error');
            return;
        }

        currentQrCode = qrCode;

        $.post(ajaxurl, {
            action: 'nuha_btd_get_guest_data',
            qr_code: qrCode,
            nonce: nuhaBtdAdmin.nonce
        }, function(response) {
            if (response.success) {
                const guest = response.data.guest;
                
                $('#guest-name').text(guest.full_name);
                $('#guest-company').text(guest.company || 'Tidak ada instansi');
                $('#guest-status').text('Status: ' + guest.status.toUpperCase());
                
                $('#nuha-scanner-result').slideDown();
                showMessage('Data tamu ditemukan!', 'success');
                
                // Update button states based on status
                updateButtonStates(guest);
            } else {
                showMessage(response.data.message || 'Tamu tidak ditemukan', 'error');
                $('#nuha-scanner-result').slideUp();
            }
        });
    }

    function updateButtonStates(guest) {
        // Reset all buttons
        $('#btn-check-in, #btn-check-out, #btn-claim-souvenir').prop('disabled', false);
        
        if (guest.status === 'checked_in') {
            $('#btn-check-in').prop('disabled', true).text('Sudah Check-In');
        } else if (guest.status === 'checked_out') {
            $('#btn-check-out').prop('disabled', true).text('Sudah Check-Out');
        }
        
        if (guest.souvenir_claimed) {
            $('#btn-claim-souvenir').prop('disabled', true).text('✓ Souvenir Sudah Diambil');
        }
    }

    // Check-In Action
    $('#btn-check-in').on('click', function() {
        $.post(ajaxurl, {
            action: 'nuha_btd_check_in',
            qr_code: currentQrCode,
            nonce: nuhaBtdAdmin.nonce
        }, function(response) {
            if (response.success) {
                showMessage('Check-in berhasil!', 'success');
                $('#guest-status').text('Status: CHECKED_IN');
                $('#btn-check-in').prop('disabled', true).text('Sudah Check-In');
            } else {
                showMessage(response.data.message, 'error');
            }
        });
    });

    // Check-Out Action
    $('#btn-check-out').on('click', function() {
        $.post(ajaxurl, {
            action: 'nuha_btd_check_out',
            qr_code: currentQrCode,
            nonce: nuhaBtdAdmin.nonce
        }, function(response) {
            if (response.success) {
                showMessage('Check-out berhasil!', 'success');
                $('#guest-status').text('Status: CHECKED_OUT');
                $('#btn-check-out').prop('disabled', true).text('Sudah Check-Out');
            } else {
                showMessage(response.data.message, 'error');
            }
        });
    });

    // Claim Souvenir Action
    $('#btn-claim-souvenir').on('click', function() {
        if (!confirm('Konfirmasi: Tamu ini sudah menerima souvenir?')) {
            return;
        }

        $.post(ajaxurl, {
            action: 'nuha_btd_claim_souvenir',
            qr_code: currentQrCode,
            nonce: nuhaBtdAdmin.nonce
        }, function(response) {
            if (response.success) {
                showMessage('Souvenir berhasil dicatat!', 'success');
                $('#btn-claim-souvenir').prop('disabled', true).text('✓ Souvenir Sudah Diambil');
            } else {
                showMessage(response.data.message, 'error');
            }
        });
    });

    function showMessage(message, type) {
        const $msg = $('#nuha-message');
        $msg.text(message)
            .removeClass('error success')
            .addClass(type)
            .css('background', type === 'success' ? '#d4edda' : '#f8d7da')
            .css('color', type === 'success' ? '#155724' : '#721c24')
            .fadeIn();
        
        setTimeout(() => {
            $msg.fadeOut();
        }, 5000);
    }

    // Auto-focus input for continuous scanning
    $(document).on('click', function() {
        $('#qr-code-input').focus();
    });
});
</script>

<div class="wrap nuha-btd-scan">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="nuha-scan-container" style="max-width: 600px; margin: 20px auto; text-align: center;">
        <div id="scan-result" style="display: none; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2 id="welcome-title" style="color: #2271b1; font-size: 28px; margin-bottom: 10px;"></h2>
            <p id="guest-company" style="color: #666; font-size: 18px;"></p>
            <div id="guest-photo" style="margin: 20px 0;"></div>
            <div class="scan-actions" style="margin-top: 20px;">
                <button id="claim-souvenir" class="button button-primary button-hero">
                    <span class="dashicons dashicons-gift"></span> Catat Souvenir
                </button>
                <button id="check-out" class="button button-secondary button-hero" style="margin-left: 10px;">
                    Check Out
                </button>
            </div>
            <div id="souvenir-status" style="margin-top: 15px; color: #00a32a; font-weight: bold;"></div>
        </div>

        <div id="scan-form" style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3>Scan QR Code Tamu</h3>
            <p>Masukkan kode QR atau scan menggunakan scanner:</p>
            <input type="text" id="qr-code-input" placeholder="Masukkan QR Code..." style="width: 100%; padding: 15px; font-size: 18px; margin: 15px 0; border: 2px solid #ddd; border-radius: 4px;">
            <button id="submit-qr" class="button button-primary button-hero" style="width: 100%;">
                Scan Sekarang
            </button>
            <div id="scan-message" style="margin-top: 15px; font-weight: bold;"></div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let currentQrCode = '';

    $('#submit-qr').on('click', function() {
        const qrCode = $('#qr-code-input').val().trim();
        
        if (!qrCode) {
            $('#scan-message').html('<span style="color: red;">QR Code tidak boleh kosong</span>');
            return;
        }

        currentQrCode = qrCode;

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'nuha_btd_scan_qr',
                qr_code: qrCode,
                nonce: nuhaBtdConfig.nonce
            },
            success: function(response) {
                if (response.success) {
                    const guest = response.data.guest;
                    $('#welcome-title').text(response.data.welcome_message);
                    $('#guest-company').text(guest.company || '');
                    
                    if (guest.photo_url) {
                        $('#guest-photo').html('<img src="' + guest.photo_url + '" alt="Foto Tamu" style="max-width: 200px; border-radius: 8px;">');
                    } else {
                        $('#guest-photo').html('<img src="' + response.data.qr_code_image + '" alt="QR Code" style="max-width: 200px;">');
                    }

                    if (guest.souvenir_claimed == '1') {
                        $('#souvenir-status').text('✓ Souvenir sudah diambil pada: ' + guest.souvenir_claim_time);
                        $('#claim-souvenir').prop('disabled', true).text('Souvenir Sudah Diambil');
                    } else {
                        $('#souvenir-status').text('');
                        $('#claim-souvenir').prop('disabled', false).html('<span class="dashicons dashicons-gift"></span> Catat Souvenir');
                    }

                    $('#scan-form').hide();
                    $('#scan-result').fadeIn();
                } else {
                    $('#scan-message').html('<span style="color: red;">' + response.data.message + '</span>');
                }
            },
            error: function() {
                $('#scan-message').html('<span style="color: red;">Terjadi kesalahan. Silakan coba lagi.</span>');
            }
        });
    });

    $('#claim-souvenir').on('click', function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'nuha_btd_claim_souvenir',
                qr_code: currentQrCode,
                nonce: nuhaBtdConfig.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#souvenir-status').text('✓ Souvenir berhasil dicatat!');
                    $('#claim-souvenir').prop('disabled', true).text('Souvenir Sudah Diambil');
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    $('#check-out').on('click', function() {
        if (confirm('Apakah Anda yakin tamu ini akan check out?')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'nuha_btd_check_out',
                    qr_code: currentQrCode,
                    nonce: nuhaBtdConfig.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Check out berhasil!');
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        }
    });

    // Enter key to submit
    $('#qr-code-input').on('keypress', function(e) {
        if (e.which === 13) {
            $('#submit-qr').click();
        }
    });
});
</script>

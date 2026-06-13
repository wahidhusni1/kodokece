/**
 * Nuha Buku Tamu Digital - Frontend JavaScript
 */

(function($) {
    'use strict';

    // Handle Guest Registration Form
    $(document).on('submit', '#nuha-guest-form', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const $message = $('#form-message');
        
        // Disable button during submission
        $submitBtn.prop('disabled', true).text('Mengirim...');
        
        const formData = {
            action: 'nuha_btd_register_guest',
            nonce: nuhaBtdConfig.nonce,
            full_name: $form.find('[name="full_name"]').val(),
            company: $form.find('[name="company"]').val() || '',
            phone: $form.find('[name="phone"]').val() || '',
            visit_purpose: $form.find('[name="visit_purpose"]').val() || ''
        };

        $.post(nuhaBtdConfig.ajaxUrl, formData, function(response) {
            if (response.success) {
                $message.html(
                    '<div style="background: #d4edda; color: #155724; padding: 20px; border-radius: 5px; text-align: center;">' +
                    '<h3 style="margin: 0 0 10px;">Pendaftaran Berhasil!</h3>' +
                    '<p>Silakan screenshot atau simpan QR Code di bawah ini untuk check-in:</p>' +
                    '<img src="' + response.data.qr_code_url + '" alt="QR Code" style="max-width: 250px; margin: 15px auto; display: block;">' +
                    '</div>'
                );
                $form[0].reset();
            } else {
                $message.html(
                    '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;">' +
                    (response.data.message || 'Terjadi kesalahan. Silakan coba lagi.') +
                    '</div>'
                );
            }
            
            $submitBtn.prop('disabled', false).text($submitBtn.data('original-text') || 'Daftar Sekarang');
        }).fail(function() {
            $message.html(
                '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;">' +
                'Terjadi kesalahan koneksi. Silakan coba lagi.' +
                '</div>'
            );
            $submitBtn.prop('disabled', false);
        });
    });

    // Welcome Display Auto-refresh
    $(document).on('init-nuha-welcome-display', function() {
        const $display = $('.nuha-welcome-display');
        if ($display.length === 0) return;
        
        const refreshInterval = parseInt($display.data('refresh')) * 1000 || 5000;
        let lastGuestId = null;
        
        function checkNewGuest() {
            $.post(nuhaBtdConfig.ajaxUrl, {
                action: 'nuha_btd_get_latest_guest',
                nonce: nuhaBtdConfig.nonce
            }, function(response) {
                if (response.success && response.data.guest && response.data.guest.id !== lastGuestId) {
                    lastGuestId = response.data.guest.id;
                    displayGuest(response.data.guest);
                }
            });
        }
        
        function displayGuest(guest) {
            const welcomeMsg = nuhaBtdConfig.welcomeMessage || 'Selamat Datang, {name}!';
            const message = welcomeMsg.replace('{name}', guest.full_name)
                                     .replace('{company}', guest.company || '');
            
            let html = '<div style="animation: fadeIn 0.5s;">';
            html += '<h1 style="font-size: 56px; margin-bottom: 20px;">' + message + '</h1>';
            
            if (guest.photo_url) {
                html += '<img src="' + guest.photo_url + '" alt="' + guest.full_name + '" ' +
                       'style="width: 200px; height: 200px; border-radius: 50%; object-fit: cover; ' +
                       'margin: 20px auto; display: block; border: 5px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">';
            }
            
            if (guest.company) {
                html += '<p style="font-size: 28px; opacity: 0.9;">' + guest.company + '</p>';
            }
            
            html += '</div>';
            
            $('#nuha-guest-display').html(html).fadeIn();
            
            // Hide after 10 seconds
            setTimeout(function() {
                $('#nuha-guest-display').fadeOut();
                lastGuestId = null;
            }, 10000);
        }
        
        // Check every interval
        setInterval(checkNewGuest, refreshInterval);
        
        // Initial check
        setTimeout(checkNewGuest, 1000);
    });

    // Initialize on document ready
    $(document).ready(function() {
        $(document).trigger('init-nuha-welcome-display');
    });

})(jQuery);

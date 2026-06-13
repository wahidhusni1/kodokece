/**
 * Nuha Buku Tamu Digital - Admin JavaScript
 */

(function($) {
    'use strict';

    // Initialize admin nonce for AJAX calls
    window.nuhaBtdAdmin = window.nuhaBtdAdmin || {};

    $(document).ready(function() {
        
        // Handle CSV Export
        $('#nuha-export-csv').on('click', function(e) {
            e.preventDefault();
            
            const searchValue = $('#nuha-search-guest').val();
            const downloadUrl = ajaxurl + '?action=nuha_btd_export_guests&nonce=' + nuhaBtdAdmin.nonce;
            
            if (searchValue) {
                window.location.href = downloadUrl + '&search=' + encodeURIComponent(searchValue);
            } else {
                window.location.href = downloadUrl;
            }
        });

        // Handle QR Code View Modal
        $(document).on('click', '.view-qr', function() {
            const qrCode = $(this).data('qr');
            const guestName = $(this).closest('tr').find('td:nth-child(2)').text();
            
            // Simple alert with QR code image URL
            const qrImageUrl = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' + 
                              encodeURIComponent(nuhaBtdAdmin.siteUrl + '/?guest=' + qrCode);
            
            const modalHtml = `
                <div id="nuha-qr-modal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
                                               background: rgba(0,0,0,0.7); z-index: 100000; 
                                               display: flex; align-items: center; justify-content: center;">
                    <div style="background: white; padding: 30px; border-radius: 8px; text-align: center; max-width: 400px;">
                        <h3 style="margin: 0 0 20px;">QR Code - ${guestName}</h3>
                        <img src="${qrImageUrl}" alt="QR Code" style="max-width: 100%;">
                        <br><br>
                        <button id="close-qr-modal" class="button">Tutup</button>
                        <a href="${qrImageUrl}" download="qr-${guestName}.png" class="button button-primary">Download</a>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            
            $(document).on('click', '#close-qr-modal, #nuha-qr-modal', function(e) {
                if (e.target === this) {
                    $('#nuha-qr-modal').remove();
                }
            });
        });

        // Auto-hide notices after 5 seconds
        setTimeout(function() {
            $('.notice').fadeOut();
        }, 5000);

        // Confirm before resetting souvenir claim
        $(document).on('click', '.reset-souvenir-claim', function(e) {
            if (!confirm('Apakah Anda yakin ingin mereset status klaim souvenir tamu ini?')) {
                e.preventDefault();
            }
        });
    });

})(jQuery);

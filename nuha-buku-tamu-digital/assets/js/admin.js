// Nuha Buku Tamu Digital - Admin JavaScript

jQuery(document).ready(function($) {
    // Export guests functionality
    $('#export-guests').on('click', function() {
        window.location.href = ajaxurl + '?action=nuha_btd_export_guests&nonce=' + nuhaBtdConfig.nonce;
    });

    // Auto-hide admin notices after 5 seconds
    setTimeout(function() {
        $('.notice').fadeOut();
    }, 5000);
});

<div class="wrap nuha-btd-guests">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="nuha-guests-actions" style="margin: 20px 0;">
        <button id="export-guests" class="button button-primary">
            <span class="dashicons dashicons-download"></span> Ekspor CSV
        </button>
    </div>

    <table class="wp-list-table widefat fixed striped" id="nuha-guests-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Lengkap</th>
                <th>Instansi</th>
                <th>No. HP</th>
                <th>Status</th>
                <th>Souvenir</th>
                <th>Tanggal</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="8" style="text-align: center;">Memuat data...</td>
            </tr>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    function loadGuests() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'nuha_btd_get_all_guests',
                nonce: nuhaBtdConfig.nonce
            },
            success: function(response) {
                if (response.success) {
                    let html = '';
                    response.data.forEach(function(guest) {
                        html += '<tr>';
                        html += '<td>' + guest.id + '</td>';
                        html += '<td>' + guest.full_name + '</td>';
                        html += '<td>' + (guest.company || '-') + '</td>';
                        html += '<td>' + (guest.phone || '-') + '</td>';
                        html += '<td><span class="status-badge status-' + guest.status + '">' + guest.status + '</span></td>';
                        html += '<td>' + (guest.souvenir_claimed == '1' ? 'Sudah' : 'Belum') + '</td>';
                        html += '<td>' + guest.created_at + '</td>';
                        html += '</tr>';
                    });
                    $('#nuha-guests-table tbody').html(html);
                }
            }
        });
    }

    $('#export-guests').on('click', function() {
        window.location.href = ajaxurl + '?action=nuha_btd_export_guests&nonce=' + nuhaBtdConfig.nonce;
    });

    loadGuests();
});
</script>

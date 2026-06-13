<div class="wrap nuha-btd-guests">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="nuha-guests-actions" style="margin: 20px 0;">
        <input type="text" id="nuha-search-guest" placeholder="Cari nama atau instansi..." style="width: 300px; padding: 8px;">
        <button id="nuha-export-csv" class="button button-primary">
            <span class="dashicons dashicons-download"></span> Ekspor ke CSV
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
                <th>Tanggal Daftar</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody id="nuha-guests-body">
            <tr>
                <td colspan="9" style="text-align: center;">Memuat data...</td>
            </tr>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    // Load guests data
    function loadGuests(search = '') {
        $.post(ajaxurl, {
            action: 'nuha_btd_get_guests_list',
            search: search,
            nonce: nuhaBtdAdmin.nonce
        }, function(response) {
            if (response.success) {
                let html = '';
                response.data.forEach(function(guest) {
                    let statusClass = guest.status === 'checked_in' ? 'green' : (guest.status === 'checked_out' ? 'gray' : 'blue');
                    let souvenirStatus = guest.souvenir_claimed ? '✓ Ya' : '✗ Belum';
                    
                    html += '<tr>';
                    html += '<td>' + guest.id + '</td>';
                    html += '<td>' + guest.full_name + '</td>';
                    html += '<td>' + (guest.company || '-') + '</td>';
                    html += '<td>' + (guest.phone || '-') + '</td>';
                    html += '<td><span class="status-' + statusClass + '">' + guest.status + '</span></td>';
                    html += '<td>' + souvenirStatus + '</td>';
                    html += '<td>' + guest.created_at + '</td>';
                    html += '<td>';
                    html += '<button class="button button-small view-qr" data-qr="' + guest.qr_code + '">Lihat QR</button>';
                    html += '</td>';
                    html += '</tr>';
                });
                $('#nuha-guests-body').html(html);
            }
        });
    }

    loadGuests();

    // Search functionality
    $('#nuha-search-guest').on('keyup', function() {
        loadGuests($(this).val());
    });

    // Export CSV
    $('#nuha-export-csv').on('click', function() {
        window.location.href = ajaxurl + '?action=nuha_btd_export_guests&nonce=' + nuhaBtdAdmin.nonce;
    });
});
</script>

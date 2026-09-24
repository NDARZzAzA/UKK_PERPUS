<?php
// Footer HTML umum
?>
        </div><!-- end content-wrapper -->
    </main>
</div><!-- end app-container -->
<script>
// Konfirmasi hapus data
function confirmDelete() {
    return confirm('Apakah Anda yakin ingin menghapus data ini?');
}

// Toggle password visibility
function togglePassword(btn) {
    const input = btn.previousElementSibling;
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '🙈';
    } else {
        input.type = 'password';
        btn.textContent = '👁️';
    }
}

// Live search functionality
function liveSearch(inputId, tableId) {
    const query = document.getElementById(inputId).value.toLowerCase();
    const rows = document.querySelectorAll('#' + tableId + ' tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
}

// Suara notifikasi (dimainkan saat aksi penting berhasil, mis. berhasil meminjam buku / mengirim usulan)
function playNotifSound() {
    try {
        const audio = new Audio('../assets/audio/notifikasi.mp3');
        audio.volume = 0.6;
        audio.play().catch(function () {
            // Browser memblokir autoplay tanpa interaksi user - abaikan saja, tidak fatal
        });
    } catch (e) {
        // Abaikan jika audio tidak didukung
    }
}

// Dropdown notifikasi (lonceng di top-bar)
function toggleNotifDropdown(e) {
    e.stopPropagation();
    const dd = document.getElementById('notif-dropdown');
    if (!dd) return;
    dd.style.display = (dd.style.display === 'none' || !dd.style.display) ? 'block' : 'none';
}
document.addEventListener('click', function (e) {
    const dd = document.getElementById('notif-dropdown');
    const bell = document.querySelector('.notif-bell');
    if (dd && dd.style.display === 'block' && !dd.contains(e.target) && e.target !== bell) {
        dd.style.display = 'none';
    }
});
</script>
</body>
</html>
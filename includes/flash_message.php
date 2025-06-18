<?php

// Cek apakah ada flash message di session
if (isset($_SESSION['flash_message'])): ?>
    <div class="container pt-4">
        <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> text-center alert-dismissible fade show" role="alert">
            <?php 
                // Tampilkan pesan
                echo sanitize_output($_SESSION['flash_message']['message']); 
                
                // PENTING UNTUK MEMORI: Hapus pesan dari session setelah ditampilkan
                // Ini mencegah file session Anda membengkak dengan data lama.
                unset($_SESSION['flash_message']); 
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>

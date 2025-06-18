document.addEventListener('DOMContentLoaded', function() {
    
    // Dapatkan BASE_URL dari tag <meta> di header.
    // PENTING: Pastikan Anda menambahkan <meta name="base-url" content="<?php echo BASE_URL; ?>"> di dalam <head> di file header.php
    const baseUrlElement = document.querySelector('meta[name="base-url"]');
    if (!baseUrlElement) {
        console.error('BASE_URL meta tag not found!');
        return;
    }
    const baseUrl = baseUrlElement.getAttribute('content');

    // ==========================================================
    // --- LOGIKA 1: FITUR PENCARIAN (GABUNGAN TOGGLE & LIVE) ---
    // ==========================================================
    const searchContainer = document.querySelector('.search-container');
    const searchIconToggle = document.getElementById('searchIconToggle');
    const searchInput = document.getElementById('liveSearchInput'); // Pastikan ID input di HTML adalah ini
    const searchForm = document.getElementById('searchForm');
    const resultsDropdown = document.getElementById('searchResultsDropdown');
    let isSearchVisible = false;

    if (searchContainer && searchIconToggle && searchInput && searchForm && resultsDropdown) {
        
        // --- Event untuk mengklik ikon search ---
        searchIconToggle.addEventListener('click', function(event) {
            event.preventDefault();
            if (!isSearchVisible) {
                // Jika tersembunyi, TAMPILKAN
                searchInput.style.display = 'inline-block';
                setTimeout(() => { 
                    searchInput.style.width = '200px';
                    searchInput.style.opacity = '1';
                }, 10);
                searchInput.focus();
                isSearchVisible = true;
            } else {
                // Jika sudah terlihat dan ada isinya, SUBMIT
                if (searchInput.value.trim() !== '') {
                    searchForm.submit();
                }
            }
        });

        // --- Event untuk live search saat mengetik ---
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length > 1) {
                fetch(`${baseUrl}live_search.php?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        resultsDropdown.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(item => {
                                const link = document.createElement('a');
                                link.href = `${baseUrl}detail_produk.php?product_id=${item.id}`;
                                link.className = 'dropdown-item d-flex align-items-center p-2';
                                link.innerHTML = `
                                    <img src="${item.image}" alt="${item.name}" class="me-3" style="width: 50px; height: 50px; object-fit: cover; border-radius: var(--border-radius-sm);">
                                    <span class="fw-bold text-dark">${item.name}</span>
                                `;
                                resultsDropdown.appendChild(link);
                            });
                            resultsDropdown.style.display = 'block';
                        } else {
                            resultsDropdown.style.display = 'none';
                        }
                    })
                    .catch(error => console.error('Live search fetch error:', error));
            } else {
                resultsDropdown.style.display = 'none';
            }
        });

    } else {
        console.warn('Satu atau lebih elemen pencarian tidak ditemukan.');
    }

    // ==========================================================
    // --- LOGIKA 2: CONTACT FORM MODAL (AJAX) ---
    // ==========================================================
    const contactModalEl = document.getElementById('contactModal');
    if (contactModalEl) {
        const contactModalForm = document.getElementById('contactModalForm');
        const contactModalAlerts = document.getElementById('contactModalAlerts');

        if (contactModalForm && contactModalAlerts) {
            contactModalForm.addEventListener('submit', function(event) {
                event.preventDefault();
                contactModalAlerts.innerHTML = '';
                // ... (sisa dari kode AJAX modal kontak Anda yang sudah benar) ...
            });
            contactModalEl.addEventListener('hidden.bs.modal', function () {
                contactModalAlerts.innerHTML = '';
            });
        }
    }

    // ==========================================================
    // --- LOGIKA 3: KLIK DI LUAR UNTUK MENUTUP SEARCH ---
    // ==========================================================
    document.addEventListener('click', function(event) {
        if (searchContainer && !searchContainer.contains(event.target)) {
            // Sembunyikan dropdown
            if (resultsDropdown) resultsDropdown.style.display = 'none';
            // Sembunyikan juga input search jika sedang terlihat
            if (isSearchVisible) {
                searchInput.style.width = '0';
                searchInput.style.opacity = '0';
                setTimeout(() => {
                    searchInput.style.display = 'none';
                }, 300);
                isSearchVisible = false;
            }
        }
    });

});
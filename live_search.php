<?php
// live_search.php (Versi Super-Debug)

// Tampilkan semua error untuk diagnosis
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db_connect.php';

// Cek apakah BASE_URL terdefinisi
if (!defined('BASE_URL')) {
    die(json_encode(['error' => 'BASE_URL is not defined in db_connect.php']));
}

$conn = connect_db();

// Cek koneksi secara eksplisit
if (!$conn) {
    die(json_encode(['error' => 'Database connection failed. Check credentials in db_connect.php']));
}

$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$results = [];
$debug_info = []; // Array untuk menyimpan info debug

$debug_info['query_received'] = $query;

if (strlen($query) > 1) {
    $search_term = "%" . $query . "%";
    $debug_info['search_term'] = $search_term;
    
    $sql = "SELECT product_id, name, foto_produk, is_available FROM produk WHERE (LOWER(name) LIKE LOWER(?) OR LOWER(deskripsi) LIKE LOWER(?)) LIMIT 6";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        $debug_info['error'] = "Query prepare failed: " . $conn->error;
    } else {
        $stmt->bind_param("ss", $search_term, $search_term);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $debug_info['num_rows_found'] = $result->num_rows; // Info paling penting

        while ($row = $result->fetch_assoc()) {
            // Cek apakah is_available adalah 1
            if($row['is_available'] == 1) {
                $results[] = [
                    'id' => $row['product_id'],
                    'name' => htmlspecialchars($row['name']),
                    'image' => BASE_URL . htmlspecialchars($row['foto_produk'] ?? 'assets/img/placeholder.jpg') 
                ];
            } else {
                 // Jika ditemukan tapi tidak tersedia, catat untuk debug
                 $debug_info['unavailable_products_found'][] = $row['name'];
            }
        }
        $stmt->close();
    }
}

$conn->close();

// Set header dan kembalikan hasilnya
header('Content-Type: application/json');

// Jika Anda ingin melihat info debug, hapus komentar pada baris di bawah ini
// die(json_encode(['debug' => $debug_info, 'results' => $results]));

// Output normal
echo json_encode($results);
?>
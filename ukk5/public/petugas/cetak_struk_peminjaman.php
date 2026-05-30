<?php
error_reporting(E_ERROR | E_PARSE);
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'petugas') {
    header("Location: ../login.php");
    exit();
}

require_once '../../src/models/Peminjaman.php';
require_once '../../src/models/User.php';
require_once '../../src/models/Bukti.php';
require_once '../../fpdf.php';

$peminjamanModel = new Peminjaman();
$userModel = new User();
$buktiModel = new Bukti();

// Check if we have bukti ID or peminjaman ID
$id_bukti = $_GET['bukti_id'] ?? null;
$id_peminjaman = $_GET['id'] ?? null;

if (!$id_bukti && !$id_peminjaman) {
    die('ID bukti atau ID peminjaman tidak ditemukan');
}

// If we have bukti ID, get the associated peminjaman ID
if ($id_bukti) {
    $bukti = $buktiModel->getBuktiById($id_bukti);
    if (!$bukti) {
        die('Bukti tidak ditemukan');
    }

    // If bukti has id_peminjaman, use it
    if (!empty($bukti['id_peminjaman'])) {
        $id_peminjaman = $bukti['id_peminjaman'];
    } else {
        // If bukti doesn't have id_peminjaman, try to find a peminjaman for this user
        $peminjamanModel = new Peminjaman();
        $userPeminjamanStmt = $peminjamanModel->getPeminjamanByUser($bukti['id_user']);
        $userPeminjamanStmt->execute();
        $allUserPeminjaman = $userPeminjamanStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($allUserPeminjaman)) {
            // Use the most recent peminjaman for this user (any status)
            $id_peminjaman = $allUserPeminjaman[0]['id_peminjaman'];

            // Update the bukti record with the found peminjaman ID
            $buktiModel->updateBukti($id_bukti, [
                'id_peminjaman' => $id_peminjaman,
                'nama_file' => $bukti['nama_file'],
                'keterangan' => $bukti['keterangan'],
                'tipe_bukti' => $bukti['tipe_bukti']
            ]);
        } else {
            die('Tidak ada data peminjaman yang terkait dengan user ID ' . $bukti['id_user']);
        }
    }
}

// Get peminjaman details
$peminjaman = $peminjamanModel->getPeminjamanById($id_peminjaman);
if (!$peminjaman) {
    die('Data peminjaman tidak ditemukan');
}

// Get user details
$user = $userModel->getUserById($peminjaman['id_user']);
if (!$user) {
    die('Data user tidak ditemukan');
}

// Get officer details
$officer = $userModel->getUserById($_SESSION['user_id']);
if (!$officer) {
    die('Data petugas tidak ditemukan');
}

// Create PDF
$pdf = new FPDF('P', 'mm', 'A4');

// Function to generate page content
function generatePageContent($pdf, $peminjaman, $user, $officer, $label) {
    // Label for the page
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, $label, 0, 1, 'C');
    $pdf->Ln(5);

    // Set font
    $pdf->SetFont('Arial', 'B', 16);

    // Header
    $pdf->Cell(0, 10, 'STRUK PEMINJAMAN ALAT', 0, 1, 'C');
    $pdf->Ln(5);

    // Line separator
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(5);

    // Company info
    $pdf->SetFont('Arial', '', 12);

    // Date and time
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, 'Tanggal Cetak: ' . date('d/m/Y H:i:s'), 0, 1, 'R');
    $pdf->Ln(5);

    // Peminjaman details
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, 'DETAIL PEMINJAMAN', 0, 1, 'L');
    $pdf->Ln(3);

    // Create table-like structure
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(45, 8, 'ID Peminjaman', 1, 0, 'L');
    $pdf->Cell(70, 8, 'Nama Peminjam', 1, 0, 'L');
    $pdf->Cell(30, 8, 'ID Peminjam', 1, 0, 'L');
    $pdf->Cell(45, 8, 'Nama Alat', 1, 1, 'L');

    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(45, 8, '#' . $peminjaman['id_peminjaman'], 1, 0, 'L');
    $pdf->Cell(70, 8, htmlspecialchars($peminjaman['nama_peminjam']), 1, 0, 'L');
    $pdf->Cell(30, 8, htmlspecialchars($user['username'] ?? '-'), 1, 0, 'L');
    $pdf->Cell(45, 8, htmlspecialchars($peminjaman['nama_alat']), 1, 1, 'L');

    $pdf->Ln(5);

    // Second row for quantity and dates
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 8, 'Jumlah', 1, 0, 'L');
    $pdf->Cell(50, 8, 'Tanggal Pinjam', 1, 0, 'L');
    $pdf->Cell(50, 8, 'Tanggal Kembali', 1, 1, 'L');

    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(40, 8, $peminjaman['jumlah'] . ' unit', 1, 0, 'L');
    $pdf->Cell(50, 8, date('d/m/Y', strtotime($peminjaman['tanggal_peminjaman'])), 1, 0, 'L');
    $pdf->Cell(50, 8, date('d/m/Y', strtotime($peminjaman['tanggal_pengembalian'])), 1, 1, 'L');

    if (!empty($peminjaman['keterangan'])) {
        $pdf->Ln(3);
        $pdf->Cell(50, 6, 'Keterangan:', 0, 0);
        $pdf->MultiCell(0, 6, htmlspecialchars($peminjaman['keterangan']));
    }

    // Officer name
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 6, 'Petugas:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, htmlspecialchars($officer['nama']), 0, 1);

    // Footer
    $pdf->Ln(15);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 4, 'Dokumen ini dicetak secara otomatis oleh sistem pada ' . date('d/m/Y H:i:s'), 0, 1, 'C');
    $pdf->Cell(0, 4, 'Sistem Peminjaman Alat - Petugas Panel', 0, 1, 'C');
}

// First page: Officer Copy
$pdf->AddPage();
generatePageContent($pdf, $peminjaman, $user, $officer, 'Lembar Petugas');

// Second page: Customer Copy
$pdf->AddPage();
generatePageContent($pdf, $peminjaman, $user, $officer, 'Lembar Customer');

// Check if preview mode
$preview = isset($_GET['preview']) && $_GET['preview'] == '1';

if ($preview) {
    // Show preview page with download option
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Preview Struk Peminjaman</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background-color: #f8f9fa; }
            .preview-container { max-width: 800px; margin: 20px auto; }
        </style>
    </head>
    <body>
        <div class="preview-container">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <a href="bukti.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Kembali
                        </a>
                        <h5 class="mb-0 d-inline ms-3">Preview Struk Peminjaman</h5>
                    </div>
                    <div>
                        <a href="?id=<?php echo $id_peminjaman; ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-download me-1"></i>Download PDF
                        </a>
                        <button onclick="window.print()" class="btn btn-primary btn-sm">
                            <i class="fas fa-print me-1"></i>Print
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <iframe src="data:application/pdf;base64,<?php echo base64_encode($pdf->Output('', 'S')); ?>"
                            width="100%" height="600px" style="border: none;"></iframe>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
} else {
    // Download PDF
    $pdf->Output('D', 'struk_peminjaman_' . $peminjaman['id_peminjaman'] . '.pdf');
}
?>

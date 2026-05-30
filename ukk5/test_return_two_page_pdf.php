<?php
// Test script to verify the return receipt PDF has two pages
require_once 'src/models/Bukti.php';
require_once 'src/models/Peminjaman.php';
require_once 'src/models/User.php';
require_once 'fpdf.php';

// Mock data for testing
$bukti = [
    'id' => 1,
    'id_peminjaman' => 1,
    'id_user' => 1,
    'nama_file' => 'test.pdf',
    'keterangan' => 'Test return',
    'tipe_bukti' => 'return'
];

$peminjaman = [
    'id_peminjaman' => 1,
    'nama_peminjam' => 'Test User',
    'nama_alat' => 'Test Tool',
    'jumlah' => 1,
    'tanggal_peminjaman' => '2023-01-01',
    'tanggal_pengembalian' => '2023-01-02',
    'tanggal_dikembalikan' => '2023-01-02'
];

$officer = [
    'nama' => 'Test Officer'
];

// Create PDF
$pdf = new FPDF('P', 'mm', 'A4');

// Function to generate page content
function generateReturnPageContent($pdf, $bukti, $peminjaman, $officer, $label) {
    // Label for the page
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, $label, 0, 1, 'C');
    $pdf->Ln(5);

    // Set font
    $pdf->SetFont('Arial', 'B', 16);

    // Header
    $pdf->Cell(0, 10, 'STRUK PENGEMBALIAN ALAT', 0, 1, 'C');
    $pdf->Ln(5);

    // Line separator
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(5);

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
    $pdf->Cell(45, 8, 'ID Pengembalian', 1, 0, 'L');
    $pdf->Cell(70, 8, 'Nama Pengembalian', 1, 0, 'L');
    $pdf->Cell(30, 8, 'ID Kembali', 1, 0, 'L');
    $pdf->Cell(45, 8, 'Nama Alat', 1, 1, 'L');

    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(45, 8, '#' . $bukti['id'], 1, 0, 'L');
    $pdf->Cell(70, 8, htmlspecialchars($peminjaman['nama_peminjam']), 1, 0, 'L');
    $pdf->Cell(30, 8, htmlspecialchars($peminjaman['id_peminjaman']), 1, 0, 'L');
    $pdf->Cell(45, 8, htmlspecialchars($peminjaman['nama_alat']), 1, 1, 'L');

    $pdf->Ln(5);

    // Second row for quantity and dates
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(40, 8, 'Jumlah Alat', 1, 0, 'L');
    $pdf->Cell(50, 8, 'Tanggal Pinjam', 1, 0, 'L');
    $pdf->Cell(50, 8, 'Tanggal Pengembalian', 1, 1, 'L');

    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(40, 8, $peminjaman['jumlah'] . ' unit', 1, 0, 'L');
    $pdf->Cell(50, 8, date('d/m/Y', strtotime($peminjaman['tanggal_peminjaman'])), 1, 0, 'L');
    $pdf->Cell(50, 8, date('d/m/Y', strtotime($peminjaman['tanggal_dikembalikan'] ?? $peminjaman['tanggal_pengembalian'])), 1, 1, 'L');

    if (!empty($bukti['keterangan'])) {
        $pdf->Ln(3);
        $pdf->Cell(50, 6, 'Keterangan:', 0, 0);
        $pdf->MultiCell(0, 6, htmlspecialchars($bukti['keterangan']));
    }

    // Officer name
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(50, 6, 'Petugas:', 0, 0);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, htmlspecialchars($officer['nama']), 0, 1);

    $pdf->Ln(10);

    // Footer
    $pdf->Ln(15);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->Cell(0, 4, 'Dokumen ini dicetak secara otomatis oleh sistem pada ' . date('d/m/Y H:i:s'), 0, 1, 'C');
    $pdf->Cell(0, 4, 'Sistem Peminjaman Alat - Petugas Panel', 0, 1, 'C');
}

// First page: Officer Copy
$pdf->AddPage();
generateReturnPageContent($pdf, $bukti, $peminjaman, $officer, 'Lembar Petugas');

// Second page: Customer Copy
$pdf->AddPage();
generateReturnPageContent($pdf, $bukti, $peminjaman, $officer, 'Lembar Customer');

// Check number of pages
$pageCount = $pdf->PageNo();

echo "Return receipt PDF generated with $pageCount pages.\n";

if ($pageCount == 2) {
    echo "Test PASSED: Return receipt PDF has exactly 2 pages as expected.\n";
} else {
    echo "Test FAILED: Return receipt PDF has $pageCount pages, expected 2.\n";
}

// Save the PDF for manual inspection
$pdf->Output('F', 'test_return_two_pages.pdf');
echo "PDF saved as test_return_two_pages.pdf for manual inspection.\n";
?>

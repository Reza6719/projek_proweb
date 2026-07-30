<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak!");
}

$result = $conn->query("SELECT * FROM laporan_global ORDER BY periode DESC");

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=laporan_global.xls");
header("Pragma: no-cache");
header("Expires: 0");

echo "Laporan Global Koperasi Siswa\n\n";
echo "Periode\tNeraca (Rp)\tLaba Rugi (Rp)\tRekapitulasi SHU (Rp)\n";

$totalNeraca = 0;
$totalLaba   = 0;
$totalShu    = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $totalNeraca += $row['neraca'];
        $totalLaba   += $row['laba_rugi'];
        $totalShu    += $row['shu'];

        echo htmlspecialchars($row['periode']) . "\t" .
             number_format($row['neraca'], 0, ',', '.') . "\t" .
             number_format($row['laba_rugi'], 0, ',', '.') . "\t" .
             number_format($row['shu'], 0, ',', '.') . "\n";
    }
    echo "TOTAL\t" .
         number_format($totalNeraca, 0, ',', '.') . "\t" .
         number_format($totalLaba, 0, ',', '.') . "\t" .
         number_format($totalShu, 0, ',', '.') . "\n";
} else {
    echo "Belum ada data laporan\n";
}
?>
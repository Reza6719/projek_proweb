<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pengurus') {
    die("Akses ditolak!");
}

// Ambil semua transaksi dengan join ke users
$query = "SELECT t.id, u.username, u.id_siswa, t.jenis, t.jumlah, t.tanggal, t.keterangan 
          FROM transaksi t 
          JOIN users u ON t.user_id = u.id 
          ORDER BY t.tanggal DESC";
$result = $conn->query($query);

$rows = [];
$total_transaksi = 0;
$total_tabungan = 0;
$total_pinjaman = 0;
$total_denda = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
        $total_transaksi += $row['jumlah'];
        if ($row['jenis'] == 'tabungan') $total_tabungan += $row['jumlah'];
        elseif ($row['jenis'] == 'pinjaman') $total_pinjaman += $row['jumlah'];
        elseif ($row['jenis'] == 'denda') $total_denda += $row['jumlah'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - Pengurus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#f0f2f5; font-family:'Poppins','Segoe UI',sans-serif; }
        .sidebar {
            background: linear-gradient(135deg, #0f2b3d 0%, #1a4a6f 100%);
            color: white;
            height: 100vh;
            position: sticky;
            top: 0;
            box-shadow: 2px 0 12px rgba(0,0,0,0.05);
        }
        .sidebar h4 { font-weight:600; letter-spacing:1px; border-bottom:1px solid rgba(255,255,255,0.2); padding-bottom:12px; margin-bottom:20px; }
        .sidebar a {
            display: flex; align-items: center; gap: 12px; color: #e0e7ff; text-decoration: none;
            padding: 12px 16px; margin: 6px 0; border-radius: 12px; transition: all 0.2s ease; font-weight:500;
        }
        .sidebar a i { width: 24px; text-align:center; }
        .sidebar a:hover { background:rgba(255,255,255,0.15); color:white; transform:translateX(5px); }
        .sidebar a.active { background:#0077b6; color:white; box-shadow:0 4px 8px rgba(0,0,0,0.2); }
        .content { padding:2rem 1.5rem; }
        .page-title { font-weight:700; color:#1e2a3e; border-left:5px solid #0077b6; padding-left:15px; margin-bottom:1.8rem; }
        .card { border-radius:28px; border:none; box-shadow:0 20px 35px -10px rgba(0,0,0,0.1); }
        .summary-box { background:linear-gradient(145deg,#0f2b3d,#1a4a6f); border-radius:20px; padding:1rem; text-align:center; color:white; }
        .summary-box h5 { font-size:0.9rem; opacity:0.9; }
        .summary-box p { font-size:1.5rem; font-weight:700; margin:0; }
        .badge-jenis { padding:6px 14px; border-radius:40px; font-size:0.75rem; font-weight:600; }
        .badge-tabungan { background:#d4edda; color:#155724; }
        .badge-pinjaman { background:#fff3cd; color:#856404; }
        .badge-denda { background:#f8d7da; color:#721c24; }
        .table-custom { border-radius:20px; overflow:hidden; }
        .table-custom thead th { background:#f8fafc; border-bottom:2px solid #e2e8f0; }
        footer { text-align:center; margin-top:2rem; color:#7f8c8d; font-size:0.8rem; }
        
        /* Desain Header Laporan Khusus PDF */
        #pdfHeader {
            display: none; /* Disembunyikan di tampilan Web */
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px double #1a4a6f;
            color: #1e2a3e;
        }
        #pdfHeader h2 { font-weight: 800; font-size: 28px; margin: 0; color: #0f2b3d; letter-spacing: 1px; }
        #pdfHeader p { margin: 2px 0; font-size: 14px; color: #555; }
        #pdfHeader .report-title { margin-top: 15px; font-weight: 700; font-size: 18px; color: #0077b6; text-transform: uppercase; }

        @media (max-width:768px) { .sidebar { height:auto; position:relative; } .content { padding:1rem; } }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0">
        <nav class="col-md-2 sidebar p-3">
            <h4><i class="fas fa-hand-holding-usd me-2"></i> Koperasi</h4>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="input_tabungan.php"><i class="fas fa-coins"></i> Input Tabungan</a>
            <a href="proses_pinjaman.php"><i class="fas fa-file-invoice-dollar"></i> Proses Pinjaman</a>
            <a href="hitung_denda.php"><i class="fas fa-clock"></i> Hitung Denda</a>
            <a href="riwayat_transaksi.php" class="active"><i class="fas fa-history"></i> Riwayat Transaksi</a>
            <a href="profile.php"><i class="fas fa-user-circle"></i> Profil</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Pengaturan</a>
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
        
        <main class="col-md-10 content">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                <h2 class="page-title"><i class="fas fa-history me-2"></i> Riwayat Transaksi</h2>
                <div class="text-muted"><i class="fas fa-calendar-alt me-1"></i> <?= date('d F Y') ?></div>
            </div>

            <!-- Area yang akan di-export ke PDF -->
            <div id="printArea">
                <!-- Desain Kop Surat & Judul (Hanya muncul saat export PDF) -->
                <div id="pdfHeader">
                    <h2><i class="fas fa-hand-holding-usd me-2"></i> KOPERASI SEKOLAH SEJAHTERA</h2>
                    <p>Jl. Pendidikan No. 123, Kota Pelajar, Indonesia 12345</p>
                    <p>Telp: (021) 1234567 | Email: admin@koperasisekolah.sch.id</p>
                    <div class="report-title">LAPORAN RIWAYAT TRANSAKSI</div>
                    <p style="font-style: italic;">Dicetak pada: <?= date('d F Y, H:i') ?> WIB</p>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3"><div class="summary-box"><h5>Total Transaksi</h5><p>Rp <?= number_format($total_transaksi,0,',','.') ?></p></div></div>
                    <div class="col-md-3"><div class="summary-box"><h5>Tabungan</h5><p>Rp <?= number_format($total_tabungan,0,',','.') ?></p></div></div>
                    <div class="col-md-3"><div class="summary-box"><h5>Pinjaman</h5><p>Rp <?= number_format($total_pinjaman,0,',','.') ?></p></div></div>
                    <div class="col-md-3"><div class="summary-box"><h5>Denda</h5><p>Rp <?= number_format($total_denda,0,',','.') ?></p></div></div>
                </div>

                <div class="card p-3 mb-4">
                    <div class="table-responsive">
                        <table id="tabelTransaksi" class="table table-custom table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Siswa</th>
                                    <th>NIS</th>
                                    <th>Jenis</th>
                                    <th>Jumlah</th>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($rows)): ?>
                                    <?php foreach ($rows as $r): ?>
                                        <tr>
                                            <td><?= $r['id'] ?></td>
                                            <td><?= htmlspecialchars($r['username']) ?></td>
                                            <td><?= htmlspecialchars($r['id_siswa']) ?></td>
                                            <td>
                                                <?php if ($r['jenis'] == 'tabungan'): ?>
                                                    <span class="badge-jenis badge-tabungan"><i class="fas fa-arrow-up"></i> Tabungan</span>
                                                <?php elseif ($r['jenis'] == 'pinjaman'): ?>
                                                    <span class="badge-jenis badge-pinjaman"><i class="fas fa-arrow-down"></i> Pinjaman</span>
                                                <?php else: ?>
                                                    <span class="badge-jenis badge-denda"><i class="fas fa-exclamation-triangle"></i> Denda</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>Rp <?= number_format($r['jumlah'],0,',','.') ?></td>
                                            <td><?= date('d-m-Y', strtotime($r['tanggal'])) ?></td>
                                            <td><?= htmlspecialchars($r['keterangan'] ?? '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada transaksi</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> <!-- Akhir printArea -->

            <!-- Tombol Export dipindah ke bawah tabel -->
            <div class="d-flex justify-content-end gap-3 mt-2">
                <button onclick="exportExcel()" class="btn btn-success px-4 rounded-pill shadow-sm">
                    <i class="fas fa-file-excel me-2"></i> Download Excel
                </button>
                <button onclick="exportPDF()" class="btn btn-danger px-4 rounded-pill shadow-sm">
                    <i class="fas fa-file-pdf me-2"></i> Download PDF
                </button>
            </div>

            <footer><i class="fas fa-history"></i> Sistem Informasi Koperasi Sekolah | Semua riwayat transaksi</footer>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Library untuk Export Excel (SheetJS) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<!-- Library untuk Export PDF (html2pdf) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<!-- Script Export -->
<script>
    function exportExcel() {
        let table = document.getElementById("tabelTransaksi");
        let wb = XLSX.utils.table_to_book(table, {sheet: "Riwayat Transaksi"});
        XLSX.writeFile(wb, "Laporan_Riwayat_Transaksi.xlsx");
    }

    function exportPDF() {
        let element = document.getElementById("printArea");
        let header = document.getElementById("pdfHeader");
        
        // Memunculkan header khusus (Kop Surat) sebelum diexport
        header.style.display = "block";
        
        let opt = {
            margin:       0.4,
            filename:     'Laporan_Riwayat_Transaksi.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'in', format: 'a4', orientation: 'landscape' }
        };
        
        // Render PDF, kemudian sembunyikan kembali header setelah selesai
        html2pdf().set(opt).from(element).save().then(function() {
            header.style.display = "none";
        });
    }
</script>
</body>
</html>
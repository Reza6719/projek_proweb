<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pengurus') {
    die("Akses ditolak!");
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$query = $conn->prepare("SELECT username, email, phone, nama_lengkap, alamat, foto FROM users WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$user = $query->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $alamat = trim($_POST['alamat']);

    if (empty($nama_lengkap)) {
        $error = "Nama lengkap harus diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif (!preg_match('/^[0-9]{10,13}$/', $phone)) {
        $error = "Nomor telepon harus 10-13 digit angka.";
    } else {
        $cek = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $cek->bind_param("si", $email, $user_id);
        $cek->execute();
        $cek->store_result();
        if ($cek->num_rows > 0) {
            $error = "Email sudah digunakan oleh pengguna lain.";
        } else {
            $foto_name = $user['foto'];

            if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/TA/uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($ext, $allowed)) {
                    $error = "Format foto tidak diizinkan.";
                } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                    $error = "Ukuran foto maksimal 2MB.";
                } else {
                    $new_foto = 'pengurus_' . $user_id . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $new_foto)) {
                        if (!empty($user['foto']) && file_exists($upload_dir . $user['foto'])) {
                            unlink($upload_dir . $user['foto']);
                        }
                        $foto_name = $new_foto;
                    } else {
                        $error = "Gagal mengupload foto. Coba lagi.";
                    }
                }
            }

            if (empty($error)) {
                $update = $conn->prepare("UPDATE users SET nama_lengkap = ?, email = ?, phone = ?, alamat = ?, foto = ? WHERE id = ?");
                $update->bind_param("sssssi", $nama_lengkap, $email, $phone, $alamat, $foto_name, $user_id);
                if ($update->execute()) {
                    header("Location: profile.php?success=1");
                    exit;
                } else {
                    $error = "❌ Gagal memperbarui profil: " . $conn->error;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil Pengurus</title>
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
        .content { padding: 2rem 1.5rem; }
        .page-title { font-weight:700; color:#1e2a3e; border-left:5px solid #0077b6; padding-left:15px; margin-bottom:1.8rem; }
        .card-form {
            border: none; border-radius: 28px; background: white;
            box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1);
            padding: 2rem; max-width:700px; margin:0 auto;
        }
        .preview-img { width:120px; height:120px; border-radius:50%; object-fit:cover; margin-top:10px; border:3px solid #0077b6; }
        footer { text-align:center; margin-top:2rem; color:#7f8c8d; font-size:0.8rem; }
        @media (max-width:768px) { .sidebar { height:auto; position:relative; } .content { padding:1rem; } }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- SIDEBAR -->
        <nav class="col-md-2 sidebar p-3">
            <h4><i class="fas fa-hand-holding-usd me-2"></i> Koperasi</h4>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="input_tabungan.php"><i class="fas fa-coins"></i> Input Tabungan</a>
            <a href="proses_pinjaman.php"><i class="fas fa-file-invoice-dollar"></i> Proses Pinjaman</a>
            <a href="hitung_denda.php"><i class="fas fa-clock"></i> Hitung Denda</a>
            <a href="rekap_transaksi.php"><i class="fas fa-chart-line"></i> Rekap Transaksi</a>
            <a href="riwayat_transaksi.php"><i class="fas fa-history"></i> Riwayat Transaksi</a>
            <a href="profile.php"><i class="fas fa-user-circle"></i> Profil</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Pengaturan</a>
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>

        <!-- KONTEN -->
        <main class="col-md-10 content">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                <h2 class="page-title"><i class="fas fa-user-edit me-2"></i> Edit Profil Pengurus</h2>
                <div class="text-muted"><i class="fas fa-calendar-alt me-1"></i> <?= date('d F Y') ?></div>
            </div>

            <div class="card-form">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($user['nama_lengkap'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nomor Telepon</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" class="form-control" rows="3"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Foto Profil</label>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                        <?php
                        $foto_path = $_SERVER['DOCUMENT_ROOT'] . '/TA/uploads/' . ($user['foto'] ?? '');
                        if (!empty($user['foto']) && file_exists($foto_path)):
                        ?>
                            <div><img src="../../uploads/<?= htmlspecialchars($user['foto']) ?>?v=<?= time() ?>" class="preview-img"></div>
                        <?php else: ?>
                            <div><span class="text-muted">Belum ada foto</span></div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Simpan Perubahan</button>
                </form>
                <div class="text-center mt-3">
                    <a href="profile.php" class="btn btn-link">Kembali ke Profil</a>
                </div>
            </div>

            <footer><i class="fas fa-user-edit"></i> Sistem Informasi Koperasi Sekolah | Edit Profil</footer>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
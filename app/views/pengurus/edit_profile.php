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
            $foto_name = $user['foto']; // default foto lama

            // Proses upload foto
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/TA/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($ext, $allowed)) {
                    $error = "Format foto tidak diizinkan (JPG, PNG, GIF, WEBP).";
                } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
                    $error = "Ukuran foto maksimal 2MB.";
                } else {
                    $new_foto = 'pengurus_' . $user_id . '_' . time() . '.' . $ext;
                    $target_file = $upload_dir . $new_foto;
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                        // Hapus foto lama jika ada
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
        body { background:#f0f2f5; font-family:'Poppins',sans-serif; }
        .card-form { border: none; border-radius: 28px; background: white; box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1); padding: 2rem; max-width:700px; margin:1rem auto; }
        .preview-img { width:120px; height:120px; border-radius:50%; object-fit:cover; margin-top:10px; border:3px solid #0077b6; }
        .btn-back { border-radius:40px; padding:8px 20px; }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mt-4">
        <a href="dashboard.php" class="btn btn-outline-secondary btn-back"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
        <div class="text-muted"><?= date('d F Y') ?></div>
    </div>
    <div class="card-form">
        <h2><i class="fas fa-user-edit"></i> Edit Profil Pengurus</h2>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3"><label>Username</label><input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled></div>
            <div class="mb-3"><label>Nama Lengkap</label><input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($user['nama_lengkap'] ?? '') ?>" required></div>
            <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required></div>
            <div class="mb-3"><label>Nomor Telepon</label><input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required></div>
            <div class="mb-3"><label>Alamat</label><textarea name="alamat" class="form-control" rows="3"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea></div>
            <div class="mb-3">
                <label>Foto Profil</label>
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
        <div class="text-center mt-3"><a href="profile.php" class="btn btn-link">Kembali ke Profil</a></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
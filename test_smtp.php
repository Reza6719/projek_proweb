<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'live.smtp.mailtrap.io';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'api';
    $mail->Password   = '3a6a54dede363169e16382ab7783fcf0'; // token Anda
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->setFrom('noreply@koperasisekolah.com', 'Test');
    $mail->addAddress('test@example.com', 'Test');
    $mail->Subject = 'Test SMTP';
    $mail->Body    = 'Testing SMTP connection.';
    $mail->send();
    echo "SUCCESS: Koneksi SMTP berhasil.";
} catch (Exception $e) {
    echo "ERROR: " . $mail->ErrorInfo;
}
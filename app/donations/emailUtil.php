<?php
// Email notification for new donations
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../../assets/PHPMailer/src/Exception.php';
require '../../assets/PHPMailer/src/PHPMailer.php';
require '../../assets/PHPMailer/src/SMTP.php';

function sendDonationNotification($donorName, $amount, $adminEmail) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com'; // Set your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'your@email.com';
        $mail->Password = 'yourpassword';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom('no-reply@yourdomain.com', 'Community Service');
        $mail->addAddress($adminEmail);
        $mail->Subject = 'New Donation Received';
        $mail->Body = "A new donation has been received from $donorName. Amount: $$amount.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
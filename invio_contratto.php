<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
session_start();

// Verifica che il PDF esista
$pdf_file_path = $_SESSION['pdf_file_path'] ?? null;
if (!$pdf_file_path || !file_exists($pdf_file_path)) {
    die("Errore: Il file PDF non è stato trovato.");
}

// Verifica se è stata inviata l'email del cliente
if (isset($_POST['email_cliente'])) {
    $email_cliente = $_POST['email_cliente'];

    // Configurazione per l'invio della mail
    $mail = new PHPMailer(true);
    try {
        // Configura il server SMTP
        $mail->isSMTP();
        $mail->Host = 'smtps.aruba.it'; // Server SMTP di Aruba
        $mail->SMTPAuth = true;
        $mail->Username = 'gestionale@gestionale-moodon.it'; // Il tuo indirizzo email completo
        $mail->Password = 'Gestion2024ale!'; // La password dell'email
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        // Mittente e destinatario
        $mail->setFrom('gestionale@gestionale-moodon.it', 'Gestionale Moodon');
        $mail->addAddress($email_cliente); // Email del cliente

        // Contenuto dell'email
        $mail->isHTML(true);
        $mail->Subject = 'Il tuo contratto firmato';
        $mail->Body = 'In allegato trovi il contratto PDF firmato.';
        $mail->addAttachment($pdf_file_path); // Aggiungi il file PDF come allegato

        // Invia l'email
        $mail->send();
        echo "Email inviata con successo a $email_cliente.";

    } catch (Exception $e) {
        echo "Errore durante l'invio dell'email: {$mail->ErrorInfo}";
    }
}
?>
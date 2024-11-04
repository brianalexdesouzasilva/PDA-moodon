<?php
// Inizio del file PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Logging degli errori
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

session_start();
require 'vendor/autoload.php';
require_once 'db_connection.php';

use setasign\Fpdi\Fpdi;

if (!isset($_SESSION['access_token'])) {
    header("Location: login.php");
    exit();
}

// Recupera i dati del cliente dalla sessione
$cliente = $_SESSION['cliente'] ?? null;
if (!$cliente) {
    die("Dati del cliente mancanti.");
}

// Recupera i dati dal processo
$processo_id = $_SESSION['processo_id'];
$sql = "SELECT dati_step FROM processi WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $processo_id);
$stmt->execute();
$stmt->bind_result($dati_step_json);
$stmt->fetch();
$stmt->close();

$dati_step = json_decode($dati_step_json, true);
$offerta_id = $dati_step['offerta_id'] ?? null;

// Recupera il nome del PDF dall'offerta
$sql = "SELECT po.nome_file FROM offerte o JOIN pdf_offerte po ON o.id = po.offerta_id WHERE o.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $offerta_id);
$stmt->execute();
$stmt->bind_result($pdf_file);
$stmt->fetch();
$stmt->close();

if (!$pdf_file) {
    die("PDF non trovato per l'offerta selezionata.");
}

// Aggiungi la firma
$signature_data = $_POST['signature'] ?? null;
if (!$signature_data) {
    die("Firma mancante.");
}

// Salva la firma
if (preg_match('/data:image\/(\w+);base64,/', $signature_data, $type)) {
    $data = substr($signature_data, strpos($signature_data, ',') + 1);
    $type = strtolower($type[1]);
    $data = base64_decode($data);
    $signature_filename = 'signature_' . time() . '.' . $type;
    $signature_path = __DIR__ . '/uploads/' . $signature_filename;
    file_put_contents($signature_path, $data);
}

// Carica il PDF di base
$pdf_path = __DIR__ . '/uploads/' . $pdf_file;

$pdf = new FPDI();
$pageCount = $pdf->setSourceFile($pdf_path);
for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
    $tplId = $pdf->importPage($pageNo);
    $pdf->AddPage();
    $pdf->useTemplate($tplId);
}

// Aggiungi la firma nel PDF
if ($signature_path) {
    $pdf->Image($signature_path, 130, 202, 20, 5, 'PNG'); // Posizione e dimensione della firma
}

// Salva il PDF
$output_pdf = 'contratto_' . time() . '.pdf';
$pdf->Output(__DIR__ . '/uploads/' . $output_pdf, 'F');

// Salva il percorso del PDF nella sessione
$_SESSION['pdf_file_path'] = __DIR__ . '/uploads/' . $output_pdf;

// Rimuovi il file della firma temporanea
if (file_exists($signature_path)) {
    unlink($signature_path);
}

// Reindirizza a step20.php
header("Location: step20.php");
exit();
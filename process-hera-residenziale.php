<?php
// Inizio del file PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Logging degli errori
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

session_start();

// Includi le librerie necessarie
require 'vendor/autoload.php';
require_once 'db_connection.php';

use setasign\Fpdi\Fpdi;

function formatString($str) {
    return ucfirst(strtolower($str));
}

// Verifica se l'utente è autenticato
if (!isset($_SESSION['access_token'])) {
    error_log("Utente non autenticato. Reindirizzamento a login.php.");
    header("Location: login.php");
    exit;
}

// Recupera i dati del cliente dalla sessione
$cliente = $_SESSION['cliente'] ?? null;
if (!$cliente) {
    die("Dati del cliente mancanti.");
}

// Recupera i dati dal database
$cf_piva = $cliente['cf_piva'];

$sql = "SELECT * FROM dati_tecnici_energia WHERE cf_piva = ? ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $cf_piva);
$stmt->execute();
$result = $stmt->get_result();
$dati_tecnici = $result->fetch_assoc();

if (!$dati_tecnici) {
    die("Dati tecnici non trovati nel database per cf_piva: $cf_piva");
}

// Percorsi ai file caricati
$front_image_file = $dati_tecnici['documento_fronte'];
$back_image_file = $dati_tecnici['documento_retro'];
$codice_fiscale_file = $dati_tecnici['documento_cf'];
$partita_iva_file = $dati_tecnici['documento_piva'] ?? null; // Può essere null

// Recupera altri dati del cliente
$nome = formatString($cliente['nome'] ?? 'N/A');
$cognome = formatString($cliente['cognome'] ?? 'N/A');
$data_nascita = $cliente['data_nascita'] ?? 'N/A';
$luogo_nascita = formatString($cliente['luogo_nascita'] ?? 'N/A');
$cf_piva_value = strtoupper($cliente['cf_piva'] ?? 'N/A');
$indirizzo_residenza = formatString($cliente['indirizzo_residenza'] ?? 'N/A');
$comune = formatString($cliente['comune'] ?? 'N/A');
$provincia = strtoupper($cliente['provincia'] ?? 'N/A');
$cap = $cliente['cap'] ?? 'N/A';
$telefono = $cliente['telefono'] ?? 'N/A';
$cellulare = $cliente['cellulare'] ?? 'N/A';
$email = strtolower($cliente['email'] ?? 'N/A');

$tipo_documento = $dati_tecnici['tipo_documento'] ?? 'N/A';
$emesso_da = $dati_tecnici['emesso_da'] ?? 'N/A';
$data_emissione = $dati_tecnici['data_emissione'] ?? 'N/A';
$pod = $dati_tecnici['pod'] ?? 'N/A';  // Aggiunto POD
$pdr = $dati_tecnici['pdr'] ?? 'N/A';  // Aggiunto PDR

// Formattazione dei campi
$cf_piva_spaziato = implode('  ', str_split($cf_piva_value)); 
$provincia_spaziato = implode('  ', str_split($provincia));
$cap_spaziato = implode('  ', str_split($cap));

// Carica il PDF di base
$pdf_file = __DIR__ . "/uploads/hera-residenziale.pdf";

if (!file_exists($pdf_file)) {
    die("File PDF di base non trovato.");
}

try {
    class PDF extends FPDI {
        function addData($data) {
            $this->SetFont('Arial', '', 9.8);
            $this->SetXY(32, 49); // Cognome
            $this->Cell(0, 10, $data['cognome'], 0, 1, 'L');
            $this->SetXY(122, 49); // Nome
            $this->Cell(0, 10, $data['nome'], 0, 1, 'L');
            // Altri campi
            $this->SetXY(32, 54); // Luogo di nascita
            $this->Cell(0, 10, $data['luogo_nascita'], 0, 1, 'L');
            $this->SetXY(60, 54); // Data di nascita
            $this->Cell(0, 10, $data['data_nascita'], 0, 1, 'L');
            $this->SetXY(130, 54); // Codice fiscale/PIVA
            $this->Cell(0, 10, $data['cf_piva_spaziato'], 0, 1, 'L');
            $this->SetXY(43, 64); // Indirizzo residenza
            $this->Cell(0, 10, $data['indirizzo_residenza'], 0, 1, 'L');
            $this->SetXY(157, 64); // Provincia
            $this->Cell(0, 10, $data['provincia_spaziato'], 0, 1, 'L');
            $this->SetXY(175, 64); // CAP
            $this->Cell(0, 10, $data['cap_spaziato'], 0, 1, 'L');
            $this->SetXY(24, 69); // Telefono
            $this->Cell(0, 10, $data['telefono'], 0, 1, 'L');
            $this->SetXY(52, 69); // Cellulare
            $this->Cell(0, 10, $data['cellulare'], 0, 1, 'L');
            $this->SetXY(118, 64); // Comune
            $this->Cell(0, 10, $data['comune'], 0, 1, 'L');
            $this->SetXY(92, 69); // Email
            $this->Cell(0, 10, $data['email'], 0, 1, 'L');
            // Campi per il documento identificativo
            $this->SetXY(40, 59); // Tipo e n° documento
            $this->Cell(0, 10, $data['tipo_documento'], 0, 1, 'L');
            $this->SetXY(127, 59); // Emesso da
            $this->Cell(0, 10, $data['emesso_da'], 0, 1, 'L');
            $this->SetXY(175, 59); // Data emissione
            $this->Cell(0, 10, $data['data_emissione'], 0, 1, 'L');
            // Aggiungi i campi POD e PDR
            $this->SetXY(40, 64); // POD
            $this->Cell(0, 10, $data['pod'], 0, 1, 'L');
            $this->SetXY(40, 69); // PDR
            $this->Cell(0, 10, $data['pdr'], 0, 1, 'L');
        }

        function addDocumentImages($front_image_file, $back_image_file, $codice_fiscale_file, $partita_iva_file) {
            if ($front_image_file && file_exists($front_image_file)) {
                $this->AddPage();
                $this->Image($front_image_file, 10, 10, 190);
            }
            if ($back_image_file && file_exists($back_image_file)) {
                $this->AddPage();
                $this->Image($back_image_file, 10, 10, 190);
            }
            if ($codice_fiscale_file && file_exists($codice_fiscale_file)) {
                $this->AddPage();
                $this->Image($codice_fiscale_file, 10, 10, 190);
            }
            if ($partita_iva_file && file_exists($partita_iva_file)) {
                $this->AddPage();
                $this->Image($partita_iva_file, 10, 10, 190);
            }
        }
    }

    $pdf = new PDF();
    $pageCount = $pdf->setSourceFile($pdf_file);

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $tplId = $pdf->importPage($pageNo);
        $pdf->AddPage();
        $pdf->useTemplate($tplId);

        if ($pageNo == 1) {
            $data = [
                               'nome' => $nome,
                'cognome' => $cognome,
                'data_nascita' => $formatted_date_nascita,
                'luogo_nascita' => $luogo_nascita,
                'cf_piva_spaziato' => $cf_piva_spaziato,
                'indirizzo_residenza' => $indirizzo_residenza,
                'comune' => $comune,
                'provincia_spaziato' => $provincia_spaziato,
                'cap_spaziato' => $cap_spaziato,
                'telefono' => $telefono,
                'cellulare' => $cellulare,
                'email' => $email,
                'tipo_documento' => $tipo_documento,
                'emesso_da' => $emesso_da,
                'data_emissione' => $data_emissione,
                'pod' => $pod,
                'pdr' => $pdr
            ];
            $pdf->addData($data);
        }
    }

    // Aggiungi le immagini dei documenti caricati
    $pdf->addDocumentImages($front_image_file, $back_image_file, $codice_fiscale_file, $partita_iva_file);

    // Genera e salva il PDF
    $pdf_output = 'contratto_compilato_' . time() . '.pdf';
    $pdf_output_path = __DIR__ . '/uploads/' . $pdf_output;
    $pdf->Output($pdf_output_path, 'F');  // Salva il file nel server

    // Memorizza il percorso del PDF generato nella sessione per gli step successivi
    $_SESSION['pdf_file_path'] = $pdf_output_path;

    // Termina lo script dopo aver salvato il PDF
    exit;
} catch (Exception $e) {
    error_log('Errore durante la generazione del PDF: ' . $e->getMessage());
    die('Si è verificato un errore durante la generazione del PDF.');
}
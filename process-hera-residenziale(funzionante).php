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
use Intervention\Image\ImageManagerStatic as Image;

function formatString($str) {
    return ucfirst(strtolower($str));
}

// Verifica se l'utente è autenticato
if (!isset($_SESSION['access_token'])) {
    error_log("Utente non autenticato. Reindirizzamento a login.php.");
    header("Location: login.php");
    exit;
}

error_log("Utente autenticato. Procedo con la generazione del PDF.");

// Recupera i dati del cliente dalla sessione
$cliente = $_SESSION['cliente'] ?? null;
if (!$cliente) {
    die("Dati del cliente mancanti.");
}

error_log("Dati del cliente: " . print_r($cliente, true));

// Recupera i dati dal database
$cf_piva = $cliente['cf_piva'];  // Mantieni il codice fiscale

$sql = "SELECT * FROM dati_tecnici_energia WHERE cf_piva = ? ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Errore nella preparazione dello statement SQL: " . $conn->error);
    die("Errore nel recupero dei dati.");
}
$stmt->bind_param('s', $cf_piva);
$stmt->execute();
$result = $stmt->get_result();
$dati_tecnici = $result->fetch_assoc();

if (!$dati_tecnici) {
    error_log("Dati tecnici non trovati nel database per cf_piva: $cf_piva");
    die("Dati tecnici non trovati.");
}

error_log("Dati tecnici: " . print_r($dati_tecnici, true));

// Percorsi ai file caricati
$front_image_file = $dati_tecnici['documento_fronte'];
$back_image_file = $dati_tecnici['documento_retro'];
$codice_fiscale_file = $dati_tecnici['documento_cf'];
$partita_iva_file = $dati_tecnici['documento_piva'] ?? null; // Può essere null

error_log("Percorsi dei file:");
error_log("Documento fronte: $front_image_file");
error_log("Documento retro: $back_image_file");
error_log("Documento Codice Fiscale: $codice_fiscale_file");
error_log("Documento Partita IVA: $partita_iva_file");

// Recupera gli altri dati
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

error_log("Dati personali e del documento recuperati.");

// Formattazione dei campi
$cf_piva_spaziato = implode('  ', str_split($cf_piva_value)); 
$provincia_spaziato = implode('  ', str_split($provincia));
$cap_spaziato = implode('  ', str_split($cap));

$date = DateTime::createFromFormat('Y-m-d', $data_nascita);
$formatted_date_nascita = $date ? $date->format('d/m/Y') : $data_nascita;

error_log("Formattazione dei dati completata.");

// Carica il PDF di base
$pdf_file = __DIR__ . "/uploads/hera-residenziale.pdf";

if (!file_exists($pdf_file)) {
    error_log("File PDF di base non trovato: $pdf_file");
    die("File PDF di base non trovato.");
} else {
    error_log("File PDF di base trovato: $pdf_file");
}



try {
    class PDF extends FPDI {
        function addData($data) {
            error_log("Inizio dell'aggiunta dei dati al PDF.");
            error_log("Dati ricevuti: " . print_r($data, true));
            $this->SetFont('Arial', '', 9.8);
            // Posizionamento dei dati nel PDF
            $this->SetXY(32, 49); // Cognome
            $this->Cell(0, 10, $data['cognome'], 0, 1, 'L');
            $this->SetXY(122, 49); // Nome
            $this->Cell(0, 10, $data['nome'], 0, 1, 'L');
            $this->SetXY(26, 54); // Luogo di nascita
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
            $this->SetFont('Arial', '', 9); // Font ridotto per telefono e cellulare
            $this->SetXY(24, 69); // Telefono
             $this->Cell(0, 10, $data['telefono'], 0, 1, 'L');
            $this->SetXY(52, 69); // Cellulare
            $this->Cell(0, 10, $data['cellulare'], 0, 1, 'L');
            $this->SetFont('Arial', '', 7.5); // Font ridotto per comune
            $this->SetXY(118, 64); // Comune
            $this->Cell(0, 10, $data['comune'], 0, 1, 'L');
            $this->SetFont('Arial', '', 9.8); // Font normale per email
            $this->SetXY(92, 69); // Email
            $this->Cell(0, 10, $data['email'], 0, 1, 'L');
            // Campi per il documento identificativo
            $this->SetXY(40, 59); // Tipo e n° documento
            $this->Cell(0, 10, $data['tipo_documento'], 0, 1, 'L');
            $this->SetXY(127, 59); // Emesso da
            $this->Cell(0, 10, $data['emesso_da'], 0, 1, 'L');
            $this->SetXY(175, 59); // Data emissione
            $this->Cell(0, 10, $data['data_emissione'], 0, 1, 'L');
        }

        function addDocumentImages($front_image_file, $back_image_file, $codice_fiscale_file, $partita_iva_file) {
            error_log("Aggiunta delle immagini dei documenti al PDF.");
            if ($front_image_file && file_exists($front_image_file)) {
                $this->AddPage();
                $this->Image($front_image_file, 10, 10, 190);
                error_log("Immagine documento fronte aggiunta.");
            } else {
                error_log("Immagine documento fronte non trovata o non specificata.");
            }

            if ($back_image_file && file_exists($back_image_file)) {
                $this->AddPage();
                $this->Image($back_image_file, 10, 10, 190);
                error_log("Immagine documento retro aggiunta.");
            } else {
                error_log("Immagine documento retro non trovata o non specificata.");
            }

            if ($codice_fiscale_file && file_exists($codice_fiscale_file)) {
                $this->AddPage();
                $this->Image($codice_fiscale_file, 10, 10, 190);
                error_log("Immagine documento Codice Fiscale aggiunta.");
            } else {
                error_log("Immagine documento Codice Fiscale non trovata o non specificata.");
            }

            if ($partita_iva_file && file_exists($partita_iva_file)) {
                $this->AddPage();
                $this->Image($partita_iva_file, 10, 10, 190);
                error_log("Immagine documento Partita IVA aggiunta.");
            } else {
                error_log("Immagine documento Partita IVA non trovata o non specificata.");
            }
        }

        // Funzione per aggiungere le firme nella seconda pagina e ripetere i dati
        function addSignaturesAndRepeatData($data, $signature_file) {
            if ($signature_file) {
                error_log("Aggiunta delle firme nella seconda pagina.");
                // Aggiungi una nuova pagina per la firma

                // Ripeti i dati se necessario sulla seconda pagina
                $this->addData($data);

                // Aggiungi la firma nelle posizioni corrette
                $this->Image($signature_file, 130, 202, 20, 5, 'PNG');
                $this->Image($signature_file, 130, 228, 20, 5, 'PNG');
                $this->Image($signature_file, 140, 257, 20, 5, 'PNG');
                $this->Image($signature_file, 130, 282, 20, 5, 'PNG');

                error_log("Firme aggiunte nella seconda pagina.");
            } else {
                error_log("Nessun file di firma fornito per aggiungere al PDF.");
            }
        }
    }

    $pdf = new PDF();
    $pageCount = $pdf->setSourceFile($pdf_file);
    error_log("Numero di pagine nel PDF di base: $pageCount");

    if ($pageCount == 0) {
        error_log("Il PDF di base non contiene pagine.");
        die("Il PDF di base non contiene pagine.");
    }

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $tplId = $pdf->importPage($pageNo);
        if (!$tplId) {
            error_log("Impossibile importare la pagina $pageNo del PDF di base.");
            continue;
        }

        $pdf->AddPage();
        $pdf->useTemplate($tplId);
        error_log("Pagina $pageNo importata e aggiunta al PDF.");

        if ($pageNo == 1) {
            // Aggiungi i dati alla prima pagina del PDF
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
                'data_emissione' => $data_emissione
            ];
            $pdf->addData($data);
        }

        if ($pageNo == 2) {
            // Aggiungi le firme nella seconda pagina
            $pdf->addSignaturesAndRepeatData($data, $signature_path);
        }
    }

    // Aggiungi le immagini dei documenti caricati
    $pdf->addDocumentImages($front_image_file, $back_image_file, $codice_fiscale_file, $partita_iva_file);
    // Genera e invia il PDF
    $pdf_output = 'contratto_compilato_' . time() . '.pdf';

    // Assicurati che non ci sia output prima di questa linea
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $pdf_output . '"');

    $pdf->Output('I', $pdf_output);

    error_log("PDF generato e inviato con successo.");

    exit; // Termina lo script dopo aver inviato il PDF
} catch (Exception $e) {
    error_log('Errore durante la generazione del PDF: ' . $e->getMessage());
    die('Si è verificato un errore durante la generazione del PDF.');
}
?>
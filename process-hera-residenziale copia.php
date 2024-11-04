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
$front_image_file = $dati_tecnici['documento_fronte'] ?? '';
$back_image_file = $dati_tecnici['documento_retro'] ?? '';
$codice_fiscale_file = $dati_tecnici['documento_cf'] ?? '';
$partita_iva_file = $dati_tecnici['documento_piva'] ?? ''; // Può essere null

// Recupera gli altri dati
$nome = formatString($cliente['nome'] ?? '');
$cognome = formatString($cliente['cognome'] ?? '');
$data_nascita = $cliente['data_nascita'] ?? '';
$luogo_nascita = formatString($cliente['luogo_nascita'] ?? '');
$cf_piva_value = strtoupper($cliente['cf_piva'] ?? '');
$indirizzo_residenza = formatString($cliente['indirizzo_residenza'] ?? '');
$comune = formatString($cliente['comune'] ?? '');
$provincia = strtoupper($cliente['provincia'] ?? '');
$cap = $cliente['cap'] ?? '';
$telefono = $cliente['telefono'] ?? '';
$cellulare = $cliente['cellulare'] ?? '';
$email = strtolower($cliente['email'] ?? '');

$tipo_documento = $dati_tecnici['tipo_documento'] ?? '';
$emesso_da = $dati_tecnici['emesso_da'] ?? '';
$data_emissione = $dati_tecnici['data_emissione'] ?? '';

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
}

// Generazione del PDF
try {
    class PDF extends FPDI {
        function addData($data) {
            $this->SetFont('Arial', '', 9.8);

            // Aggiungi i dati personali solo se non vuoti
            if (!empty($data['cognome'])) {
                $this->SetXY(32, 49); // Cognome
                $this->Cell(0, 10, $data['cognome'], 0, 1, 'L');
            }
            if (!empty($data['nome'])) {
                $this->SetXY(122, 49); // Nome
                $this->Cell(0, 10, $data['nome'], 0, 1, 'L');
            }
            if (!empty($data['luogo_nascita'])) {
                $this->SetXY(26, 54); // Luogo di nascita
                $this->Cell(0, 10, $data['luogo_nascita'], 0, 1, 'L');
            }
            if (!empty($data['data_nascita'])) {
                $this->SetXY(60, 54); // Data di nascita
                $this->Cell(0, 10, $data['data_nascita'], 0, 1, 'L');
            }
            if (!empty($data['cf_piva_spaziato'])) {
                $this->SetXY(130, 54); // Codice fiscale/PIVA
                $this->Cell(0, 10, $data['cf_piva_spaziato'], 0, 1, 'L');
            }
            if (!empty($data['indirizzo_residenza'])) {
                $this->SetXY(43, 64); // Indirizzo residenza
                $this->Cell(0, 10, $data['indirizzo_residenza'], 0, 1, 'L');
            }
            if (!empty($data['provincia_spaziato'])) {
                $this->SetXY(157, 64); // Provincia
                $this->Cell(0, 10, $data['provincia_spaziato'], 0, 1, 'L');
            }
            if (!empty($data['cap_spaziato'])) {
                $this->SetXY(175, 64); // CAP
                $this->Cell(0, 10, $data['cap_spaziato'], 0, 1, 'L');
            }
            if (!empty($data['telefono'])) {
                $this->SetFont('Arial', '', 9); // Font ridotto per telefono e cellulare
                $this->SetXY(24, 69); // Telefono
                $this->Cell(0, 10, $data['telefono'], 0, 1, 'L');
            }
            if (!empty($data['cellulare'])) {
                $this->SetXY(52, 69); // Cellulare
                $this->Cell(0, 10, $data['cellulare'], 0, 1, 'L');
            }
            if (!empty($data['comune'])) {
                $this->SetFont('Arial', '', 7.5); // Font ridotto per comune
                $this->SetXY(118, 64); // Comune
                $this->Cell(0, 10, $data['comune'], 0, 1, 'L');
            }
            if (!empty($data['email'])) {
                $this->SetFont('Arial', '', 9.8); // Font normale per email
                $this->SetXY(92, 69); // Email
                $this->Cell(0, 10, $data['email'], 0, 1, 'L');
            }

            // Aggiungi i dati tecnici solo se non vuoti
            if (!empty($data['pod'])) {
                $this->SetXY(40, 74); // POD
                $this->Cell(0, 10, 'POD: ' . $data['pod'], 0, 1, 'L');
            }
            if (!empty($data['pdr'])) {
                $this->SetXY(40, 79); // PDR
                $this->Cell(0, 10, 'PDR: ' . $data['pdr'], 0, 1, 'L');
            }
            if (!empty($data['kw_disponibili'])) {
                $this->SetXY(40, 84); // KW Disponibili
                $this->Cell(0, 10, 'KW Disponibili: ' . $data['kw_disponibili'], 0, 1, 'L');
            }
            if (!empty($data['kw_impegnati'])) {
                $this->SetXY(40, 89); // KW Impegnati
                $this->Cell(0, 10, 'KW Impegnati: ' . $data['kw_impegnati'], 0, 1, 'L');
            }
            if (!empty($data['smc'])) {
                $this->SetXY(40, 94); // SMC
                $this->Cell(0, 10, 'SMC: ' . $data['smc'], 0, 1, 'L');
            }
            if (!empty($data['consumo_annuo_luce'])) {
                $this->SetXY(40, 99); // Consumo Annuo Luce
                $this->Cell(0, 10, 'Consumo Annuo Luce: ' . $data['consumo_annuo_luce'], 0, 1, 'L');
            }
            if (!empty($data['consumo_annuo_gas'])) {
                $this->SetXY(40, 104); // Consumo Annuo Gas
                $this->Cell(0, 10, 'Consumo Annuo Gas: ' . $data['consumo_annuo_gas'], 0, 1, 'L');
            }
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
    }

    // Inizio della generazione del PDF
    $pdf = new PDF();
    $pageCount = $pdf->setSourceFile($pdf_file);
    error_log("Numero di pagine nel PDF di base: $pageCount");

    if ($pageCount == 0) {
        error_log("Il PDF di base non contiene pagine.");
        die("Il PDF di base non contiene pagine.");
    }

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $pdf->AddPage();
        $tplId = $pdf->importPage($pageNo);

        if (!$tplId) {
            error_log("Impossibile importare la pagina $pageNo del PDF di base.");
            continue;
        }

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
                'pod' => $dati_tecnici['pod'] ?? '',
                'pdr' => $dati_tecnici['pdr'] ?? '',
                'kw_disponibili' => $dati_tecnici['KW_disponibili'] ?? '',
                'kw_impegnati' => $dati_tecnici['KW_impegnati'] ?? '',
                'smc' => $dati_tecnici['smc'] ?? '',
                'consumo_annuo_luce' => $dati_tecnici['consumo_annuo_luce'] ?? '',
                'consumo_annuo_gas' => $dati_tecnici['consumo_annuo_gas'] ?? ''
            ];
            $pdf->addData($data);
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
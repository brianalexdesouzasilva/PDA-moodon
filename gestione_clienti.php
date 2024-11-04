<?php
session_start();
require_once 'db_connection.php';

// Abilita la visualizzazione degli errori (rimuovere in produzione)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifica se l'agente è autenticato
if (!isset($_SESSION['agente_id'])) {
    header("Location: login.php");
    exit();
}

$agente_id = $_SESSION['agente_id'];

// Recupera i dati tecnici energia associati all'agente
$sql_dati_tecnici = "SELECT * FROM dati_tecnici_energia WHERE cliente_id = ?";
$stmt_dati_tecnici = $conn->prepare($sql_dati_tecnici);
if (!$stmt_dati_tecnici) {
    die("Errore nella preparazione della query dati tecnici: " . $conn->error);
}
$stmt_dati_tecnici->bind_param("i", $agente_id);
$stmt_dati_tecnici->execute();
$result_dati_tecnici = $stmt_dati_tecnici->get_result();
$dati_tecnici = [];
while ($row = $result_dati_tecnici->fetch_assoc()) {
    $dati_tecnici[] = $row;
}
$stmt_dati_tecnici->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione contratti</title>
    <link rel="stylesheet" href="style.css">
    <!-- Meta tag per rendere la pagina responsive -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Stili CSS */
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
        }

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 1200px;
            margin: 50px auto;
        }

        h2 {
            margin-top: 0;
            color: #333;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #007bff;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 12px;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .no-data {
            text-align: center;
            color: #777;
            padding: 20px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff !important;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            text-align: center;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        /* Responsive */
        @media (max-width: 768px) {
            th, td {
                padding: 8px;
                font-size: 12px;
            }

            .container {
                padding: 20px;
                margin: 20px auto;
            }
        }
    </style>
</head>
<body>
    <!-- Includi il menu -->
    <?php include __DIR__ . '/menu.php'; ?>

    <div class="container">
        <h2>Gestione Dati Tecnici Energia</h2>
        <?php if (!empty($dati_tecnici)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <!-- <th>Cliente ID</th> -->
                        <th>CF/P.IVA</th>
                        <th>POD/PDR</th>
                        <th>KW/SMC</th>
                        <th>Monofase/Trifase</th>
                        <th>Consumo Annuo</th>
                        <th>Attuale Gestore</th>
                        <th>Indirizzo Fornitura</th>
                        <th>Civico</th>
                        <th>Città</th>
                        <th>Provincia</th>
                        <th>CAP</th>
                        <th>Offerta</th>
                        <th>Data Creazione</th>
                        <th>Tipo Cliente</th>
                        <th>Tipo Attivazione</th>
                        <th>POD/PDR Scelta</th>
                        <th>POD</th>
                        <th>PDR</th>
                        <th>KW</th>
                        <th>SMC</th>
                        <th>KW Disponibili</th>
                        <th>KW Impegnati</th>
                        <th>Consumo Annuo Luce</th>
                        <th>Consumo Annuo Gas</th>
                        <th>Attuale Gestore Luce</th>
                        <th>Attuale Gestore Gas</th>
                        <th>Tipo Documento</th>
                        <th>Emesso da</th>
                        <th>Data Emissione</th>
                        <th>Documento Fronte</th>
                        <th>Documento Retro</th>
                        <th>Documento CF</th>
                        <th>Fattura</th>
                        <th>RID Intestatario Diverso Fronte</th>
                        <th>RID Intestatario Diverso Retro</th>
                        <th>Firma RID Intestatario Diverso</th>
                        <th>Intestatario Diverso Nome</th>
                        <th>Intestatario Diverso Cognome</th>
                        <th>Banca</th>
                        <th>IBAN</th>
                        <th>Intestatario Nome</th>
                        <th>Intestatario Cognome</th>
                        <th>Codice Fiscale</th>
                        <th>Ente Luogo</th>
                        <!-- Aggiungi altre colonne necessarie -->
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dati_tecnici as $dati): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($dati['id'] ?? 'N/A'); ?></td>
                            <!-- <td><?php // echo htmlspecialchars($dati['cliente_id'] ?? 'N/A'); ?></td> -->
                            <td><?php echo htmlspecialchars($dati['cf_piva'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['pod_pdr'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['kw_smc'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['monofase_trifase'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['consumo_annuo'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['attuale_gestore'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['indirizzo_fornitura'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['civico'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['citta'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['provincia'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['cap'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['offerta'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['created_at'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['tipo_cliente'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['tipo_attivazione'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['pod_pdr_scelta'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['pod'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['pdr'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['kw'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['smc'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['KW_disponibili'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['KW_impegnati'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['consumo_annuo_luce'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['consumo_annuo_gas'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['attuale_gestore_luce'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['attuale_gestore_gas'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['tipo_documento'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['emesso_da'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['data_emissione'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if (!empty($dati['documento_fronte'])): ?>
                                    <a href="<?php echo htmlspecialchars($dati['documento_fronte']); ?>" target="_blank">Visualizza</a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($dati['documento_retro'])): ?>
                                    <a href="<?php echo htmlspecialchars($dati['documento_retro']); ?>" target="_blank">Visualizza</a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($dati['documento_cf'])): ?>
                                    <a href="<?php echo htmlspecialchars($dati['documento_cf']); ?>" target="_blank">Visualizza</a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($dati['fattura'])): ?>
                                    <a href="<?php echo htmlspecialchars($dati['fattura']); ?>" target="_blank">Visualizza</a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($dati['rid_intestatario_diverso_fronte'])): ?>
                                    <a href="<?php echo htmlspecialchars($dati['rid_intestatario_diverso_fronte']); ?>" target="_blank">Visualizza</a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($dati['rid_intestatario_diverso_retro'])): ?>
                                    <a href="<?php echo htmlspecialchars($dati['rid_intestatario_diverso_retro']); ?>" target="_blank">Visualizza</a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($dati['firma_rid_intestatario_diverso'])): ?>
                                    <a href="<?php echo htmlspecialchars($dati['firma_rid_intestatario_diverso']); ?>" target="_blank">Visualizza</a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($dati['intestatario_diverso_nome'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['intestatario_diverso_cognome'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['banca'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['iban'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['intestatario_nome'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['intestatario_cognome'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['codicefiscale'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dati['ente_luogo'] ?? 'N/A'); ?></td>
                            <!-- Aggiungi altre colonne necessarie -->
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-data">Nessun dato tecnico disponibile.</p>
        <?php endif; ?>
    </div>
</body>
</html>
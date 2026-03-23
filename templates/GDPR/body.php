<?php

use Models\Module;
use Models\Upload;

$module_anagrafiche = Module::where('name', 'Anagrafiche')->first()->id;

// Recupero le condizioni GDPR dalle impostazioni
$condizioni_gdpr = setting('Condizioni GDPR');

echo "
<table class='table gdpr-table' autosize='0'>
    <tr>
        <th class='gdpr-header-main'>
            INFORMATIVA PRIVACY (Art. 13 GDPR)<br>
        </th>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            &nbsp;<br>
        </td>
    </tr>
    <tr>
        <th class='gdpr-section-header'>
            1. TITOLARE DEL TRATTAMENTO
        </th>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            Il Titolare del trattamento è $f_ragionesociale con sede in $f_indirizzo, $f_citta_full.<br>
            Codice Fiscale: $f_codicefiscale - P.IVA: $f_piva<br><br>
            Per qualsiasi informazione relativa al trattamento dei dati personali, è possibile contattare il Titolare all'indirizzo sopra indicato o all'email $f_email.
        </td>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            &nbsp;<br>
        </td>
    </tr>
    <tr>
        <th class='gdpr-section-header'>
            2. FINALITÀ DEL TRATTAMENTO
        </th>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            I dati personali raccolti saranno trattati per le seguenti finalità:<br>
            &bull; Esecuzione di obblighi contrattuali e precontrattuali<br>
            &bull; Adempimento di obblighi di legge (fiscali, contabili, ecc.)<br>
            &bull; Gestione amministrativa e commerciale della clientela<br>
            &bull; Attività di assistenza tecnica e supporto<br>
            &bull; Finalità marketing e promozionali (previo consenso)
        </td>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            &nbsp;<br>
        </td>
    </tr>
    <tr>
        <th class='gdpr-section-header'>
            3. BASE GIURIDICA DEL TRATTAMENTO
        </th>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            Il trattamento dei dati personali si basa sulle seguenti basi giuridiche previste dal GDPR:<br>
            &bull; Art. 6, comma 1, lett. b): esecuzione di un contratto<br>
            &bull; Art. 6, comma 1, lett. c): adempimento di un obbligo legale<br>
            &bull; Art. 6, comma 1, lett. f): legittimo interesse del titolare<br>
            &bull; Art. 6, comma 1, lett. a): consenso dell'interessato (per finalità marketing)
        </td>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            &nbsp;<br>
        </td>
    </tr>
    <tr>
        <th class='gdpr-section-header'>
            4. CATEGORIE DI DATI TRATTATI
        </th>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            Il trattamento riguarda i seguenti dati personali:<br>
            &bull; Dati anagrafici (nome, cognome, ragione sociale, ecc.)<br>
            &bull; Dati di contatto (indirizzo, telefono, email)<br>
            &bull; Dati fiscali (codice fiscale, partita IVA)<br>
            &bull; Dati bancari (IBAN per pagamenti)<br>
            &bull; Dati relativi a servizi e prodotti acquistati
        </td>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            &nbsp;<br>
        </td>
    </tr>
    <tr>
        <th class='gdpr-section-header'>
            5. DESTINATARI DEI DATI
        </th>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            I dati personali potranno essere comunicati a:<br>
            &bull; Dipendenti e collaboratori del Titolare<br>
            &bull; Professionisti e consulenti (avvocati, commercialisti, ecc.)<br>
            &bull; Autorità pubbliche e organi di controllo (per adempimenti di legge)<br>
            &bull; Banche e istituti finanziari (per gestione pagamenti)<br>
            &bull; Fornitori di servizi informatici e di comunicazione<br>
            I dati non saranno diffusi al pubblico.
        </td>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            &nbsp;<br>
        </td>
    </tr>
    <tr>
        <th class='gdpr-section-header'>
            6. TRASFERIMENTO DEI DATI VERSO PAESI TERZI
        </th>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            I dati personali non saranno trasferiti verso paesi terzi non appartenenti all'Unione Europea, salvo quanto necessario per l'esecuzione del contratto o previo consenso dell'interessato. In tal caso, il trasferimento avverrà in conformità alle disposizioni del GDPR (art. 44 e seguenti).
        </td>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            &nbsp;<br>
        </td>
    </tr>
    <tr>
        <th class='gdpr-section-header'>
            7. PERIODO DI CONSERVAZIONE DEI DATI
        </th>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            I dati personali saranno conservati per il tempo necessario al conseguimento delle finalità per cui sono stati raccolti e, in ogni caso, per il periodo previsto dalle normative vigenti:<br>
            &bull; Dati fiscali e contabili: 10 anni (art. 2220 c.c.)<br>
            &bull; Dati contrattuali: 10 anni dalla cessazione del rapporto<br>
            &bull; Dati marketing: fino a revoca del consenso<br>
            &bull; Backup e dati tecnici: per il tempo strettamente necessario all'intervento e comunque non oltre 30 giorni
        </td>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            &nbsp;<br>
        </td>
    </tr>
</table>
<div class='gdpr-page-break'></div>
<table class='table gdpr-table' autosize='0'>
    <tr>
        <th class='gdpr-section-header'>
            8. DIRITTI DELL'INTERESSATO
        </th>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            L'interessato ha il diritto di esercitare i seguenti diritti previsti dagli artt. 15-22 del GDPR:<br>
            &bull; Diritto di accesso (art. 15)<br>
            &bull; Diritto di rettifica (art. 16)<br>
            &bull; Diritto alla cancellazione (diritto all'oblio) (art. 17)<br>
            &bull; Diritto di limitazione del trattamento (art. 18)<br>
            &bull; Diritto alla portabilità dei dati (art. 20)<br>
            &bull; Diritto di opposizione (art. 21)<br>
            &bull; Diritto di non essere sottoposto a processi decisionali automatizzati (art. 22)<br>
            &bull; Diritto di revocare il consenso in qualsiasi momento<br><br>
            Per esercitare tali diritti, l'interessato può inviare una richiesta all'indirizzo email $f_email o per iscritto alla sede del Titolare.
        </td>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            &nbsp;<br>
        </td>
    </tr>
    <tr>
        <th class='gdpr-section-header'>
            9. DIRITTO DI RECLAMO
        </th>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            L'interessato ha il diritto di proporre reclamo all'Autorità Garante per la protezione dei dati personali (Garante Privacy) qualora ritenga che il trattamento dei suoi dati sia avvenuto in violazione della normativa vigente. Il reclamo può essere presentato presso la sede del Garante (Piazza Venezia, 11 - 00186 Roma) o tramite il sito web www.garanteprivacy.it.
        </td>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            &nbsp;<br>
        </td>
    </tr>
    <tr>
        <th class='gdpr-section-header'>
            10. OBBLIGATORIETÀ O FACOLTATIVITÀ DEL CONFERIMENTO
        </th>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            Il conferimento dei dati personali è obbligatorio per le finalità connesse all'esecuzione del contratto e all'adempimento degli obblighi di legge. Il rifiuto di conferire tali dati comporta l'impossibilità di instaurare o proseguire il rapporto contrattuale. Il conferimento dei dati per finalità di marketing è facoltativo e il mancato consenso non ha alcuna conseguenza sul rapporto contrattuale.
        </td>
    </tr>";

// Mostra la sezione Condizioni Generali solo se specificata
if (!empty($condizioni_gdpr)) {
    echo "
    <tr class='border-0'>
        <td class='gdpr-cell'>
            &nbsp;<br>
        </td>
    </tr>
    <tr>
        <th class='gdpr-section-header'>
            11. CONDIZIONI GENERALI
        </th>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            ".nl2br($condizioni_gdpr)."
        </td>
    </tr>";
}

echo "
    <tr class='border-0'>
        <td class='gdpr-cell'>
            &nbsp;<br>&nbsp;<br>
        </td>
    </tr>";

// Ricerca della firma negli allegati con key 'signature'
$firma = '';
$upload = Upload::where('id_record', $id_record)
    ->where('id_module', $module_anagrafiche)
    ->where('key', 'signature_gdpr')
    ->first();

if (!empty($upload)) {
    $firma = '<img src="'.Uploads::getDirectory($module_anagrafiche).'/'.$upload->filename.'" class="gdpr-signature-img">';
}

echo "
    <tr>
        <td class='gdpr-cell'>
            <table class='w-100'>
                <tr>
                    <td class='gdpr-signature-width-70'>
                        Luogo e Data: ___________________________<br>
                    </td>
                    <td class='gdpr-signature-width-30'>
                        <table class='gdpr-signature-table'>
                            <tr>
                                <td class='gdpr-signature-label'>Firma</td>
                            </tr>
                            <tr>
                                <td class='gdpr-signature-box-compact'>
                                    ".($firma!=''?$firma:'<br>.............................')."
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            &nbsp;<br>
        </td>
    </tr>
    <tr class='border-0'>
        <td class='gdpr-cell'>
            &nbsp;<br>&nbsp;<br>
        </td>
    </tr>
    <tr>
        <th class='gdpr-section-header'>
            CONSENSI PRIVACY E MARKETING
        </th>
    </tr>
    <tr>
        <td class='gdpr-cell'>";


// Recuperare le scelte GDPR dalle variabili globali
$marketing_generico = isset($GLOBALS['gdpr_marketing_generico']) ? $GLOBALS['gdpr_marketing_generico'] : '1';
$profilazione = isset($GLOBALS['gdpr_profilazione']) ? $GLOBALS['gdpr_profilazione'] : '1';

// Funzione helper per generare le caselle con X
$checkbox_marketing = ($marketing_generico == '1') ? '[X]' : '[  ]';
$checkbox_marketing_no = ($marketing_generico == '0') ? '[X]' : '[  ]';

$checkbox_profilazione = ($profilazione == '1') ? '[X]' : '[  ]';
$checkbox_profilazione_no = ($profilazione == '0') ? '[X]' : '[  ]';

echo"
            1. PRESA VISIONE INFORMATIVA (Obbligatorio)<br>
            Dichiaro di aver ricevuto l'informativa privacy e compreso le modalità di trattamento dei miei dati personali ai sensi del Regolamento (UE) 2016/679 (GDPR).<br>
            [X] Confermo<br><br>

            2. MARKETING GENERICO (Facoltativo)<br>
            Acconsento all'invio di comunicazioni promozionali, newsletter e materiale informativo tramite email, SMS, WhatsApp o altri mezzi di comunicazione.<br>
            ".$checkbox_marketing." Acconsento &nbsp;&nbsp; ".$checkbox_marketing_no." Non acconsento<br><br>

            3. PROFILAZIONE (Facoltativo)<br>
            Acconsento all'analisi dei miei dati di acquisto e alle preferenze manifestate per ricevere offerte personalizzate e servizi su misura.<br>
            ".$checkbox_profilazione." Acconsento &nbsp;&nbsp; ".$checkbox_profilazione_no." Non acconsento<br><br>
        </td>
    </tr>
    <tr>
        <td class='gdpr-cell'>
            <table class='w-100'>
                <tr>
                    <td class='gdpr-signature-width-70'>
                        $f_citta_full, lì ".date('d/m/Y')." (data)<br>
                    </td>
                    <td class='gdpr-signature-width-30'>
                        <table class='gdpr-signature-table'>
                            <tr>
                                <td class='gdpr-signature-label'>Firma</td>
                            </tr>
                            <tr>
                                <td class='gdpr-signature-box-compact'>
                                    ".($firma!=''?$firma:'<br>.............................')."
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<style>

.gdpr-table {
    overflow: hidden;
    font-size: 8pt;
}

.gdpr-header-main {
    background-color: white;
    color: black;
    font-size: 10pt;
    font-weight: bold;
    padding: 3px;
    text-align: center;
}

.gdpr-section-header {
    background-color: #dbe5f1;
    font-size: 9pt;
    font-weight: bold;
    padding: 3px;
}

.gdpr-cell {
    border: 0;
    padding: 3px;
    text-align: justify;
}

.gdpr-signature-img {
    max-width: 100%;
    height: auto;
}

.gdpr-signature-table {
    width: 100%;
    border-collapse: collapse;
}

.gdpr-signature-label {
    text-align: center;
    padding: 5px;
}

.gdpr-signature-box-compact {
    text-align: center;
    padding: 5px;
    height: 30px;
}

.gdpr-signature-width-70 {
    width: 70%;
    padding: 0;
}

.gdpr-signature-width-30 {
    width: 30%;
    padding: 0;
    text-align: center;
}

.gdpr-page-break {
    page-break-before: always;
}
</style>";
?>

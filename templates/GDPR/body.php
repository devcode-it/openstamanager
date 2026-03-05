<?php

// Recupero le condizioni GDPR dalle impostazioni
$condizioni_gdpr = setting('Condizioni GDPR');

echo "
<table class='table' style='overflow:hidden;font-size:8pt;' autosize='0'>
    <tr>
        <th style='background-color:white;color:black;font-size:10pt;font-weight:bold;padding:3px;text-align:center;'>
            INFORMATIVA PRIVACY (Art. 13 GDPR)<br>
        </th>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            &nbsp;<br>
        </td>
    </tr>
    <tr>
        <th style='background-color:#dbe5f1;font-size:9pt;font-weight:bold;padding:3px;'>
            1. TITOLARE DEL TRATTAMENTO
        </th>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            Il Titolare del trattamento è $f_ragionesociale con sede in $f_indirizzo, $f_citta_full.<br>
            Codice Fiscale: $f_codicefiscale - P.IVA: $f_piva<br><br>
            Per qualsiasi informazione relativa al trattamento dei dati personali, è possibile contattare il Titolare all'indirizzo sopra indicato o all'email $f_email.
        </td>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            &nbsp;<br>
        </td>
    </tr>
    <tr>
        <th style='background-color:#dbe5f1;font-size:9pt;font-weight:bold;padding:3px;'>
            2. FINALITÀ DEL TRATTAMENTO
        </th>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            I dati personali raccolti saranno trattati per le seguenti finalità:<br>
            &bull; Esecuzione di obblighi contrattuali e precontrattuali<br>
            &bull; Adempimento di obblighi di legge (fiscali, contabili, ecc.)<br>
            &bull; Gestione amministrativa e commerciale della clientela<br>
            &bull; Attività di assistenza tecnica e supporto<br>
            &bull; Finalità marketing e promozionali (previo consenso)
        </td>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            &nbsp;<br>
        </td>
    </tr>
    <tr>
        <th style='background-color:#dbe5f1;font-size:9pt;font-weight:bold;padding:3px;'>
            3. BASE GIURIDICA DEL TRATTAMENTO
        </th>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            Il trattamento dei dati personali si basa sulle seguenti basi giuridiche previste dal GDPR:<br>
            &bull; Art. 6, comma 1, lett. b): esecuzione di un contratto<br>
            &bull; Art. 6, comma 1, lett. c): adempimento di un obbligo legale<br>
            &bull; Art. 6, comma 1, lett. f): legittimo interesse del titolare<br>
            &bull; Art. 6, comma 1, lett. a): consenso dell'interessato (per finalità marketing)
        </td>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            &nbsp;<br>
        </td>
    </tr>
    <tr>
        <th style='background-color:#dbe5f1;font-size:9pt;font-weight:bold;padding:3px;'>
            4. CATEGORIE DI DATI TRATTATI
        </th>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            Il trattamento riguarda i seguenti dati personali:<br>
            &bull; Dati anagrafici (nome, cognome, ragione sociale, ecc.)<br>
            &bull; Dati di contatto (indirizzo, telefono, email)<br>
            &bull; Dati fiscali (codice fiscale, partita IVA)<br>
            &bull; Dati bancari (IBAN per pagamenti)<br>
            &bull; Dati relativi a servizi e prodotti acquistati
        </td>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            &nbsp;<br>
        </td>
    </tr>
    <tr>
        <th style='background-color:#dbe5f1;font-size:9pt;font-weight:bold;padding:3px;'>
            5. DESTINATARI DEI DATI
        </th>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            I dati personali potranno essere comunicati a:<br>
            &bull; Dipendenti e collaboratori del Titolare<br>
            &bull; Professionisti e consulenti (avvocati, commercialisti, ecc.)<br>
            &bull; Autorità pubbliche e organi di controllo (per adempimenti di legge)<br>
            &bull; Banche e istituti finanziari (per gestione pagamenti)<br>
            &bull; Fornitori di servizi informatici e di comunicazione<br>
            I dati non saranno diffusi al pubblico.
        </td>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            &nbsp;<br>
        </td>
    </tr>
    <tr>
        <th style='background-color:#dbe5f1;font-size:9pt;font-weight:bold;padding:3px;'>
            6. TRASFERIMENTO DEI DATI VERSO PAESI TERZI
        </th>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            I dati personali non saranno trasferiti verso paesi terzi non appartenenti all'Unione Europea, salvo quanto necessario per l'esecuzione del contratto o previo consenso dell'interessato. In tal caso, il trasferimento avverrà in conformità alle disposizioni del GDPR (art. 44 e seguenti).
        </td>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            &nbsp;<br>
        </td>
    </tr>
    <tr>
        <th style='background-color:#dbe5f1;font-size:9pt;font-weight:bold;padding:3px;'>
            7. PERIODO DI CONSERVAZIONE DEI DATI
        </th>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            I dati personali saranno conservati per il tempo necessario al conseguimento delle finalità per cui sono stati raccolti e, in ogni caso, per il periodo previsto dalle normative vigenti:<br>
            &bull; Dati fiscali e contabili: 10 anni (art. 2220 c.c.)<br>
            &bull; Dati contrattuali: 10 anni dalla cessazione del rapporto<br>
            &bull; Dati marketing: fino a revoca del consenso<br>
            &bull; Backup e dati tecnici: per il tempo strettamente necessario all'intervento e comunque non oltre 30 giorni
        </td>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            &nbsp;<br>
        </td>
    </tr>
</table>
<div style='page-break-before:always'></div>
<table class='table' style='overflow:hidden;font-size:8pt;' autosize='0'>
    <tr>
        <th style='background-color:#dbe5f1;font-size:9pt;font-weight:bold;padding:3px;'>
            8. DIRITTI DELL'INTERESSATO
        </th>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
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
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            &nbsp;<br>
        </td>
    </tr>
    <tr>
        <th style='background-color:#dbe5f1;font-size:9pt;font-weight:bold;padding:3px;'>
            9. DIRITTO DI RECLAMO
        </th>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            L'interessato ha il diritto di proporre reclamo all'Autorità Garante per la protezione dei dati personali (Garante Privacy) qualora ritenga che il trattamento dei suoi dati sia avvenuto in violazione della normativa vigente. Il reclamo può essere presentato presso la sede del Garante (Piazza Venezia, 11 - 00186 Roma) o tramite il sito web www.garanteprivacy.it.
        </td>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            &nbsp;<br>
        </td>
    </tr>
    <tr>
        <th style='background-color:#dbe5f1;font-size:9pt;font-weight:bold;padding:3px;'>
            10. OBBLIGATORIETÀ O FACOLTATIVITÀ DEL CONFERIMENTO
        </th>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            Il conferimento dei dati personali è obbligatorio per le finalità connesse all'esecuzione del contratto e all'adempimento degli obblighi di legge. Il rifiuto di conferire tali dati comporta l'impossibilità di instaurare o proseguire il rapporto contrattuale. Il conferimento dei dati per finalità di marketing è facoltativo e il mancato consenso non ha alcuna conseguenza sul rapporto contrattuale.
        </td>
    </tr>";

// Mostra la sezione Condizioni Generali solo se specificata
if (!empty($condizioni_gdpr)) {
    echo "
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            &nbsp;<br>
        </td>
    </tr>
    <tr>
        <th style='background-color:#dbe5f1;font-size:9pt;font-weight:bold;padding:3px;'>
            11. CONDIZIONI GENERALI
        </th>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            ".nl2br($condizioni_gdpr)."
        </td>
    </tr>";
}

echo "
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            &nbsp;<br>&nbsp;<br>
        </td>
    </tr>";

$firma = !empty($anagrafica['firma_file']) ? '<img src="'.DOCROOT.'/files/anagrafiche/'.$anagrafica['firma_file'].'" style="width:60mm;">' : '';

echo "
    <tr>
        <td align='justify' style='padding:3px;'>
            <table style='width:100%'>
                <tr>
                    <td style='width:60%;padding:0px;'>
                        Luogo e Data: ___________________________<br>
                        IL CLIENTE (Firma per accettazione)
                    </td>
                    <td style='width:40%'>
                        Firma<br>".($firma!=''?$firma:'<br><br>.............................')."
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            &nbsp;<br>
        </td>
    </tr>
    <tr style='border:0px;'>
        <td align='justify'  style='border:0px;padding:3px;'>
            &nbsp;<br>&nbsp;<br>
        </td>
    </tr>
    <tr>
        <th style='background-color:#dbe5f1;font-size:9pt;font-weight:bold;padding:3px;'>
            CONSENSI PRIVACY E MARKETING
        </th>
    </tr>
    <tr>
        <td align='justify' style='padding:3px;'>
            1. PRESA VISIONE INFORMATIVA (Obbligatorio)<br>
            Dichiaro di aver ricevuto l'informativa privacy e compreso le modalità di trattamento dei miei dati personali ai sensi del Regolamento (UE) 2016/679 (GDPR).<br>
            [X] Confermo<br><br>

            2. MARKETING GENERICO (Facoltativo)<br>
            Acconsento all'invio di comunicazioni promozionali, newsletter e materiale informativo tramite email, SMS, WhatsApp o altri mezzi di comunicazione.<br>
            [  ] Acconsento &nbsp;&nbsp; [  ] Non acconsento<br><br>

            3. PROFILAZIONE (Facoltativo)<br>
            Acconsento all'analisi dei miei dati di acquisto e alle preferenze manifestate per ricevere offerte personalizzate e servizi su misura.<br>
            [  ] Acconsento &nbsp;&nbsp; [  ] Non acconsento<br><br>

            4. CESSIONE DATI A TERZI (Facoltativo)<br>
            Acconsento alla cessione dei miei dati a partner commerciali selezionati per finalità promozionali.<br>
            [  ] Acconsento &nbsp;&nbsp; [  ] Non acconsento<br><br>
        </td>
    </tr>";

$firma = !empty($anagrafica['firma_file']) ? '<img src="'.DOCROOT.'/files/anagrafiche/'.$anagrafica['firma_file'].'" style="width:60mm;">' : '';

echo "
    <tr>
        <td align='justify' style='padding:3px;'>
            <table style='width:100%'>
                <tr>
                    <td style='width:60%;padding:0px;'>
                        $f_citta_full, lì ".date('d/m/Y')." (data)<br>
                        IL CLIENTE (Seconda Firma Obbligatoria)
                    </td>
                    <td style='width:40%'>
                        Firma<br>".($firma!=''?$firma:'<br><br>.............................')."
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>";

?>

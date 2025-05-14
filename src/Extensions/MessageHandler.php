<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Extensions;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

/**
 * Gestore dei messaggi di avvertenza in caso di malfunzionamento del gestionale.
 *
 * @since 2.4.6
 */
class MessageHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        // Controlla se la richiesta è AJAX
        if (\Whoops\Util\Misc::isAjaxRequest()) {
            return;
        }

        // Verifica se l'utente è un amministratore
        $is_admin = auth()->check() && auth()->isAdmin();

        // Estrai i dati dal record
        $uid = $record->extra['uid'] ?? '';
        $message_text = $record->message ?? '';
        $context = $record->context ?? [];
        $file = $context['file'] ?? '';
        $line = $context['line'] ?? '';

        // Genera un ID univoco per il collapsible
        $collapse_id = 'error_details_' . uniqid();

        if ($is_admin) {
            // Messaggio dettagliato per amministratori
            $message = '<div class="card card-danger shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0 d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fa fa-exclamation-triangle mr-2"></i>
                            '.tr('Si è verificato un errore').'
                        </div>
                        <button class="btn btn-sm btn-outline-light btn-xs" type="button" data-toggle="collapse" data-target="#'.$collapse_id.'" aria-expanded="false" aria-controls="'.$collapse_id.'">
                            <i class="fa fa-info-circle mr-1"></i> '.tr('Dettagli tecnici').'
                        </button>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="collapse" id="'.$collapse_id.'">
                        <div class="card card-body bg-light mb-2">
                            <div class="row">';

            // Messaggio di errore (colonna sinistra)
            if (!empty($message_text)) {
                $message .= '<div class="col-md-6 mb-2">
                    <h6 class="font-weight-bold text-danger mb-1"><i class="fa fa-bug mr-1"></i>'.tr('Messaggio errore').'</h6>
                    <pre class="p-2 rounded bg-white border" style="white-space: pre-wrap; word-wrap: break-word;"><code>'.htmlspecialchars($message_text).'</code></pre>
                </div>';
            }

            // Informazioni sul file e sulla riga (colonna destra)
            if (!empty($file) && !empty($line)) {
                $message .= '<div class="col-md-6 mb-2">
                    <h6 class="font-weight-bold text-info mb-1"><i class="fa fa-file-code mr-1"></i>'.tr('Informazioni file').'</h6>
                    <div class="p-2 rounded bg-white border">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-file-text text-secondary mr-1"></i>
                            <strong class="mr-1">'.tr('File').':</strong> <code class="text-truncate" title="'.$file.'">'.$file.'</code>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fa fa-code text-secondary mr-1"></i>
                            <strong class="mr-1">'.tr('Linea').':</strong> <code>'.$line.'</code>
                        </div>
                    </div>
                </div>';
            }

            $message .= '</div>
                    </div>
                </div>';

            // Prepara i dettagli dell'errore per il clipboard
            $error_details = "Descrizione del problema:\n[Inserire qui una descrizione dettagliata del problema riscontrato]\n\n";
            $error_details .= "Passi per riprodurre l'errore:\n1. \n2. \n3. \n\n";
            $error_details .= "Dettagli tecnici dell'errore:\n";

            if (!empty($message_text)) {
                $error_details .= "Messaggio: " . strip_tags($message_text) . "\n";
            }
            if (!empty($file) && !empty($line)) {
                $error_details .= "File: " . $file . "\n";
                $error_details .= "Linea: " . $line . "\n";
            }

            // Aggiungi informazioni sul sistema
            $error_details .= "\nInformazioni di sistema:\n";
            $error_details .= "Versione PHP: " . phpversion() . "\n";
            $error_details .= "Sistema operativo: " . php_uname() . "\n";
            $error_details .= "Browser: [Inserire il browser utilizzato]\n";
            $error_details .= "Versione OpenSTAManager: [Inserire la versione in uso]\n\n";

            // Genera un ID univoco per il contenitore dei dettagli
            $details_id = 'error_details_clipboard_' . uniqid();

            // URL del forum semplice
            $forum_url = "https://forum.openstamanager.com/";

            // Informazioni di assistenza
            $message .= '<div class="alert alert-warning mt-2 mb-0 py-2 px-3">
                <div class="d-flex align-items-center">
                    <i class="fa fa-question-circle text-warning mr-2"></i>
                    <div>
                        '.tr('Se il problema dovesse persistere, invitiamo gli utenti con contratto di assistenza attivo ad aprire un ticket nell\'area clienti. <br>In alternativa, è possibile richiedere supporto tramite il forum ufficiale (_LINK_FORUM_)', [
                            '_LINK_FORUM_' => '<a href="'.$forum_url.'" target="_blank" class="font-weight-bold mr-2">'.tr('Forum di supporto').'</a>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-xs copy-error-details" data-clipboard-text="'.htmlspecialchars($error_details).'" id="'.$details_id.'">
                                <i class="fa fa-clipboard mr-1"></i> '.tr('Copia dettagli errore').'
                            </button>
                            <script>
                                $(document).ready(function() {
                                    var copiedText = "'.tr('Copiato').'";

                                    $("#'.$details_id.'").on("click", function() {
                                        var textArea = document.createElement("textarea");
                                        textArea.value = $(this).attr("data-clipboard-text");
                                        document.body.appendChild(textArea);
                                        textArea.select();
                                        document.execCommand("copy");
                                        document.body.removeChild(textArea);

                                        // Cambia il testo del pulsante temporaneamente
                                        var originalText = $(this).html();
                                        $(this).html("<i class=\"fa fa-check mr-1\"></i> " + copiedText);

                                        // Ripristina il testo originale dopo 2 secondi
                                        var button = this;
                                        setTimeout(function() {
                                            $(button).html(originalText);
                                        }, 2000);
                                    });
                                });
                            </script>',
                        ]).'
                    </div>
                </div>
            </div>';

            $message .= '</div></div>';
        } else {
            // Messaggio semplificato per utenti normali
            $message = '<div class="card card-danger shadow-sm mb-0">
                <div class="card-header bg-danger text-white py-2">
                    <h5 class="mb-0 d-flex align-items-center">
                        <i class="fa fa-exclamation-triangle mr-2"></i>
                        '.tr('Si è verificato un errore').'
                    </h5>
                </div>
                <div class="card-body py-2">
                    <div class="d-flex align-items-center">
                        <div>
                            <p class="mb-1">'.tr('Si è verificato un errore durante l\'elaborazione della richiesta').'.</p>
                            <p class="mb-0">'.tr("Contattare l'amministratore di sistema").'.</p>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-2 mb-0 py-2 px-3">
                        <div class="d-flex align-items-center">
                            <i class="fa fa-question-circle text-warning mr-2"></i>
                            <div class="small">
                                '.tr('Se il problema persiste, contattare l\'assistenza tecnica').'
                            </div>
                        </div>
                    </div>
                </div>
            </div>';
        }

        // Messaggio nella sessione
        try {
            flash()->error(tr('Si è verificato un errore'));
        } catch (\Exception) {
            // Gestisci l'eccezione se necessario
        }

        // Messaggio visivo immediato
        echo '
    <div class="container-fluid py-3">
        '.$message.'
    </div>';
    }
}

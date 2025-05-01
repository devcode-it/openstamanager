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

include_once __DIR__.'/../../../core.php';

use Modules\Preventivi\Components\Articolo;
use Modules\Preventivi\Components\Riga;
use Modules\Preventivi\Components\Sconto;

// Recupero le righe selezionate
$riga_id = get('riga_id');
$righe_ids = $_GET['righe'];

// Array per memorizzare le aliquote IVA distinte
$aliquote_iva = [];
// Array per memorizzare le righe senza aliquota IVA o con aliquota non valida
$righe_senza_iva = [];
// Contatore per le righe totali
$righe_totali = 0;

if (!empty($riga_id)) {
    // Caso singola riga
    $riga = Riga::find($riga_id) ?: Articolo::find($riga_id);
    $riga = $riga ?: Sconto::find($riga_id);
    $righe_totali++;

    if (!empty($riga) && !empty($riga->idiva) && !empty($riga->aliquota)) {
        $aliquote_iva[$riga->idiva] = [
            'id' => $riga->idiva,
            'codice' => $riga->aliquota->codice,
            'descrizione' => $riga->aliquota->getTranslation('title'),
            'percentuale' => $riga->aliquota->percentuale,
            'count' => 1
        ];
    } else {
        // Riga senza aliquota IVA o con aliquota non valida
        $righe_senza_iva[] = [
            'id' => $riga->id,
            'descrizione' => $riga->descrizione,
            'idiva' => $riga->idiva ?: 'N/D'
        ];
    }
} elseif (!empty($righe_ids)) {
    // Caso multiple righe
    $righe_array = explode(',', $righe_ids);
    $righe_totali = count($righe_array);

    foreach ($righe_array as $id_riga) {
        $riga = Riga::find($id_riga) ?: Articolo::find($id_riga);
        $riga = $riga ?: Sconto::find($id_riga);

        if (!empty($riga) && !empty($riga->idiva) && !empty($riga->aliquota)) {
            if (!isset($aliquote_iva[$riga->idiva])) {
                $aliquote_iva[$riga->idiva] = [
                    'id' => $riga->idiva,
                    'codice' => $riga->aliquota->codice,
                    'descrizione' => $riga->aliquota->getTranslation('title'),
                    'percentuale' => $riga->aliquota->percentuale,
                    'count' => 0
                ];
            }
            $aliquote_iva[$riga->idiva]['count']++;
        } elseif (!empty($riga)) {
            // Riga senza aliquota IVA o con aliquota non valida
            $righe_senza_iva[] = [
                'id' => $riga->id,
                'descrizione' => $riga->descrizione,
                'idiva' => $riga->idiva ?: 'N/D'
            ];
        }
    }
}

// Se non ci sono aliquote IVA, ma ci sono righe selezionate, mostriamo comunque il form
$show_form = count($aliquote_iva) > 0 || count($righe_senza_iva) > 0 || (!empty($righe_ids) && !empty($righe_array));
?>

<form id="modifica-iva-form">
    <?php if (count($aliquote_iva) > 0): ?>
    <div class="row">
        <div class="col-md-5">
            <div class="alert alert-info">
                <p><strong><?= tr('Aliquote IVA attuali') ?></strong></p>
                <ul class="list-unstyled">
                <?php foreach ($aliquote_iva as $aliquota): ?>
                    <li>
                        <strong><?= $aliquota['codice'] ?></strong> - <?= $aliquota['descrizione'] ?> (<?= $aliquota['percentuale'] ?>%)
                        <?php if (count($aliquote_iva) > 1): ?>
                            <span class="badge"><?= $aliquota['count'] ?> <?= tr('righe') ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="col-md-2 text-center" style="padding-top: 30px;">
            <i class="fa fa-arrow-right fa-3x text-muted"></i>
        </div>

        <div class="col-md-5">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <h4 class="panel-title"><?= tr('Aliquota da applicare') ?></h4>
                </div>
                <div class="panel-body">
                    {[ "type": "select", "label": "", "name": "iva_id", "required": 1, "ajax-source": "iva" ]}
                    <input type="hidden" name="riga_id" value="<?= get('riga_id') ?>">
                    <input type="hidden" name="righe" value="<?= $_GET['righe'] ?>">
                </div>
            </div>
        </div>
    </div>
    <?php elseif ($show_form): ?>
    <div class="row">
        <div class="col-md-5">
            <div class="alert alert-info">
                <p><strong><?= tr('Aliquote IVA attuali') ?></strong></p>

                <?php if (count($aliquote_iva) > 0): ?>
                <ul class="list-unstyled">
                <?php foreach ($aliquote_iva as $aliquota): ?>
                    <li>
                        <strong><?= $aliquota['codice'] ?></strong> - <?= $aliquota['descrizione'] ?> (<?= $aliquota['percentuale'] ?>%)
                        <span class="badge"><?= $aliquota['count'] ?> <?= tr('righe') ?></span>
                    </li>
                <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <?php if (count($righe_senza_iva) > 0): ?>
                <p><strong><?= tr('Righe senza aliquota IVA valida') ?>:</strong> <span class="badge"><?= count($righe_senza_iva) ?></span></p>
                <?php endif; ?>

                <p class="text-muted"><?= tr('Totale righe selezionate') ?>: <?= $righe_totali ?></p>
            </div>
        </div>

        <div class="col-md-2 text-center" style="padding-top: 30px;">
            <i class="fa fa-arrow-right fa-3x text-muted"></i>
        </div>

        <div class="col-md-5">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <h4 class="panel-title"><?= tr('Aliquota da applicare') ?></h4>
                </div>
                <div class="panel-body">
                    {[ "type": "select", "label": "", "name": "iva_id", "required": 1, "ajax-source": "iva" ]}
                    <input type="hidden" name="riga_id" value="<?= get('riga_id') ?>">
                    <input type="hidden" name="righe" value="<?= get('righe') ?>">
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-warning">
                <p><?= tr('Nessuna riga selezionata') ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- PULSANTI -->
    <div class="row">
        <div class="col-md-12 text-right">
            <button type="button" class="btn btn-primary" onclick="salvaIva()">
                <i class="fa fa-save"></i> <?= tr('Salva') ?>
            </button>
        </div>
    </div>
</form>

<script>
function salvaIva() {
    var riga_id = $('#modifica-iva-form input[name=riga_id]').val();
    var righe = $('#modifica-iva-form input[name=righe]').val();
    var iva_id = input('iva_id').get();

    // Se ci sono righe multiple selezionate
    if (righe) {
        // Converto la stringa in array
        var righe_array = righe.split(',');

        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "update_iva_multiple",
                righe: righe_array,
                iva_id: iva_id
            },
            success: function(response) {
                $('#modals > div').modal('hide');
                caricaRighe(null);
                renderMessages();
            },
            error: function() {
                alert(<?= json_encode(tr('Errore durante il salvataggio')) ?>);
            }
        });
    } else {
        // Singola riga
        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "update_iva",
                riga_id: riga_id,
                iva_id: iva_id
            },
            success: function(response) {
                $('#modals > div').modal('hide');
                caricaRighe(riga_id);
                renderMessages();
            },
            error: function() {
                alert(<?= json_encode(tr('Errore durante il salvataggio')) ?>);
            }
        });
    }
}
</script>

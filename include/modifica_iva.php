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

include_once __DIR__.'/../core.php';

// Mapping dei tipi di documento con i relativi namespace
$tipo_documento = get('tipo_documento');
$namespace_map = [
    'fatture' => 'Modules\\Fatture\\Components',
    'ordini' => 'Modules\\Ordini\\Components',
    'preventivi' => 'Modules\\Preventivi\\Components',
    'interventi' => 'Modules\\Interventi\\Components',
    'ddt' => 'Modules\\DDT\\Components',
    'contratti' => 'Modules\\Contratti\\Components',
];

// Se il tipo di documento non è specificato, prova a dedurlo dal modulo corrente
if (empty($tipo_documento)) {
    $module = Modules::get($id_module);
    if ($module) {
        $module_name = strtolower($module['name']);
        // Mappatura dei nomi dei moduli ai tipi di documento
        $module_map = [
            'fatture di vendita' => 'fatture',
            'fatture di acquisto' => 'fatture',
            'ordini cliente' => 'ordini',
            'ordini fornitore' => 'ordini',
            'preventivi' => 'preventivi',
            'interventi' => 'interventi',
            'ddt in entrata' => 'ddt',
            'ddt in uscita' => 'ddt',
            'contratti' => 'contratti',
        ];
        $tipo_documento = $module_map[$module_name] ?? null;
    }
}

// Se non è stato possibile determinare il tipo di documento, usa il default (fatture)
if (empty($tipo_documento) || !isset($namespace_map[$tipo_documento])) {
    $tipo_documento = 'fatture';
}

// Carica le classi appropriate
$namespace = $namespace_map[$tipo_documento];
$articolo_class = $namespace . '\\Articolo';
$riga_class = $namespace . '\\Riga';
$sconto_class = $namespace . '\\Sconto';

/**
 * Funzione helper per recuperare una riga indipendentemente dal tipo
 */
function getRiga($id, $riga_class, $articolo_class, $sconto_class)
{
    $riga = $riga_class::find($id) ?: $articolo_class::find($id);
    return $riga ?: $sconto_class::find($id);
}

/**
 * Funzione helper per costruire l'array delle informazioni sull'aliquota IVA
 */
function buildAliquotaArray($riga)
{
    return [
        'id' => $riga->idiva,
        'codice' => $riga->aliquota->codice,
        'descrizione' => $riga->aliquota->getTranslation('title'),
        'percentuale' => $riga->aliquota->percentuale,
        'count' => 0,
    ];
}

// Recupero le righe selezionate
$riga_id = get('riga_id');
$righe_ids = sanitizeRighe($_GET['righe'] ?? '');

// Array per memorizzare le aliquote IVA distinte
$aliquote_iva = [];
// Array per memorizzare le righe senza aliquota IVA o con aliquota non valida
$righe_senza_iva = [];
// Contatore per le righe totali
$righe_totali = 0;
// Array degli ID delle righe processate
$righe_ids_array = [];

if (!empty($riga_id)) {
    // Caso singola riga
    $riga = getRiga($riga_id, $riga_class, $articolo_class, $sconto_class);
    $righe_totali = 1;
    $righe_ids_array = [$riga_id];

    if (!empty($riga) && !empty($riga->idiva) && !empty($riga->aliquota)) {
        $aliquota = buildAliquotaArray($riga);
        $aliquota['count'] = 1;
        $aliquote_iva[$riga->idiva] = $aliquota;
    } elseif (!empty($riga)) {
        $righe_senza_iva[] = [
            'id' => $riga->id,
            'descrizione' => $riga->descrizione,
            'idiva' => $riga->idiva ?: 'N/D',
        ];
    }
} elseif (!empty($righe_ids)) {
    // Caso multiple righe
    $righe_ids_array = explode(',', (string) $righe_ids);
    $righe_totali = count($righe_ids_array);

    foreach ($righe_ids_array as $id_riga) {
        $riga = getRiga($id_riga, $riga_class, $articolo_class, $sconto_class);

        if (!empty($riga) && !empty($riga->idiva) && !empty($riga->aliquota)) {
            if (!isset($aliquote_iva[$riga->idiva])) {
                $aliquote_iva[$riga->idiva] = buildAliquotaArray($riga);
            }
            ++$aliquote_iva[$riga->idiva]['count'];
        } elseif (!empty($riga)) {
            $righe_senza_iva[] = [
                'id' => $riga->id,
                'descrizione' => $riga->descrizione,
                'idiva' => $riga->idiva ?: 'N/D',
            ];
        }
    }
}

// Determina se mostrare il form
$show_form = count($aliquote_iva) > 0 || count($righe_senza_iva) > 0 || !empty($righe_ids_array);
$multiple_aliquote = count($aliquote_iva) > 1;
?>

<form id="modifica-iva-form">
    <?php if ($show_form) { ?>
    <div class="row">
        <div class="col-md-5">
            <div class="alert alert-info">
                <p><strong><?php echo tr('Aliquote IVA attuali'); ?></strong></p>

                <?php if (count($aliquote_iva) > 0) { ?>
                <ul class="list-unstyled">
                <?php foreach ($aliquote_iva as $aliquota) { ?>
                    <li>
                        <strong><?php echo $aliquota['codice']; ?></strong> - <?php echo $aliquota['descrizione']; ?> (<?php echo $aliquota['percentuale']; ?>%)
                        <?php if ($multiple_aliquote) { ?>
                            <span class="badge"><?php echo $aliquota['count']; ?> <?php echo tr('righe'); ?></span>
                        <?php } ?>
                    </li>
                <?php } ?>
                </ul>
                <?php } ?>

                <?php if (count($righe_senza_iva) > 0) { ?>
                <p><strong><?php echo tr('Righe senza aliquota IVA valida'); ?>:</strong> <span class="badge"><?php echo count($righe_senza_iva); ?></span></p>
                <?php } ?>

                <?php if ($righe_totali > 0) { ?>
                <p class="text-muted"><?php echo tr('Totale righe selezionate'); ?>: <?php echo $righe_totali; ?></p>
                <?php } ?>
            </div>
        </div>

        <div class="col-md-2 text-center" style="padding-top: 30px;">
            <i class="fa fa-arrow-right fa-3x text-muted"></i>
        </div>

        <div class="col-md-5">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <h4 class="panel-title"><?php echo tr('Aliquota da applicare'); ?></h4>
                </div>
                <div class="panel-body">
                    {[ "type": "select", "label": "", "name": "iva_id", "required": 1, "ajax-source": "iva" ]}
                    <input type="hidden" name="riga_id" value="<?php echo get('riga_id'); ?>">
                    <input type="hidden" name="righe" value="<?php echo htmlspecialchars($righe_ids, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
        </div>
    </div>
    <?php } else { ?>
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-warning">
                <p><?php echo tr('Nessuna riga selezionata'); ?></p>
            </div>
        </div>
    </div>
    <?php } ?>

    <!-- PULSANTI -->
    <div class="row">
        <div class="col-md-12 text-right">
            <button type="button" class="btn btn-primary" onclick="salvaIva()">
                <i class="fa fa-save"></i> <?php echo tr('Salva'); ?>
            </button>
        </div>
    </div>
</form>

<script>
// Inizializzazione dei select AJAX quando il modal viene caricato
$(document).ready(function() {
    initializeAjaxSelects();
});

// Inizializzazione quando il modal viene mostrato
$('#modals').on('shown.bs.modal', function() {
    initializeAjaxSelects();
});

// Funzione per inizializzare i select AJAX
function initializeAjaxSelects() {
    $('#modifica-iva-form select[data-source], #modifica-iva-form .superselectajax').each(function() {
        if (!$(this).hasClass('select2-hidden-accessible')) {
            $(this).addClass('superselectajax');
            input(this);
        }
    });
}

function salvaIva() {
    var riga_id = $('#modifica-iva-form input[name=riga_id]').val();
    var righe = $('#modifica-iva-form input[name=righe]').val();
    var iva_id = input('iva_id').get();

    var requestData = {
        id_module: globals.id_module,
        id_record: globals.id_record,
        iva_id: iva_id
    };

    // Determina l'operazione in base al tipo di selezione
    if (righe) {
        requestData.op = "update_iva_multiple";
        requestData.righe = righe.split(',');
    } else {
        requestData.op = "update_iva";
        requestData.riga_id = riga_id;
    }

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "POST",
        data: requestData,
        success: function(response) {
            $('#modals > div').modal('hide');
            caricaRighe(riga_id || null);
            renderMessages();
        },
        error: function() {
            alert(<?php echo json_encode(tr('Errore durante il salvataggio')); ?>);
        }
    });
}
</script>

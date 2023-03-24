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

// Imposto come azienda l'azienda predefinita per selezionare le sedi a cui ho accesso
// select-options

$id_anagrafica = get('id_anagrafica');
$direzione = (!empty(get('direzione'))) ? 'entrata' : 'uscita';
$righe = $_GET['righe'];

$righe = $dbo->fetchArray(
    'SELECT mg_articoli.descrizione, co_righe_contratti.*
    FROM co_righe_contratti
    JOIN mg_articoli ON mg_articoli.id = co_righe_contratti.idarticolo
    WHERE co_righe_contratti.id IN ('.$righe.')'
);
?>
<form action="" method="post" id="add-form">
    <table class="table table-striped table-hover table-condensed table-bordered m-3">
        <thead>
            <tr>
                <th width="35" class="text-center" ><?php echo tr('#'); ?></th>
                <th><?php echo tr('Descrizione'); ?></th>
                <th class="text-center" width="150"><?php echo tr('Prezzo corrente'); ?></th>
                <th class="text-center" width="150"><?php echo tr('Ultimo preventivo'); ?></th>
                <th class="text-center" width="150"><?php echo tr('Ultima vendita'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($righe as $riga) { ?>
                <?php
                    $prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

                    $ultimo_prezzo_preventivo = $dbo->fetchArray(
                        'SELECT co_righe_contratti.idarticolo, co_righe_contratti.prezzo_unitario, date(co_preventivi.updated_at) as updated_at
                        FROM co_preventivi
                        JOIN co_righe_contratti ON co_preventivi.id = co_righe_contratti.idpreventivo
                        WHERE co_preventivi.idanagrafica ='.prepare($id_anagrafica).'
                        AND co_righe_contratti.idarticolo ='.prepare($riga['idarticolo']).'
                        ORDER BY co_righe_contratti.updated_at DESC'
                    )[0];

                    $ultimo_prezzo_vendita = $dbo->fetchArray(
                        'SELECT iddocumento AS id, "Fattura" AS tipo, "Fatture di vendita" AS modulo,
                        (subtotale-sconto)/qta AS costo_unitario,
                        (SELECT numero FROM co_documenti WHERE id=iddocumento) AS n_documento,
                        (SELECT numero_esterno FROM co_documenti WHERE id=iddocumento) AS n2_documento,
                        (SELECT data FROM co_documenti WHERE id=iddocumento) AS data_documento
                        FROM co_righe_documenti WHERE idarticolo='.prepare($riga['idarticolo']).'
                        AND iddocumento IN(SELECT id FROM co_documenti WHERE idtipodocumento IN
                        (SELECT id FROM co_tipidocumento WHERE dir="entrata") AND idanagrafica='.prepare($id_anagrafica).')
                        UNION
                        SELECT idddt AS id, "Ddt" AS tipo, "Ddt di vendita" AS modulo,
                        (subtotale-sconto)/qta AS costo_unitario,
                        (SELECT numero FROM dt_ddt WHERE id=idddt) AS n_documento,
                        (SELECT numero_esterno FROM dt_ddt WHERE id=idddt) AS n2_documento,
                        (SELECT data FROM dt_ddt WHERE id=idddt) AS data_documento
                        FROM dt_righe_ddt WHERE idarticolo='.prepare($riga['idarticolo']).' AND
                        idddt IN(SELECT id FROM dt_ddt WHERE idtipoddt IN(SELECT id FROM dt_tipiddt WHERE dir="entrata")
                        AND idanagrafica='.prepare($id_anagrafica).') ORDER BY data_documento DESC LIMIT 1'
                    )[0];
                ?>

                <tr>
                    <td><?= $riga['idarticolo'] ?></td>
                    <td><?= $riga['descrizione'] ?></td>
                    <td>
                        <div>
                            {[ "type": "number", "label": "", "data-id":"<?php echo $riga['id']; ?>","name": "nuovo_prezzo_unitario[]", "value": "<?php echo number_format($riga['prezzo_unitario'], 4); ?>", "icon-after": "<?php echo currency(); ?>" ]}
                        </div>
                    </td>
                    <td><?php
                        if (isset($ultimo_prezzo_preventivo)) {
                            echo number_format($ultimo_prezzo_preventivo['prezzo_unitario'], 4) . ' &euro; - ' . $ultimo_prezzo_preventivo['updated_at'];
                        } else {
                            echo 'Non disponibile';
                        }
                    ?></td>
                    <td><?php
                        if (isset($ultimo_prezzo_vendita)) {
                            echo number_format($ultimo_prezzo_vendita['costo_unitario'], 4) . ' &euro; - ' . $ultimo_prezzo_vendita['data_documento'];
                        } else {
                            echo 'Non disponibile';
                        }
                    ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <a class="btn btn-primary btn-edit">
        <i class="fa fa-edit"></i> <?php echo tr('Modifica'); ?>
    </a>
</form>

<script>
    $(document).ready(function() {
        $('.btn-edit').on('click', function() {
            var id = [];
            $('input[name^="nuovo_prezzo_unitario"]').each(function() {
                id.push({
                    'id': $(this).data('id'),
                    'price': $(this).val(),
                });
            });

            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "POST",
                dataType: "json",
                data: {
                    id_module: globals.id_module,
                    id_record: globals.id_record,
                    op: "edit-price",
                    backto: "record-edit",
                    righe: id,
                },
            success: function (response) {
                location.reload();
            },
            error: function() {
                location.reload();
            }
        });
        });
    });
</script>

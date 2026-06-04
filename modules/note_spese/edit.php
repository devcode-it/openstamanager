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

include_once __DIR__.'/../../core.php';

$righe = $dbo->fetchArray('SELECT * FROM `ns_righe_note_spese` WHERE `id_nota_spesa` = ? ORDER BY `data` ASC, `id` ASC', [$id_record]);
$totale = (float) ($record['totale'] ?? 0);

?>
<form action="" method="post" id="edit-form">
    <input type="hidden" name="op" value="update">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

    <div class="row">
        <div class="col-md-2">
            {[ "type": "text", "label": "<?php echo tr('Numero'); ?>", "name": "numero", "required": 1, "value": "$numero$" ]}
        </div>

        <div class="col-md-2">
            {[ "type": "date", "label": "<?php echo tr('Data'); ?>", "name": "data", "required": 1, "value": "$data$" ]}
        </div>

        <div class="col-md-3">
            {[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "stato", "value": "$stato$", "values": "list=bozza|<?php echo tr('Bozza'); ?>,confermata|<?php echo tr('Confermata'); ?>,rimborsata|<?php echo tr('Rimborsata'); ?>" ]}
        </div>

        <div class="col-md-5">
            {[ "type": "select", "label": "<?php echo tr('Persona'); ?>", "name": "id_anagrafica", "required": 1, "value": "$id_anagrafica$", "values": "query=SELECT `an_anagrafiche`.`id`, `ragione_sociale` AS descrizione FROM `an_anagrafiche` WHERE `an_anagrafiche`.`deleted_at` IS NULL ORDER BY `ragione_sociale`" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-9">
            {[ "type": "text", "label": "<?php echo tr('Oggetto'); ?>", "name": "oggetto", "required": 1, "value": "$oggetto$" ]}
        </div>

        <div class="col-md-3">
            {[ "type": "text", "label": "<?php echo tr('Totale'); ?>", "name": "totale", "value": "<?php echo moneyFormat($totale); ?>", "disabled": 1, "class": "text-right", "icon-after": "<?php echo currency(); ?>" ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            {[ "type": "ckeditor", "label": "<?php echo tr('Note'); ?>", "name": "note", "value": "$note$" ]}
        </div>
    </div>
</form>

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fa fa-list"></i> <?php echo tr('Righe nota spese'); ?></h3>
    </div>

    <div class="card-body">
        <form action="" method="post" class="mb-3">
            <input type="hidden" name="op" value="add_riga">
            <input type="hidden" name="backto" value="record-edit">
            <input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

            <div class="row">
                <div class="col-md-2">
                    {[ "type": "date", "label": "<?php echo tr('Data'); ?>", "name": "data_riga", "required": 1, "value": "<?php echo date('Y-m-d'); ?>" ]}
                </div>

                <div class="col-md-2">
                    {[ "type": "select", "label": "<?php echo tr('Categoria'); ?>", "name": "categoria", "required": 1, "values": "list=carburante|<?php echo tr('Carburante'); ?>,autostrada|<?php echo tr('Autostrada'); ?>,parcheggio|<?php echo tr('Parcheggio'); ?>,pasto|<?php echo tr('Pasto'); ?>,alloggio|<?php echo tr('Alloggio'); ?>,trasporto|<?php echo tr('Trasporto'); ?>,altro|<?php echo tr('Altro'); ?>" ]}
                </div>

                <div class="col-md-5">
                    {[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione_riga", "required": 1 ]}
                </div>

                <div class="col-md-2">
                    {[ "type": "number", "label": "<?php echo tr('Importo'); ?>", "name": "importo", "required": 1, "value": "0", "min-value": "0", "icon-after": "<?php echo currency(); ?>" ]}
                </div>

                <div class="col-md-1 text-right">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-plus"></i></button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th><?php echo tr('Data'); ?></th>
                        <th><?php echo tr('Categoria'); ?></th>
                        <th><?php echo tr('Descrizione'); ?></th>
                        <th class="text-right"><?php echo tr('Importo'); ?></th>
                        <th class="text-center" width="90"><?php echo tr('Azioni'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($righe as $riga) { ?>
                    <tr>
                        <td><?php echo dateFormat($riga['data']); ?></td>
                        <td><?php echo ucfirst($riga['categoria']); ?></td>
                        <td><?php echo $riga['descrizione']; ?></td>
                        <td class="text-right"><?php echo moneyFormat($riga['importo']); ?></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-xs btn-warning" data-toggle="collapse" data-target="#riga-<?php echo $riga['id']; ?>"><i class="fa fa-edit"></i></button>
                            <form action="" method="post" class="d-inline ask" data-backto="record-edit">
                                <input type="hidden" name="op" value="delete_riga">
                                <input type="hidden" name="backto" value="record-edit">
                                <input type="hidden" name="id_record" value="<?php echo $id_record; ?>">
                                <input type="hidden" name="id_riga" value="<?php echo $riga['id']; ?>">
                                <button type="submit" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <tr class="collapse" id="riga-<?php echo $riga['id']; ?>">
                        <td colspan="5">
                            <form action="" method="post">
                                <input type="hidden" name="op" value="update_riga">
                                <input type="hidden" name="backto" value="record-edit">
                                <input type="hidden" name="id_record" value="<?php echo $id_record; ?>">
                                <input type="hidden" name="id_riga" value="<?php echo $riga['id']; ?>">

                                <div class="row">
                                    <div class="col-md-2">
                                        {[ "type": "date", "label": "<?php echo tr('Data'); ?>", "name": "data_riga", "required": 1, "value": "<?php echo $riga['data']; ?>" ]}
                                    </div>

                                    <div class="col-md-2">
                                        {[ "type": "select", "label": "<?php echo tr('Categoria'); ?>", "name": "categoria", "required": 1, "value": "<?php echo $riga['categoria']; ?>", "values": "list=carburante|<?php echo tr('Carburante'); ?>,autostrada|<?php echo tr('Autostrada'); ?>,parcheggio|<?php echo tr('Parcheggio'); ?>,pasto|<?php echo tr('Pasto'); ?>,alloggio|<?php echo tr('Alloggio'); ?>,trasporto|<?php echo tr('Trasporto'); ?>,altro|<?php echo tr('Altro'); ?>" ]}
                                    </div>

                                    <div class="col-md-5">
                                        {[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione_riga", "required": 1, "value": "<?php echo prepareToField($riga['descrizione']); ?>" ]}
                                    </div>

                                    <div class="col-md-2">
                                        {[ "type": "number", "label": "<?php echo tr('Importo'); ?>", "name": "importo", "required": 1, "value": "<?php echo numberFormat($riga['importo']); ?>", "min-value": "0", "icon-after": "<?php echo currency(); ?>" ]}
                                    </div>

                                    <div class="col-md-1 text-right">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn btn-success btn-block"><i class="fa fa-save"></i></button>
                                    </div>
                                </div>
                            </form>
                        </td>
                    </tr>
                    <?php } ?>

                    <?php if (empty($righe)) { ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted"><?php echo tr('Nessuna riga inserita.'); ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-right"><?php echo tr('Totale'); ?></th>
                        <th class="text-right"><?php echo moneyFormat($totale); ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

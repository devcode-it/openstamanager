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

echo '
<table class="table table-hover table-bordered table-condensed">
    <thead>
        <tr>
            <th>'.tr('Nome').'</th>
            <th class="text-center">'.tr('Versione').'</th>
            <th class="text-center">'.tr('Stato').'</th>
            <th class="text-center">#</th>
        </tr>
    </thead>

    <tbody>';

$moduli = Modules::getHierarchy();
echo renderElencoModuli($moduli);

echo '
    </tbody>
</table>

<script>
function disabilitaModulo(button){
    const riga = $(button).closest("tr");
    const id = riga.data("id");

    const nome = riga.data("nome");
    const tipo = riga.data("tipo");
    const nome_tipo = riga.data("nome_tipo");

    swal({
        title: "'.tr('Disabilitare il _TYPE_?', [
            '_TYPE_' => '" + nome_tipo + "',
        ]).'",
        html: "'.tr('Sei sicuro di voler disabilitare il _TYPE_ _NAME_?', [
            '_TYPE_' => '" + nome_tipo + "',
            '_NAME_' => '" + nome + "',
        ]).'",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Continua').'"
    }).then(function (result) {
        let restore = buttonLoading(button);

        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            dataType: "JSON",
            data: {
                id_module: globals.id_module,
                op: "disabilita-modulo",
                tipo: tipo,
                id: id,
            },
            success: function (response) {
                caricaElencoModuli();
                renderMessages();
            },
            error: function() {
                buttonRestore(button, restore);

                swal({
                    type: "error",
                    title: globals.translations.ajax.error.title,
                    text: globals.translations.ajax.error.text,
                });
            }
        });
    })
}

function abilitaModulo(button) {
    const riga = $(button).closest("tr");
    const id = riga.data("id");

    const nome = riga.data("nome");
    const tipo = riga.data("tipo");
    const nome_tipo = riga.data("nome_tipo");

    swal({
        title: "'.tr('Abilitare il _TYPE_?', [
            '_TYPE_' => '" + nome_tipo + "',
        ]).'",
        html: "'.tr('Sei sicuro di voler abilitare il _TYPE_ _NAME_?', [
            '_TYPE_' => '" + nome_tipo + "',
            '_NAME_' => '" + nome + "',
        ]).'",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Continua').'"
    }).then(function (result) {
        let restore = buttonLoading(button);

        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            dataType: "JSON",
            data: {
                id_module: globals.id_module,
                op: "abilita-modulo",
                tipo: tipo,
                id: id,
            },
            success: function (response) {
                caricaElencoModuli();
                renderMessages();
            },
            error: function() {
                buttonRestore(button, restore);

                swal({
                    type: "error",
                    title: globals.translations.ajax.error.title,
                    text: globals.translations.ajax.error.text,
                });
            }
        });
    })
}

function abilitaSottoModuli(button) {
    const riga = $(button).closest("tr");
    const id = riga.data("id");

    const nome = riga.data("nome");
    const tipo = riga.data("tipo");
    const nome_tipo = riga.data("nome_tipo");

    swal({
        title: "'.tr('Abilitare tutti i sotto-moduli?').'",
        html: "'.tr('Sei sicuro di voler abilitare tutti i sotto-moduli del modulo _NAME_?', [
            '_NAME_' => '" + nome + "',
        ]).'",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Continua').'"
    }).then(function (result) {
        let restore = buttonLoading(button);

        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            dataType: "JSON",
            data: {
                id_module: globals.id_module,
                op: "abilita-sotto-modulo",
                tipo: tipo,
                id: id,
            },
            success: function (response) {
                caricaElencoModuli();
                renderMessages();
            },
            error: function() {
                buttonRestore(button, restore);

                swal({
                    type: "error",
                    title: globals.translations.ajax.error.title,
                    text: globals.translations.ajax.error.text,
                });
            }
        });
    })
}

function rimuoviModulo(button) {
    const riga = $(button).closest("tr");
    const id = riga.data("id");

    const nome = riga.data("nome");
    const tipo = riga.data("tipo");
    const nome_tipo = riga.data("nome_tipo");

    swal({
        title: "'.tr('Rimuovere il _TYPE_?', [
            '_TYPE_' => '" + nome_tipo + "',
        ]).'",
        html: "'.tr('Sei sicuro di voler rimuovere il _TYPE_ _NAME_?', [
            '_TYPE_' => '" + nome_tipo + "',
            '_NAME_' => '" + nome + "',
        ]).'<br>'.tr('Questa operazione è irreversibile e provocherà la potenziale perdita dei dati attualmente collegati al _TYPE_', [
            '_TYPE_' => '" + nome_tipo + "',
        ]).'.",
        type: "warning",
        showCancelButton: true,
        confirmButtonText: "'.tr('Continua').'"
    }).then(function (result) {
        let restore = buttonLoading(button);

        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "POST",
            dataType: "JSON",
            data: {
                id_module: globals.id_module,
                op: "rimuovi-modulo",
                tipo: tipo,
                id: id,
            },
            success: function (response) {
                caricaElencoModuli();
                renderMessages();
            },
            error: function() {
                buttonRestore(button, restore);

                swal({
                    type: "error",
                    title: globals.translations.ajax.error.title,
                    text: globals.translations.ajax.error.text,
                });
            }
        });
    })
}
</script>

<style>
.depth-0 {
    filter: brightness(1);
}

.depth-1 {
    filter: brightness(1.05);
}

.depth-2 {
    filter: brightness(1.1);
}

.depth-3 {
    filter: brightness(1.15);
}
</style>';

function renderElencoModuli($elenco, $depth = 0)
{
    $versione_gestionale = Update::getVersion();
    $moduli_sempre_attivi = ['Utenti e permessi', 'Stato dei servizi'];

    $result = '';
    foreach ($elenco as $record) {
        $record_bloccato = in_array($record['name'], $moduli_sempre_attivi);

        $is_plugin = !empty($record['idmodule_to']);
        $nome_tipo = string_lowercase($is_plugin ? tr('Plugin') : tr('Modulo'));

        // Render per sotto-moduli
        $sotto_moduli = renderElencoModuli($record['all_children'], $depth + 1);

        $elenco_plugin = null;
        if (empty($record['idmodule_to'])) {
            $plugins = database()->table('zz_plugins')
                ->where('idmodule_to', '=', $record['id'])
                ->get()->map(function ($i) {
                    return (array) $i;
                })->toArray();

            $elenco_plugin = renderElencoModuli($plugins, $depth + 1);
        }

        // COMPATIBILITA'
        // Controllo per ogni versione se la regexp combacia per dire che è compatibile o meno
        $compatibile = false;
        $versioni_compatibili = explode(',', $record['compatibility']);
        foreach ($versioni_compatibili as $versione) {
            $compatibile = (preg_match('/'.$versione.'/', $versione_gestionale)) ? true : $compatibile;
        }

        if ($compatibile) {
            $class = ($record['enabled']) ? 'success' : 'warning';
        } else {
            $class = 'danger';
        }

        $result .= '
        <tr class="'.$class.' depth-'.$depth.'" data-id="'.$record['id'].'" data-nome='.json_encode($record['title']).' data-tipo="'.($is_plugin ? 'plugin' : 'module').'" data-nome_tipo='.json_encode($nome_tipo).'>
            <td>
                '.str_repeat('&nbsp;', $depth * 4).'- '.$record['title'].'
                '.($compatibile ? '' :
                    '<div class="tip pull-right" data-toggle="tooltip" title="'.tr('Non compatibile!').' '.tr('Questo _TYPE_ è compatibile solo con le versioni: _LIST_', [
                        '_TYPE_' => $nome_tipo,
                        '_LIST_' => $record['compatibility'],
                    ]).'">
                        <span class="label label-danger">
                            <i class="fa fa-warning" title="'.tr('Non compatibile!').'"></i>
                        </span>
                    </div>'
        ).'

                '.($is_plugin ? '<span class="badge pull-right" style="margin-right: 5px">'.tr('Plugin').'</span>' : '').'
            </td>
            <td class="text-center">'.$record['version'].'</td>
            <td class="text-center">';

        // Possibilità di disabilitare o abilitare il moduli/plugin
        if (!$record_bloccato) {
            if ($record['enabled']) {
                $result .= '
                <div class="tip" data-toggle="tooltip" title="'.tr('Questo _TYPE_ è abilitato: clicca qui per disabilitarlo', [
                    '_TYPE_' => $nome_tipo,
                ]).'">
                    <button type="button" class="btn btn-warning btn-xs" onclick="disabilitaModulo(this)">
                        <i class="fa fa-power-off" title="'.tr('Disabilita').'"></i>
                    </button>
                </div>';

                // Possibilità di abilitare tutti i sottomoduli
                $sotto_moduli_disabilitato = strpos($sotto_moduli, 'fa fa-plug') !== false;
                if ($sotto_moduli_disabilitato) {
                    $result .= '
                <div class="tip" data-toggle="tooltip" title="'.tr('Abilita tutti i sotto-moduli').'">
                    <button type="button" class="btn btn-success btn-xs" onclick="abilitaSottoModuli(this)">
                        <i class="fa fa-recycle" title="'.tr('Abilita sotto-moduli').'"></i>
                    </button>
                </div>';
                }
            } else {
                $result .= '
                <div class="tip" data-toggle="tooltip" title="'.tr('Questo _TYPE_ è disabilitato: clicca qui per abilitarlo', [
                    '_TYPE_' => $nome_tipo,
                ]).'">
                    <button type="button" class="btn btn-success btn-xs" onclick="abilitaModulo(this)">
                        <i class="fa fa-plug" title="'.tr('Abilita').'"></i>
                    </button>
                </div>';
            }
        } else {
            $result .= '
                <div class="tip" data-toggle="tooltip" title="'.tr('Questo _TYPE_ non può essere disabilitato', [
                    '_TYPE_' => $nome_tipo,
                ]).'">
                    <i class="fa fa-eye" title="'.tr('Modulo sempre abilitato').'"></i>
                </div>';
        }

        $result .= '
            </td>
            <td class="text-center">';

        // Possibilità di disinstallare solo se il modulo/plugin non è tra quelli predefiniti
        if (empty($record['default'])) {
            $result .= '
                <div class="tip" data-toggle="tooltip" title="'.tr('Puoi disintallare questo modulo: clicca qui per procedere').'">
                    <button type="button" class="btn btn-danger btn-xs" onclick="rimuoviModulo(this)">
                        <i class="fa fa-trash" title="'.tr('Disinstalla').'"></i>
                    </button>
                </div>';
        } else {
            $result .= '
                <div class="tip" data-toggle="tooltip" title="'.tr('Questo _TYPE_ non può essere disinstallato', [
                    '_TYPE_' => $nome_tipo,
                ]).'">
                    <i class="fa fa-trash text-muted" title="'.tr('Modulo non disinstallabile').'"></i>
                </div>';
        }

        $result .= '
            </td>
        </tr>';

        $result .= $elenco_plugin;
        $result .= $sotto_moduli;
    }

    return $result;
}

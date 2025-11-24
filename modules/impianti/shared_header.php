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

use Modules\Impianti\Impianto;

// Controllo che l'impianto sia disponibile
if (empty($record)) {
    return;
}

// Carico l'impianto con Eloquent per avere accesso alle relazioni
$impianto = Impianto::find($id_record);
if (empty($impianto)) {
    return;
}

// Anagrafica cliente
$anagrafica = $impianto->anagrafica;
if (empty($anagrafica)) {
    return;
}

// Sede
$sede = null;
if (!empty($record['idsede'])) {
    $sede = $dbo->fetchOne('SELECT * FROM an_sedi WHERE id = '.prepare($record['idsede']));
} else {
    $sede = $anagrafica ? $anagrafica->toArray() : null;
}

// Categoria e sottocategoria
$categoria = null;
$sottocategoria = null;
if (!empty($record['id_categoria'])) {
    $categoria = $dbo->fetchOne('SELECT zz_categorie.*, zz_categorie_lang.title FROM zz_categorie LEFT JOIN zz_categorie_lang ON (zz_categorie.id = zz_categorie_lang.id_record AND zz_categorie_lang.id_lang = '.prepare(Models\Locale::getDefault()->id).') WHERE zz_categorie.id = '.prepare($record['id_categoria']));
}
if (!empty($record['id_sottocategoria'])) {
    $sottocategoria = $dbo->fetchOne('SELECT zz_categorie.*, zz_categorie_lang.title FROM zz_categorie LEFT JOIN zz_categorie_lang ON (zz_categorie.id = zz_categorie_lang.id_record AND zz_categorie_lang.id_lang = '.prepare(Models\Locale::getDefault()->id).') WHERE zz_categorie.id = '.prepare($record['id_sottocategoria']));
}

// Marca e modello
$marca = null;
$modello = null;
if (!empty($record['id_marca'])) {
    $marca = $dbo->fetchOne('SELECT * FROM zz_marche WHERE id = '.prepare($record['id_marca']).' AND deleted_at IS NULL');
}
if (!empty($record['id_modello'])) {
    $modello = $dbo->fetchOne('SELECT * FROM zz_marche WHERE id = '.prepare($record['id_modello']).' AND deleted_at IS NULL');
}

echo '
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card card-outline card-primary shadow">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-cogs"></i> <span style="color: #000;">'.tr('Informazioni Impianto').'</span></h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="mb-2"><b><i class="fa fa-cog text-primary mr-1"></i> '.$record['nome'].'</b></h4>

                                <p class="mb-2">
                                    <span class="badge badge-secondary"><i class="fa fa-barcode mr-1"></i> '.$record['matricola'].'</span>
                                    '.(!empty($categoria['title']) ? '<span class="badge badge-info ml-1"><i class="fa fa-tag mr-1"></i> '.$categoria['title'].'</span>' : '').'
                                    '.(!empty($sottocategoria['title']) ? '<span class="badge badge-light ml-1">'.$sottocategoria['title'].'</span>' : '').'
                                </p>

                                '.(!empty($record['descrizione']) ? '<p class="text-muted mb-2"><i class="fa fa-info-circle mr-1"></i> '.$record['descrizione'].'</p>' : '').'

                                <!-- Marca e Modello a sinistra -->';

// Informazioni tecniche
if (!empty($marca['name']) || !empty($modello['name'])) {
    echo '<div class="mb-3">';
    if (!empty($marca['name'])) {
        echo '<h5 class="mb-1"><strong>'.tr('Marca').':</strong> '.$marca['name'].'</h5>';
    }
    if (!empty($modello['name'])) {
        echo '<h5 class="mb-1"><strong>'.tr('Modello').':</strong> '.$modello['name'].'</h5>';
    }
    echo '</div>';
}

echo '
                            </div>

                            <div class="col-md-6">';

// Data installazione - spostata sopra
if (!empty($record['data'])) {
    echo '<div class="mb-3"><i class="fa fa-calendar text-muted mr-1"></i> <strong>'.tr('Installato il').':</strong> '.Translator::dateToLocale($record['data']).'</div>';
}

echo '
                                <!-- Cliente a destra -->
                                <div class="mb-3">
                                    <h5 class="mb-2">
                                        <i class="fa fa-user text-primary mr-1"></i>
                                        <strong>'.$anagrafica->ragione_sociale.'</strong>
                                    </h5>
                                </div>

                                <div class="mt-2">';

// Contatti sede/cliente
if (!empty($sede['telefono']) || !empty($sede['email']) || !empty($anagrafica->telefono) || !empty($anagrafica->email)) {
    echo '<div class="mt-3">';
    if (!empty($sede['telefono'])) {
        echo '<a class="btn btn-light btn-xs mr-1 mb-1" href="tel:'.$sede['telefono'].'" target="_blank"><i class="fa fa-phone text-primary"></i> '.$sede['telefono'].'</a>';
    } elseif (!empty($anagrafica->telefono)) {
        echo '<a class="btn btn-light btn-xs mr-1 mb-1" href="tel:'.$anagrafica->telefono.'" target="_blank"><i class="fa fa-phone text-primary"></i> '.$anagrafica->telefono.'</a>';
    }
    
    if (!empty($sede['email'])) {
        echo '<a class="btn btn-light btn-xs mr-1 mb-1" href="mailto:'.$sede['email'].'"><i class="fa fa-envelope text-primary"></i> '.$sede['email'].'</a>';
    } elseif (!empty($anagrafica->email)) {
        echo '<a class="btn btn-light btn-xs mr-1 mb-1" href="mailto:'.$anagrafica->email.'"><i class="fa fa-envelope text-primary"></i> '.$anagrafica->email.'</a>';
    }
    echo '</div>';
}

echo '
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

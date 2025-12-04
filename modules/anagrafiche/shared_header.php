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

use Models\Module;
use Models\Upload;
use Modules\Anagrafiche\Anagrafica;

// Controllo che l'anagrafica sia disponibile
if (empty($anagrafica)) {
    return;
}

// Logo
$logo_record = Upload::where('id_module', Module::where('name', 'Anagrafiche')->first()->id)
    ->where('id_record', $anagrafica->idanagrafica)
    ->where('name', 'Logo azienda')
    ->first();

// Tipologie anagrafica
$tipologie = $anagrafica->tipi->pluck('title')->toArray();
$tipologie_string = !empty($tipologie) ? implode(', ', $tipologie) : tr('Nessuna tipologia');

// Sedi aggiuntive
$sedi_aggiuntive = $anagrafica->sedi()->count();

// Referenti
$referenti = $dbo->fetchArray('SELECT * FROM an_referenti WHERE idanagrafica = '.prepare($anagrafica->idanagrafica).' ORDER BY nome');

echo '
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card card-outline card-primary shadow">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-vcard"></i> <span style="color: #000;">'.tr('Informazioni Anagrafica').'</span></h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-10">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="mb-2"><b>'.$anagrafica->ragione_sociale.'</b></h4>
                                
                                <p class="mb-2">
                                    '.(!empty($anagrafica->indirizzo) ? '<i class="fa fa-map-marker text-muted mr-1"></i> '.$anagrafica->indirizzo.(!empty($anagrafica->indirizzo2) ? ', '.$anagrafica->indirizzo2 : '').'<br>' : '').'
                                    '.(!empty($anagrafica->cap) || !empty($anagrafica->citta) ? '<i class="fa fa-map text-muted mr-1"></i> '.trim(($anagrafica->cap ?? '').' - '.($anagrafica->citta ?? '').' '.($anagrafica->provincia ?? ''), ' -()') : '').'
                                </p>
                                
                                <div class="mb-2">
                                    '.(!empty($anagrafica->codice) ? '<i class="fa fa-barcode mr-1"></i> '.$anagrafica->codice : '').'
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <h5 class="mb-2">
                                        <i class="fa fa-info-circle text-primary mr-1"></i> 
                                        <strong>'.tr('Dati Fiscali').'</strong>
                                    </h5>
                                    <p class="text-muted mb-2">
                                        '.(!empty($anagrafica->piva) ? '<i class="fa fa-building text-muted mr-1"></i> <strong>'.tr('P.IVA').':</strong> '.$anagrafica->piva.'<br>' : '').'
                                        '.(!empty($anagrafica->codice_fiscale) ? '<i class="fa fa-id-card text-muted mr-1"></i> <strong>'.tr('C.F.').':</strong> '.$anagrafica->codice_fiscale : '').'
                                    </p>
                                </div>
                                
                                <div class="mt-2">
                                    '.(!empty($anagrafica->telefono) ? '<a class="btn btn-light btn-xs mr-1 mb-1" href="tel:'.$anagrafica->telefono.'" target="_blank"><i class="fa fa-phone text-primary"></i> '.$anagrafica->telefono.'</a>' : '').'
                                    '.(!empty($anagrafica->cellulare) ? '<a class="btn btn-light btn-xs mr-1 mb-1" href="tel:'.$anagrafica->cellulare.'" target="_blank"><i class="fa fa-mobile text-primary"></i> '.$anagrafica->cellulare.'</a>' : '').'
                                    '.(!empty($anagrafica->email) ? '<a class="btn btn-light btn-xs mr-1 mb-1" href="mailto:'.$anagrafica->email.'"><i class="fa fa-envelope text-primary"></i> '.$anagrafica->email.'</a>' : '').'
                                    '.(!empty($anagrafica->pec) ? '<a class="btn btn-light btn-xs mr-1 mb-1" href="mailto:'.$anagrafica->pec.'"><i class="fa fa-envelope-o text-primary"></i> PEC</a>' : '').'
                                    '.(!empty($anagrafica->sitoweb) ? '<a class="btn btn-light btn-xs mr-1 mb-1" href="'.(str_starts_with((string) $anagrafica->sitoweb, 'http') ? $anagrafica->sitoweb : 'http://'.$anagrafica->sitoweb).'" target="_blank"><i class="fa fa-globe text-primary"></i> '.tr('Sito web').'</a>' : '').'
                                </div>';

// Mostra informazioni su sedi aggiuntive
if ($sedi_aggiuntive > 0) {
    echo '
                                <div class="mt-3">
                                    <div class="alert alert-info py-2 mb-2">
                                        <i class="fa fa-building-o mr-1"></i> 
                                        <strong>'.tr('Sedi aggiuntive').':</strong> '.$sedi_aggiuntive.'
                                    </div>
                                </div>';
}

// Mostra referenti se presenti
if (!empty($referenti)) {
    echo '
                                <div class="mt-3">
                                    <h6><i class="fa fa-users text-muted mr-1"></i> <strong>'.tr('Referenti').':</strong></h6>';

    foreach ($referenti as $referente) {
        echo '
                                    <div class="mb-2">
                                        <span class="text-muted"><i class="fa fa-user-o mr-1"></i> '.$referente['nome'].'</span>
                                        '.(!empty($referente['telefono']) ? '<a class="btn btn-light btn-xs ml-2" href="tel:'.$referente['telefono'].'" target="_blank"><i class="fa fa-phone text-primary"></i> '.$referente['telefono'].'</a>' : '').'
                                        '.(!empty($referente['email']) ? '<a class="btn btn-light btn-xs ml-1" href="mailto:'.$referente['email'].'"><i class="fa fa-envelope text-primary"></i> '.$referente['email'].'</a>' : '').'
                                    </div>';
    }

    echo '
                                </div>';
}

echo '
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>';

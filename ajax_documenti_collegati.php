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

use Common\DocumentiCollegati;
use Models\Module;

// Pulisci l'output buffer prima di tutto
while (ob_get_level() > 0) {
    ob_end_clean();
}

include_once __DIR__.'/core.php';

// Abilita error reporting per debug
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Verifica che l'id_record sia valido
if (empty($id_record) || !is_numeric($id_record)) {
    $count_only = isset($_GET['count_only']) && $_GET['count_only'] == '1';
    
    if ($count_only) {
        header('Content-Type: application/json');
        echo json_encode(['count' => 0, 'error' => 'ID record non valido']);
    } else {
        echo '<div class="alert alert-warning">'.tr('ID record non valido').'</div>';
    }
    exit;
}

// Verifica che id_module sia presente
if (empty($id_module)) {
    $count_only = isset($_GET['count_only']) && $_GET['count_only'] == '1';
    
    if ($count_only) {
        header('Content-Type: application/json');
        echo json_encode(['count' => 0, 'error' => 'ID modulo non valido']);
    } else {
        echo '<div class="alert alert-warning">'.tr('ID modulo non valido').'</div>';
    }
    exit;
}

// Verifica se è richiesto solo il conteggio
$count_only = isset($_GET['count_only']) && $_GET['count_only'] == '1';

try {
    // Recupera informazioni sul modulo corrente
    $module = Module::find($id_module);
    if (empty($module)) {
        if ($count_only) {
            header('Content-Type: application/json');
            echo json_encode(['count' => 0, 'error' => 'Modulo non trovato']);
        } else {
            echo '<div class="alert alert-warning">'.tr('Modulo non trovato').'</div>';
        }
        exit;
    }

    $module_name = $module->name;

    // Determina il tipo di record in base al nome del modulo
    $tipo_record = 'intervento'; // Default

    switch ($module_name) {
        case 'Interventi':
            $tipo_record = 'intervento';
            break;
        case 'Fatture di vendita':
            $tipo_record = 'fattura_vendita';
            break;
        case 'Fatture di acquisto':
            $tipo_record = 'fattura_acquisto';
            break;
        case 'Contratti':
            $tipo_record = 'contratto';
            break;
        case 'Preventivi':
            $tipo_record = 'preventivo';
            break;
        case 'Ordini cliente':
        case 'Ordini fornitore':
            $tipo_record = 'ordine';
            break;
        case 'Ddt in entrata':
        case 'Ddt in uscita':
            $tipo_record = 'ddt';
            break;
        default:
            // Per altri moduli, usa il default
            $tipo_record = 'intervento';
            break;
    }

    // Gestisci la richiesta AJAX
    DocumentiCollegati::handleAjaxRequest($id_record, $tipo_record, $count_only);
} catch (Exception $e) {
    // Gestione errori generici
    if ($count_only) {
        header('Content-Type: application/json');
        echo json_encode(['count' => 0, 'error' => 'Errore: '.$e->getMessage()]);
    } else {
        echo '<div class="alert alert-danger">'.tr('Errore nel caricamento dei documenti collegati').': '.$e->getMessage().'</div>';
    }
    exit;
}

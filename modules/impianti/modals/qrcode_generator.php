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

// Salta il controllo dei permessi per permettere la generazione del QR code
$skip_permissions = true;

include_once __DIR__.'/../../../core.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

$url = filter('url');

if (empty($url)) {
    http_response_code(400);
    echo 'URL mancante';
    exit;
}

try {
    // Generazione QR code con endroid/qr-code
    $builder = new Builder(
        new PngWriter(),
        [],
        false,
        $url,
        new Encoding('UTF-8'),
        ErrorCorrectionLevel::Low,
        200,
        10
    );

    $result = $builder->build();

    // Imposta gli header per immagine PNG
    header('Content-Type: image/png');
    
    // Output diretto dell'immagine PNG
    echo $result->getString();
    
} catch (Exception $e) {
    http_response_code(500);
    echo 'Errore nella generazione del QR code: ' . $e->getMessage();
}
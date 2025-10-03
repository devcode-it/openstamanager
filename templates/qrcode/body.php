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

$token_url = base_url().'/?token='.$token['token'];

// Generazione qrcode con endroid/qr-code v6
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

$builder = new Builder(
    new PngWriter(),
    [],
    false,
    $token_url,
    new Encoding('UTF-8'),
    ErrorCorrectionLevel::Low,
    300,
    10
);

$result = $builder->build();

echo '
<div class="text-center" style="width: 100%;">
    <img src="data:image/png;base64,'.base64_encode($result->getString()).'" />
</div>';

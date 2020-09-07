<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

echo '<style>
.barcode {
    padding: 0;
    margin: 0;
    vertical-align: top;
}
.barcode-cell {
    text-align: center;
    vertical-align: middle;
}
</style>';

$width_factor = 0.33 * 110;
$height_factor = 25.93;

$size = $settings['width'] / $width_factor;
$height = $settings['height'] / $height_factor / $size;

$number = 1; // 32
for ($i = 0; $i < $number; ++$i) {
    echo '
    <div class="barcode-cell">
        <barcode code="'.$articolo->barcode.'" type="EAN13" height="'.$height.'" size="'.$size.'" class="barcode" />
    </div>';
}

<?php

include_once __DIR__.'/../../core.php';

echo '<style>
.barcode {
    padding: 4mm;
    padding-bottom: 2.5mm;
    margin: 0;
}
</style>';

$width_factor = 0.33 * 110;
$height_factor = 25.93;

$size = $settings['width'] / $width_factor;
$height = $settings['height'] / $height_factor / $size;

$number = 1; // 32
for ($i = 0; $i < $number; ++$i) {
    echo '<barcode code="'.$articolo->barcode.'" type="EAN13" height="'.$height.'" size="'.$size.'" class="barcode" />';
}

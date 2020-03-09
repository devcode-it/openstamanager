<?php

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

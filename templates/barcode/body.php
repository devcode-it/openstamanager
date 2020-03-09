<?php

include_once __DIR__.'/../../core.php';

echo '<style>
.barcode {
    padding: 4mm;
    padding-bottom: 2.5mm;
    margin: 0;
}
</style>';

$number = 32;
for ($i = 0; $i < $number; ++$i) {
    echo '<barcode code="'.$articolo->barcode.'" type="EAN13" height="1" size="1" class="barcode" />';
}

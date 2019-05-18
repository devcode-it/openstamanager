<?php

// Fix del calcolo del bollo
$fatture = \Modules\Fatture\Fattura::all();
foreach ($fatture as $fattura) {
    $fattura->save();
}

<?php

echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "'.tr('Descrizione').'", "name": "descrizione", "id": "descrizione_riga", "value": '.json_encode($result['descrizione']).', "required": 1, "extra": "rows=\"4\"" ]}
        </div>
    </div>';

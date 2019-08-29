<?php

include_once __DIR__.'/../../core.php';

echo '
<!-- DATI -->
<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title">'.tr('Informazioni').'</h3>
    </div>
    
    <div class="panel-body">
        <div class="row">
            <div class="col-md-4">
                {[ "type": "select", "label": "'.tr('Template email').'", "name": "id_template", "values": "query=SELECT id, name AS descrizione FROM em_templates", "required": 1, "value": "$id_template$", "disabled": 1 ]}
            </div>

            <div class="col-md-4">
                {[ "type": "span", "label": "'.tr('Data di invio').'", "name": "sent_at", "value": "$sent_at$" ]}
            </div>
            
            <div class="col-md-4">
                {[ "type": "span", "label": "'.tr('Ultimo tentativo').'", "name": "failed_at", "value": "$failed_at$" ]}
            </div>
        </div>

    </div>
</div>

<h4>'.tr('Oggetto').'</h4>
<div class="well">
'.$mail->subject.'
</div>
            
<h4>'.tr('Contenuto').'</h4>
<div class="well">
'.$mail->content.'
</div>';

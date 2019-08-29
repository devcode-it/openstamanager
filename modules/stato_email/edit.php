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
                <h4>'.tr('Template').'</h4>
                '.$mail->template->name.'
            </div>

            <div class="col-md-4">
                <h4>'.tr('Data di invio').'</h4>
                '.timestampFormat($mail->sent_at).'
            </div>
            
            <div class="col-md-4">
                <h4>'.tr('Ultimo tentativo').'</h4>
                '.timestampFormat($mail->failed_at).'
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

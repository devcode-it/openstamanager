<?php

echo '
<div class="container">
    <div class="row">
        <div class=col-md-12">
            <h5>'.tr('Legenda').':</h5>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <span class="pull-left icon" style="background-color:#CCFFCC;"></span>
            <span class="text">&nbsp;'.tr('Scadenza pagata').'</span>
        </div>
        <div class="col-md-4">
            <span class="pull-left icon" style="background-color:#ec5353;"></span>
            <span class="text">&nbsp;'.tr('Data concordata superata').'</span>
        </div>
        <div class="col-md-4">
            <span class="pull-left icon" style="background-color:#b3d2e3;"></span>
            <span class="text">&nbsp;'.tr('Data concordata').'</span>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <span class="pull-left icon" style="background-color:#f08080;"></span>
            <span class="text">&nbsp;'.tr('Scaduta').'</span>
        </div>
        <div class="col-md-4">
            <span class="pull-left icon" style="background-color:#f9f9c6;"></span>
            <span class="text">&nbsp;'.tr('Scadenza entro 10 giorni').'</span>
        </div>
        <div class="col-md-4">
            <span class="pull-left icon" style="background-color:#ffffff;"></span>
            <span class="text">&nbsp;Scadenza futura</span>
        </div>
    </div>
</div>';

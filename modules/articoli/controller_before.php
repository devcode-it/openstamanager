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
            <span class="pull-left icon" style="background-color:white;"></span>
            <span class="text">&nbsp;'.tr('Articolo senza soglia minima').'</span>
        </div>
        <div class="col-md-4">
            <span class="pull-left icon" style="background-color:#CCFFCC;"></span>
            <span class="text">&nbsp;'.tr('Quantità superiore alla soglia minima').'</span>
        </div>
        <div class="col-md-4">
            <span class="pull-left icon" style="background-color:#ec5353;"></span>
            <span class="text">&nbsp;'.tr('Quantità inferiore alla soglia minima').'</span>
        </div>
    </div>
</div>';

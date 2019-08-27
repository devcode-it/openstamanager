<?php

if ($newsletter->state == 'DEV') {
    echo '
<button type="button" class="btn btn-primary ask" data-msg="'.tr('Procedere ad inviare la newsletter?').'" data-op="send" data-button="'.tr('Invia').'" data-class="btn btn-lg btn-warning">
    <i class="fa fa-envelope"></i> '.tr('Invia newsletter').'
</button>';
}

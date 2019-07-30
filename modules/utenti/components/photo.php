<?php

include_once __DIR__.'/../../core.php';

$user_photo = $user->photo;
if ($user_photo) {
    echo '
        <center><img src="'.$user_photo.'" class="img-responsive" alt="'.$user['username'].'" /></center>';
}

echo '
    <div class="row">
		 <div class="col-md-12">
            {[ "type": "file", "label": "'.tr('Foto utente').'", "name": "photo", "help": "'.tr('Dimensione consigliata 100x100 pixel').'" ]}
        </div>
    </div>';

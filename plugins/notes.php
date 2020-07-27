<?php

include_once __DIR__.'/../core.php';

if (count($notes) > 0) {
    echo '
        <div class="box box-info direct-chat direct-chat-info">
            <div class="box-header with-border">
                <h3 class="box-title">'.tr('Note interne').'</h3>
            </div>

            <div class="box-body">
                <div class="direct-chat-messages" style="height: 50vh">';

    foreach ($notes as $nota) {
        $utente = $nota->user;
        $photo = $utente->photo;

        echo '
                    <div class="direct-chat-msg '.($utente->id == $user->id ? 'right' : '').'" id="nota_'.$nota->id.'">
                        <div class="direct-chat-info clearfix">
                            <span class="direct-chat-name pull-left">'.$utente->nome_completo.'</span>
                            <span class="direct-chat-timestamp pull-right">
                                '.timestampFormat($nota->created_at).'
                            </span>
                        </div>';

        if ($photo) {
            echo '
                        <img class="direct-chat-img" src="'.$photo.'">';
        } else {
            echo '
                
                        <i class="fa fa-user-circle-o direct-chat-img fa-3x" alt="'.tr('OpenSTAManager').'"></i>';
        }

        echo '
                        <div class="direct-chat-text">
                            <div class="pull-right">';

        if (!empty($nota->notification_date)) {
            echo '
                                <span class="label label-default tip" title="'.tr('Data di notifica').'" style="margin-right: 5px">
                                    <i class="fa fa-bell"></i> '.dateFormat($nota->notification_date).'
                                </span>
                                
                                <button type="button" class="btn btn-info btn-xs ask" data-op="notification_nota" data-id_nota="'.$nota->id.'" data-msg="'.tr('Rimuovere la data di notifica da questa nota?').'" data-backto="record-edit" data-button="'.tr('Rimuovi').'" data-class="btn btn-lg btn-warning">
                                    <i class="fa fa-eye"></i>
                                </button>';
        }

        if ($user->is_admin || $utente->id == $user->id) {
            echo '
                                <button type="button" class="btn btn-danger btn-xs ask" data-op="delete_nota" data-id_nota="'.$nota->id.'" data-msg="'.tr('Rimuovere questa nota?').'" data-backto="record-edit">
                                    <i class="fa fa-trash-o"></i>
                                </button>';
        }

        echo '
                            </div>
                            '.$nota->content.'
                        </div>
                    </div>';
    }
    echo '
                </div>
            </div>
        </div>';
} else {
    echo '
        <div class="alert alert-info" ><i class="fa fa-info-circle" ></i> <b>'.tr('Informazione:').'</b> '.tr('Non sono presenti note interne.').'</div>';
}

if ($structure->permission == 'rw') {
    echo '
        <form action="" method="post">
            <input type="hidden" name="op" value="add_nota">
            <input type="hidden" name="backto" value="record-edit">
            <div class="row" >
                <div class="col-md-12" >
                    {[ "type": "ckeditor", "label": "'.tr('Nuova nota').'", "name": "contenuto", "required": 1, "class": "unblockable" ]}
                </div>
                <div class="col-md-4" >
                    {[ "type": "date", "label": "'.tr('Data di notifica').'", "name": "data_notifica", "class": "unblockable", "help": "'.tr('Eventuale data di notifica di un promemoria di questa nota.').'" ]}
                </div>
            </div>
            <!-- PULSANTI -->
            <div class="row">
                <div class="col-md-12 text-right">
                    <button type="submit"  class="btn btn-primary" disabled id="aggiungi_nota" >
                        <i class="fa fa-plus"></i> '.tr('Aggiungi').'
                    </button>
                </div>
            </div>
        </form>';
}

echo '
<script>
    $(document).ready(function(){
        CKEDITOR.instances["contenuto"].on("key", function() {
            setTimeout(function(){
                if(CKEDITOR.instances["contenuto"].getData() == ""){
                    $("#aggiungi_nota").prop("disabled", true);
                }
                else $("#aggiungi_nota").prop("disabled", false);
            }, 10);
        });
    });
</script>';

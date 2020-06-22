<?php

if (( $record['attempt'] >= 10) && empty($record['sent_at'])) {

        echo '
        <span class="label label-danger">
            <i class="fa fa-times"></i> '.tr('Email fallita il: ').Translator::timestampToLocale($record['failed_at']).'
        </span> &nbsp;';

        echo '
        <a class="btn btn-warning ask" data-backto="record-edit" data-msg="'.tr("Rimettere in coda l'email?").'" data-op="retry" data-button="'.tr('Rimetti in coda').'" data-class="btn btn-lg btn-warning" >
            <i class="fa fa-refresh"></i> '.tr('Rimetti in coda').'
        </a>';

        echo '
        <a class="btn btn-info ask" data-backto="record-edit" data-msg="'.tr("Inviare immediatamente l'email?").'" data-op="send" data-button="'.tr('Invia').'" data-class="btn btn-lg btn-info" >
            <i class="fa fa-envelope"></i> '.tr('Invia immeditamente').'
        </a>';

}else if (!empty($record['sent_at'])) {

    echo '
    <span class="label label-success">
        <i class="fa fa-success"></i> '.tr('Email inviata il: ').Translator::timestampToLocale($record['sent_at']).'
    </span>';

}
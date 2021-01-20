<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

if (($record['attempt'] >= 10) && empty($record['sent_at'])) {
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
} elseif (!empty($record['sent_at'])) {
    echo '
    <span class="label label-success">
        <i class="fa fa-success"></i> '.tr('Email inviata il: ').Translator::timestampToLocale($record['sent_at']).'
    </span>';
}

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

if ($newsletter->state == 'DEV') {
    echo '
<button type="button" class="btn btn-primary ask" data-msg="'.tr('Procedere ad inviare la newsletter?').'" data-op="send" data-button="'.tr('Invia').'" data-class="btn btn-lg btn-warning">
    <i class="fa fa-envelope"></i> '.tr('Invia newsletter').'
</button>';
} elseif ($newsletter->state == 'WAIT') {
    echo '
<button type="button" class="btn btn-danger ask" data-msg="'.tr('Svuotare la coda di invio della newsletter?').'" data-op="block" data-button="'.tr('Svuota').'" data-class="btn btn-lg btn-warning">
    <i class="fa fa-envelope"></i> '.tr('Svuota coda newsletter').'
</button>';
}

<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

echo '
<a class="btn btn-warning ask" data-backto="record-edit" data-method="post" data-op="import" data-msg="'.tr('Vuoi impostare tutte le tariffe dei tecnici a questi valori?').'" data-button="'.tr('Applica').'" data-class="btn btn-lg btn-warning">
    <i class="fa fa-upload"></i> '.tr('Applica a tutti i tecnici').'
</a>';

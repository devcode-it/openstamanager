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

echo '
<h5 style="border-bottom:1px solid #777; display:block;">
    <div class="col-xs-5">STAMPA BILANCIO <small>'.dateFormat($date_start).' - '.dateFormat($date_end).'</small></div>
    <div class="col-xs-7 text-right">'.$azienda['ragione_sociale'].'</div>
</h5><br>
<h4 class="text-center">ESERCIZIO '.$esercizio.'</h4><br>';

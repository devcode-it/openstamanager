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

include_once __DIR__.'/core.php';

$pageTitle = tr('Scorciatoie da tastiera');

$paths = App::getPaths();

include_once App::filepath('include|custom|', 'top.php');

echo '
<div class="box">
    <div class="box-header">
        <div class="box-title">
            <i class="fa fa-lightbulb-o"></i> '.tr('Scorciatoie da tastiera').'
        </div>
    </div>

    <div class="box-body">
        <p>Scorciatoie raggiungibili dall\'interno dei singoli record:</p>
        <ul>
            <li><b>F1:</b> Viene aperto il modal di inserimento di un nuovo record corrispondendo al modulo in cui ci si trova.</li>
            <li><b>F2:</b> Viene effettuato un salvataggio del record corrente.</li>
            <li><b>F3:</b> Viene lanciata la stampa impostata come predefinita del record corrente, ove presente.</li>
            <li><b>F4:</b> Viene aperto il modal di invio email, corrispondente al templato impostato come predefinito, ove presente.</li>
        </ul>
    </div>

    <div class="box-body">
        <p>Scorciatoie raggiungibili in fase di aggiunta movimenti:</p>
        <ul>
            <li><b>F7:</b> Sposta il focus sul campo Barcode</li>
            <li><b>F8:</b> Viene selezionata come causale "Carico"</li>
            <li><b>F9:</b> Viene selezionata come causale "Scarico"</li>
            <li><b>F10:</b> Viene selezionata come causale "Spostamento"</li>
        </ul>
	</div>
</div>
<div class="box">
    <div class="box-header">
        <div class="box-title">
            <i class="fa fa-info"></i> '.tr('Verifica impostazione').'
        </div>
    </div>
    <div class="box-body">
        <div class="box-title">
            <div class="col-md-12">';
                if (setting('Attiva scorciatoie da tastiera')) {
                    echo '<p>Le scorciatoie da tastiera sono attive.</p>';
                } else {
                    echo '<p>Scorciatoie da tastiera non attivate, verificare in '.Modules::link('Impostazioni', null, tr('Strumenti/Impostazioni/Generali/<b>Abilita scorciatoie da tastiera</b>')).'.</p>';
                }
            echo'
            </div>
        </div>
    </div>
</div>
';

include_once App::filepath('include|custom|', 'bottom.php');

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

echo '
<p>'.tr('Scorciatoie utilizzabili dalla schermata di modifica record').':</p>
<ul>
    <li><kbd>F1</kbd> '.tr('nuova finestra per l\'inserimento di un nuovo record').'.</li>
    <li><kbd>F2</kbd> '.tr('salvataggio del record corrente').'.</li>
    <li><kbd>F3</kbd> '.tr('generazione della stampa predefinita del record corrente, se presente').'.</li>
    <li><kbd>F4</kbd> '.tr('apertura finestra predefinita di invio email, se presente').'.</li>
</ul><br>';

if (setting('Attiva scorciatoie da tastiera')) {
    echo '<p class="text-muted"><i class="fa fa-check text-success"></i> '.tr('Le scorciatoie da tastiera sono attive').'.</p>';
} else {
    echo '<p class="text-muted"><i class="fa fa-warning text-orange"></i> '.tr('Scorciatoie da tastiera non attivate. Attivale in _LINK_IMPOSTAZIONI_',
        [
            '_LINK_IMPOSTAZIONI_' => Modules::link('Impostazioni', null, tr('Strumenti » Impostazioni » Generali » <b>Abilita scorciatoie da tastiera</b>'))
        ]).'.</p>';
}

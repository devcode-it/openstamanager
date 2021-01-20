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

namespace HTMLBuilder\Handler;

/**
 * Intefaccia utilizzata per interagire con la classe HTMLBuilder.
 *
 * @since 2.3
 */
interface HandlerInterface
{
    /**
     * Gestore pubblico, liberamente implementabile per la creazione del codice HTML.
     *
     * @param array $values
     * @param array $extras
     *
     * @return string
     */
    public function handle(&$values, &$extras);
}

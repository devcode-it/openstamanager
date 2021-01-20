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

function init() {
    // Inizializzazzione dei box AdminLTE
    $('.box').boxWidget();

    // Modal di default
    $('[data-href]').not('.ask, .bound').click(function () {
        launch_modal($(this).data('title'), $(this).data('href'), 1);
    });
    $('[data-href]').not('.ask, .bound').addClass('bound clickable');

    // Tooltip
    $('.tip').not('.tooltipstered').each(function () {
        $this = $(this);
        $this.tooltipster({
            animation: 'grow',
            contentAsHTML: true,
            hideOnClick: true,
            onlyOne: true,
            maxWidth: 350,
            touchDevices: true,
            trigger: 'hover',
            position: $this.data('position') ? $this.data('position') : 'top',
        });
    });

    if ($('form').length) {
        $('form').not('.no-check').parsley();
    }

    // Aggiunta nell'URL del nome tab su cui tornare dopo il submit
    // Blocco del pulsante di submit dopo il primo submit
    $('form').on("submit", function (e) {
        if ($(this).parsley().validate() && (e.result == undefined || e.result)) {
            $(this).submit(function () {
                return false;
            });

            $(this).find('[type=submit]').prop("disabled", true).addClass("disabled");

            prepareForm(this);

            return true;
        }

        return false;
    });

    window.Parsley.on('field:success', function () {
        this.$element.removeClass('parsley-success');
    });

    restart_inputs();
}

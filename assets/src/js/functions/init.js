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
    // Modal di default
    $('[data-href]').not('.ask, .bound').click(function () {
        launch_modal($(this).data('title'), $(this).data('href'), 1);
    });
    $('[data-href]').not('.ask, .bound').addClass('bound clickable');

    // Inizializza tutti i tooltip con Tooltipster
    initTooltips();

    if ($('form').length) {
      $('form').not('.no-check').parsley();

      if (window.CKEDITOR){
        CKEDITOR.on('instanceReady', function () {
          $('form textarea').each(function () {
            if ($(this).data('mandatory') === '1') {
              $(this).prop('required', true);
            }
          });

          $.each(CKEDITOR.instances, function (instance) {
            CKEDITOR.instances[instance].on("change", function (e) {
              for (instance in CKEDITOR.instances) {
                CKEDITOR.instances[instance].updateElement();
                $('form').each(function () {
                  if ($(this).attr('novalidate') == undefined) {
                    $(this).parsley().validate();
                  }
                });
              }
            });
          });
        });
      }
    }

    // Aggiunta nell'URL del nome tab su cui tornare dopo il submit
    // Blocco del pulsante di submit dopo il primo submit
    $('form').on("submit", function (e) {
        if ($(this).parsley().validate() && (e.result == undefined || e.result)) {
            $(this).submit(function () {
                return false;
            });

            //$(this).find('[type=submit]').prop("disabled", true).addClass("disabled");

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

/**
 * Funzione per standardizzare l'inizializzazione dei tooltip utilizzando Tooltipster
 * Converte anche i tooltip Bootstrap (data-toggle="tooltip") in tooltip Tooltipster
 */
function initTooltips() {
    // Opzioni standard per Tooltipster
    const tooltipsterOptions = {
        animation: 'grow',
        contentAsHTML: true,
        hideOnClick: true,
        onlyOne: true,
        maxWidth: 350,
        touchDevices: true,
        trigger: 'hover',
        theme: 'tooltipster-shadow',
        interactive: false,
        speed: 200,
        delay: 200,
        arrow: false, // Disabilita la freccia del tooltip
        border: false, // Disabilita il bordo
        functionReady: function(instance, helper) {
            // Rimuove qualsiasi ombra o bordo quando il tooltip viene mostrato
            $(".tooltipster-base").css({
                "box-shadow": "none",
                "border": "none"
            });

            // Rimuove la freccia del tooltip
            $(".tooltipster-arrow").css("display", "none");
        }
    };

    // Inizializza i tooltip con classe .tip
    $('.tip').not('.tooltipstered').each(function () {
        const $this = $(this);
        const position = $this.data('position') ? $this.data('position') : 'top';

        $this.tooltipster({
            ...tooltipsterOptions,
            position: position,
        });
    });

    // Converti i tooltip Bootstrap (data-toggle="tooltip") in tooltip Tooltipster
    $('[data-toggle="tooltip"]').not('.tooltipstered').each(function () {
        const $this = $(this);

        // Ottieni il titolo dal tooltip Bootstrap
        const title = $this.attr('title') || $this.data('original-title');

        // Rimuovi gli attributi di Bootstrap per evitare conflitti
        $this.removeAttr('data-toggle');
        $this.removeAttr('data-original-title');

        // Aggiungi la classe .tip per coerenza
        $this.addClass('tip');

        // Inizializza Tooltipster
        $this.tooltipster({
            ...tooltipsterOptions,
            content: title,
            position: $this.data('placement') || 'top',
        });
    });

    // Converti i tooltip jQuery UI in tooltip Tooltipster
    $('.ui-tooltip-content').each(function() {
        const $this = $(this);
        const $parent = $this.parent();

        // Ottieni il contenuto
        const content = $this.html();

        // Rimuovi il tooltip jQuery UI
        $parent.remove();

        // Trova l'elemento originale e inizializza Tooltipster
        const $target = $('[aria-describedby="' + $parent.attr('id') + '"]');
        if ($target.length) {
            $target.addClass('tip');
            $target.tooltipster({
                ...tooltipsterOptions,
                content: content,
            });
        }
    });
}

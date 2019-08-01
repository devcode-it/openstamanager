function init() {
    // Inizializzazzione dei box AdminLTE
    $('.box').boxWidget();

    // Modal di default
    $('[data-href]').not('.ask, .bound').click(function () {
        launch_modal($(this).data('title'), $(this).data('href'), $(this).data('target'));
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
    $('form').on("submit", function(e) {
        if ($(this).parsley().validate() && e.result) {
            $(this).submit(function () {
                return false;
            });

            $(this).find('[type=submit]').prop("disabled", true).addClass("disabled");

            $(this).find('input:disabled, select:disabled').prop('disabled', false);

            var hash = window.location.hash;
            if (hash) {
                var input = $('<input/>', {
                    type: 'hidden',
                    name: 'hash',
                    value: hash,
                });

                $(this).append(input);
            }

            return true;
        }

        return false;
    });

    window.Parsley.on('field:success', function () {
        this.$element.removeClass('parsley-success');
    });

    restart_inputs();
}

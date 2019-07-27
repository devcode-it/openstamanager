$(document).ready(function () {
    // Messaggio di avviso salvataggio a comparsa sulla destra solo nella versione a desktop intero
    if ($(window).width() > 1023) {
        var i = 0;

        $('.alert-success.push').each(function () {
            i++;
            tops = 60 * i + 95;

            $(this).css({
                'position': 'fixed',
                'z-index': 3,
                'right': '10px',
                'top': -100,
            }).delay(1000).animate({
                'top': tops,
            }).delay(3000).animate({
                'top': -100,
            });
        });
    }

    // Nascondo la notifica se passo sopra col mouse
    $('.alert-success.push').on('mouseover', function () {
        $(this).stop().animate({
            'top': -100,
            'opacity': 0
        });
    });
});

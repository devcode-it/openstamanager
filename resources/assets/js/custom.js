$(document).ready(function () {
    // Fix per il menu principale
    $('.sidebar-menu').tree({
        followLink: true,
    });

    // Pulsante per il ritorno a inizio pagina
    var slideToTop = $("<div />");
    slideToTop.html('<i class="fa fa-chevron-up"></i>');
    slideToTop.css({
        position: 'fixed',
        bottom: '20px',
        right: '25px',
        width: '40px',
        height: '40px',
        color: '#eee',
        'font-size': '',
        'line-height': '40px',
        'text-align': 'center',
        'background-color': 'rgba(255, 78, 0)',
        'box-shadow': '0 0 10px rgba(0, 0, 0, 0.05)',
        cursor: 'pointer',
        'z-index': '99999',
        opacity: '.7',
        'display': 'none'
    });

    slideToTop.on('mouseenter', function () {
        $(this).css('opacity', '1');
    });

    slideToTop.on('mouseout', function () {
        $(this).css('opacity', '.7');
    });

    $('.wrapper').append(slideToTop);
    $(window).scroll(function () {
        if ($(window).scrollTop() >= 150) {
            if (!$(slideToTop).is(':visible')) {
                $(slideToTop).fadeIn(500);
            }
        } else {
            $(slideToTop).fadeOut(500);
        }
    });

    $(slideToTop).click(function () {
        $("html, body").animate({
            scrollTop: 0
        }, 500);
    });
    
    $(".sidebar-toggle").click(function(){
        setTimeout(function(){
            window.dispatchEvent(new Event('resize'));
        }, 350);
    });
    
    // Forza l'evento "blur" nei campi di testo per formattare i numeri con
    // jquery inputmask prima del submit
    setTimeout( function(){
        $('form').on('submit', function(){
            $('input').trigger('blur');
        });
    }, 1000 );
});

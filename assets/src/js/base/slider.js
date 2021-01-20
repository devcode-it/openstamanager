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

$(document).ready(function () {
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
});

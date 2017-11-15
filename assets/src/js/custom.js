$(document).ready(function () {
    // Fix per il menu principale
    var animationSpeed = 500;
    $(document).off('click', '.sidebar li a')
        .on('click', '.sidebar li a', function (e) {
            //Get the clicked link and the next element
            var $this = $(this);
            var checkElement = $this.next();

            //Check if the next element is a menu and is visible
            if ((checkElement.is('.treeview-menu')) && (checkElement.is(':visible')) && (!$('body').hasClass('sidebar-collapse'))) {
                //Close the menu
                checkElement.slideUp(animationSpeed, function () {
                    checkElement.removeClass('menu-open');
                });
                checkElement.parent("li").removeClass("active");
            }
            //If the menu is not visible
            else if ((checkElement.is('.treeview-menu')) && (!checkElement.is(':visible'))) {
                //Get the parent menu
                var parent = $this.parents('ul').first();
                //Close all open menus within the parent
                var ul = parent.find('ul:visible').slideUp(animationSpeed);
                //Remove the menu-open class from the parent
                ul.removeClass('menu-open');
                //Get the parent li
                var parent_li = $this.parent("li");

                //Open the target menu and add the menu-open class
                checkElement.slideDown(animationSpeed, function () {
                    //Add the class active to the parent li
                    checkElement.addClass('menu-open');
                    parent.find('li.active').removeClass('active');
                    parent_li.addClass('active');
                });
            }
            //if this isn't a link, prevent the page from being redirected
            if (checkElement.is('.treeview-menu') && $(e.target).is('.pull-right-container')) {
                e.preventDefault();
            }
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
});

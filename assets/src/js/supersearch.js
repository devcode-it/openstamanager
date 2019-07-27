$(document).ready(function () {
    $('#supersearch').keyup(function () {
        $(document).ajaxStop();

        if ($(this).val() == '') {
            $(this).removeClass('wait');
        } else {
            $(this).addClass('wait');
        }
    });

    $.widget("custom.supersearch", $.ui.autocomplete, {
        _create: function () {
            this._super();
            this.widget().menu("option", "items", "> :not(.ui-autocomplete-category)");
        },
        _renderMenu: function (ul, items) {
            if (items[0].value == undefined) {
                $('#supersearch').removeClass('wait');
                ul.html('');
            } else {
                var that = this,
                    currentCategory = "";

                ul.addClass('ui-autocomplete-scrollable');
                ul.css('z-index', '999');

                $.each(items, function (index, item) {

                    if (item.category != currentCategory) {
                        ul.append("<li class='ui-autocomplete-category'>" + item.category + "</li>");
                        currentCategory = item.category;
                    }

                    that._renderItemData(ul, item);
                });
            }
        },
        _renderItem: function (ul, item) {
            return $("<li>")
                .append("<a href='" + item.link + "' title='Clicca per aprire'><b>" + item.value + "</b><br/>" + item.label + "</a>")
                .appendTo(ul);
        }
    });

    // Configurazione supersearch
    var $super = $('#supersearch').supersearch({
        minLength: 3,
        select: function (event, ui) {
            location.href = ui.item.link;
        },
        source: function (request, response) {
            $.ajax({
                url: globals.rootdir + '/ajax_search.php',
                dataType: "json",
                data: {
                    term: request.term
                },

                complete: function (jqXHR) {
                    $('#supersearch').removeClass('wait');
                },

                success: function (data) {
                    if (data == null) {
                        response($.map(['a'], function (item) {
                            return false;
                        }));
                    } else {
                        response($.map(data, function (item) {
                            labels = (item.labels).toString();
                            labels = labels.replace('<br/>,', '<br/>');

                            return {
                                label: labels,
                                category: item.category,
                                link: item.link,
                                value: item.title
                            }
                        }));
                    }
                }
            });
        }
    });
});

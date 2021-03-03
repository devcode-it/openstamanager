<!DOCTYPE html>
<html lang="{{ localeLanguage() }}">
    <head>
        <meta charset="UTF-8">
        <title>@yield('title') - {{ tr('OpenSTAManager') }}</title>
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

        <meta name="robots" content="noindex,nofollow">

        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link href="{{ base_url() }}/assets/img/favicon.png" rel="icon" type="image/x-icon" />
        <link rel="manifest" href="{{ base_url() }}/manifest.json">

        <!--link rel="stylesheet" type="text/css" media="all" href="{{ base_url() }}{{ asset('/css/app.css') }}"/>
        <link rel="stylesheet" type="text/css" media="all" href="{{ base_url() }}{{ asset('/css/style.css') }}"/>
        <link rel="stylesheet" type="text/css" media="all" href="{{ base_url() }}{{ asset('/css/themes.css') }}"/>

        <link rel="stylesheet" type="text/css" media="print" href="{{ base_url() }}{{ asset('/css/print.css') }}"/>

        <script type="text/javascript" charset="utf-8" src="{{ base_url() }}{{ asset('/js/manifest.js') }}"></script>
        <script type="text/javascript" charset="utf-8" src="{{ base_url() }}{{ asset('/js/vendor.js') }}"></script>
        <script type="text/javascript" charset="utf-8" src="{{ base_url() }}{{ asset('/js/app.js') }}"></script>
        <script type="text/javascript" charset="utf-8" src="{{ base_url() }}{{ asset('/js/base.js') }}"></script--->

        @foreach (AppLegacy::getAssets()['css'] as $css)
        <link rel="stylesheet" type="text/css" media="all" href="{{ $css }}"/>
        @endforeach

        @foreach (AppLegacy::getAssets()['print'] as $css)
        <link rel="stylesheet" type="text/css" media="print" href="{{ $css }}"/>
        @endforeach

        @foreach (AppLegacy::getAssets()['js'] as $script)
        <script type="text/javascript" charset="utf-8" src="{{ $script }}"></script>
        @endforeach

        <script>
            var globals = {
                content_was_modified: false,
                rootdir: '{{ base_url() }}',

                timestamp_format: "{{ formatter()->getTimestampPattern() }}",
                date_format: "{{ formatter()->getDatePattern() }}",
                time_format: "{{ formatter()->getTimePattern() }}",
                decimals: "{{ formatter()->getNumberSeparators()['decimals'] }}",
                thousands: "{{ formatter()->getNumberSeparators()['thousands'] }}",

                locale: '{{ localeLanguage() }}',
                full_locale: '{{ locale() }}',

                translations: {
                    password: {
                        "wordMinLength": "{{ tr('La password è troppo corta') }}",
                        "wordMaxLength": "{{ tr('La password è troppo lunga') }}",
                        "wordInvalidChar": "{{ tr('La password contiene un carattere non valido') }}",
                        "wordNotEmail": "{{ tr('Non usare la tua e-mail come password') }}",
                        "wordSimilarToUsername": "{{ tr('La password non può contenere il tuo nome') }}",
                        "wordTwoCharacterClasses": "{{ tr('Usa classi di caratteri diversi') }}",
                        "wordRepetitions": "{{ tr('La password contiene ripetizioni') }}",
                        "wordSequences": "{{ tr('La password contiene sequenze') }}",
                        "errorList": "{{ tr('Attenzione') }}:",
                        "veryWeak": "{{ tr('Molto debole') }}",
                        "weak": "{{ tr('Debole') }}",
                        "normal": "{{ tr('Normale') }}",
                        "medium": "{{ tr('Media') }}",
                        "strong": "{{ tr('Forte') }}",
                        "veryStrong": "{{ tr('Molto forte') }}",
                    },
                },

                urls: {
                    messages: "{{ route('messages') }}",
                }
            };

            // CSRF Token automatico per richieste AJAX
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        </script>

        @yield('css')
        @yield('js')
    </head>

    <body class="skin-{{ AppLegacy::getConfig() ? 'default' : 'default' }} @yield('body_class')">
        <!-- Loader principale -->
        <div id="main_loading">
            <div>
                <i class="fa fa-cog fa-spin text-danger"></i>
            </div>
        </div>

        <!-- Loader secondario -->
        <div id="mini-loader" style="display:none;">
            <div></div>
        </div>

        <!-- Loader senza overlay -->
        <div id="tiny-loader" style="display:none;"></div>

        @yield('body')
    </body>
</html>

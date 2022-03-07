<!DOCTYPE html>
<html lang="{{app()->getLocale()}}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <meta name="csrf-token" content="{{csrf_token()}}">
    <title>@lang('OpenSTAManager')</title>

    @client

    <link rel="apple-touch-icon" sizes="180x180" href="{{asset('images/favicon/apple-touch-icon.png')}}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{asset('images/favicon/favicon-32x32.png')}}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{asset('images/favicon/favicon-16x16.png')}}">
    <link rel="mask-icon" href="{{asset('images/favicon/safari-pinned-tab.svg')}}" color="#3f3f3f">
    <link rel="shortcut icon" href="{{asset('images/favicon/favicon.ico')}}">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="msapplication-config" content="{{asset('images/favicon/browserconfig.xml')}}">
    <meta name="theme-color" content="#3f3f3f">
    <meta name="description" content="Your app description">

    @tag('styles')
    @tag('_material')
</head>

<body class="mdc-typography @if(session('high-contrast')) mdc-high-contrast @endif">
@php
    /** @var $modules */
    assert($modules instanceof Illuminate\Support\Collection);

    /** @var string $translations */
    $translations = cache('translations_' . app()->getLocale());
@endphp

@yield('contents')

<script>
    window.modules = @js($modules->pluck('modules')->collapse()->all());
    window.translations = @js($translations);
    window.user = @js(auth()->user());
    window.version = @js(trim(file_get_contents(base_path('VERSION'))));
    window.revision = @js(trim(file_get_contents(base_path('REVISION'))));

    window.theme = @js(session('high-contrast') ? 'high-contrast' : 'light');
</script>

@routes
@tag('app')
</body>
</html>

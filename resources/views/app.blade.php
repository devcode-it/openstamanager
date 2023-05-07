<!DOCTYPE html>
<html lang="{{app()->getLocale()}}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>@lang('OpenSTAManager')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;500;700&family=Plus+Jakarta+Sans:wght@400;500;700&display=swap"
          rel="stylesheet">

    <link rel="apple-touch-icon" sizes="180x180" href="{{Vite::image('favicon/apple-touch-icon.png')}}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{Vite::image('favicon/favicon-32x32.png')}}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{Vite::image('favicon/favicon-16x16.png')}}">
    <link rel="mask-icon" href="{{Vite::image('favicon/safari-pinned-tab.svg')}}" color="#3f3f3f">
    <link rel="shortcut icon" href="{{Vite::image('favicon/favicon.ico')}}">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="msapplication-config" content="{{Vite::image('favicon/browserconfig.xml')}}">
    <meta name="theme-color" content="#3f3f3f">
    <meta name="description" content="Your app description">
</head>

<body>
@php
    /** @var $modules */
    assert($modules instanceof Illuminate\Support\Collection);
@endphp

@inertia
<script>
  app = @js([
    'locale' => app()->getLocale(),
    'modules' => $modules,
    'user' => auth()->user(),
    'VERSION' => trim(file_get_contents(base_path('VERSION'))),
    'REVISION' => trim(file_get_contents(base_path('REVISION'))),
    'settings' => [
        'date_format' => settings('date_format_long'),
        'date_format_short' => settings('date_format_short'),
        'time_format' => settings('time_format'),
]
]);
</script>

@routes
@vite('resources/ts/app.ts')
</body>
</html>

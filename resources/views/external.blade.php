<!DOCTYPE html>
<html lang="{{app()->getLocale()}}">
    @include('layouts.head')
    <body>
        @php
            $modules = app(\App\Http\Controllers\Controller::class)
                        ->getModules(request());
        @endphp
        @inertia
        @include('layouts.footer')
    </body>
</html>

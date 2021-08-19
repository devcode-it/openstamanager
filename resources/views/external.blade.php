<!DOCTYPE html>
<html lang="{{app()->getLocale()}}">
    @include('layouts.head')
    <body>
        @inertia

        @routes

        <script src="{{mix('js/manifest.js')}}" defer></script>
        <script src="{{mix('js/vendor.js')}}" defer></script>
        <script src="{{ mix('js/app.js') }}" defer></script>
    </body>
</html>

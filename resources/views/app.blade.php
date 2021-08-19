<!DOCTYPE html>
<html lang="{{app()->getLocale()}}">
@include('layouts.head')
<body class="mdc-typography @if(session('high-contrast')) mdc-high-contrast @endif">

<top-app-bar>
    @include('layouts.top-app-bar')
    <material-drawer @mobile type="modal" @elsenotmobile type="dismissible" open @endmobile>
        @include('layouts.drawer')
        <div slot="appContent">
            <main>
                @inertia
            </main>
        </div>
    </material-drawer>
    <footer class="@if(session('high-contrast')) mdc-high-contrast @endif">
        <div class="left-footer">
            <span>
                <a href="https://openstamanager.com">
                    @lang('OpenSTAManager')
                </a>
            </span>
        </div>
        <div class="right-footer">
            <b>@lang('Versione')</b> {{file_get_contents(base_path('VERSION'))}} <small>(<code>{{file_get_contents(base_path('REVISION'))}}</code>)</small>
        </div>
    </footer>
</top-app-bar>

@include('layouts.top-app-bar-menus')

@routes

<script src="{{mix('js/manifest.js')}}" defer></script>
<script src="{{mix('js/vendor.js')}}" defer></script>
<script src="{{ mix('js/app.js') }}" defer></script>

@yield('scripts')
</body>
</html>

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
            <b>@lang('Versione')</b> {{trim(file_get_contents(base_path('VERSION')))}}
            <small>(<code>{{trim(file_get_contents(base_path('REVISION')))}}</code>)</small>
        </div>
    </footer>
</top-app-bar>

@include('layouts.top-app-bar-menus')

<script>
  window.importPath = '{{Str::contains(vite_asset(''), config('vite.dev_url')) ? config('vite.dev_url') : '.'}}';
</script>

@routes
@client

@vite('app')

@include('layouts.translations')

</body>
</html>

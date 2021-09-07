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
@client

<!-- Load module outside core -->
<script async src="https://unpkg.com/es-module-shims@0.12.8/dist/es-module-shims.js"></script>
@php
$component = Route::current()->parameter('component');
$split1 = explode('::', $component);
$path = null;
if (count($split1) !== 1) {
    $split = explode('/', $split1[0]);
    $vendor = $split[0];
    $module = $split[1];
    $path = "vendor/$vendor/$module/index.js";
}
@endphp
@empty($path)
@else
<script type="importmap">
{
  "imports": {
    "external_module": "{{vite_asset($path)}}"
  }
}
</script>
    <script type="module">
      import * as extModule from 'external_module';
      window.extmodule = extModule;
    </script>
@endempty

@vite('app')
</body>
</html>

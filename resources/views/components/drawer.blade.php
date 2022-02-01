<mwc-list activatable>
    @php
        assert($modules instanceof \Illuminate\Support\Collection);
        $entries = $modules->pluck('config.drawer_entries')
            ->collapse()
            ->prepend([
                'icon' => 'view-dashboard-outline',
                'text' => __('Dashboard')
            ], 'dashboard');
    @endphp
    @foreach($entries as $route => $details)
        @switch($route)
            @case('hr')
                <li divider padded role="separator"></li>
                @break
            @default
                <a class="drawer-item" href="{{Route::has($route) ? route($route) : ''}}">
                    <mwc-list-item graphic="icon" @if(request()->routeIs($route)) activated @endif>
                        <i class="mdi mdi-{{$details['icon']}}"
                           slot="graphic" aria-hidden="true"></i>
                        <span class="mdc-typography--subtitle2">{{$details['text']}}</span>
                    </mwc-list-item>
                </a>
        @endswitch
    @endforeach
</mwc-list>

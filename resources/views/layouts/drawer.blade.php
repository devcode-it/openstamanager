<mwc-list activatable>
    @php
        $modules = app(\App\Http\Controllers\Controller::class)
            ->getModules(request());
        $entries = [
            'dashboard' => [
                'icon' => 'view-dashboard-outline',
                'text' => __('Dashboard')
            ],
        ];
        foreach ($modules as $module) {
            $to_merge[] = $module->drawer_entries;
        }

        $entries = array_merge($entries, ...$to_merge);
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

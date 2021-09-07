<mwc-list activatable>
    @foreach([
        'tipi-attivita' => [
            'icon' => 'shape',
            'text' => __('Tipi attività')
        ]
    ] as $route => $details)
        @switch($route)
            @case('hr')
            <li divider padded role="separator"></li>
            @break
            @default
            <a href="{{Route::has($route) ? route($route) : $route}}">
                <mwc-list-item graphic="icon" @if(Request::is($route)) selected activated @endif>
                    <i class="mdi mdi-{{$details['icon']}}{{empty($details['no_outline']) ? '-outline' : ''}}"
                       slot="graphic" aria-hidden="true"></i>
                    <span class="mdc-typography--subtitle2">{{$details['text']}}</span>
                </mwc-list-item>
            </a>
        @endswitch
    @endforeach
</mwc-list>

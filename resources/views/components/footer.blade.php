<footer class="@if(session('high-contrast')) mdc-high-contrast @endif">
    <div class="left-footer">
                    <span>
                        <a href="https://openstamanager.com">
                            @lang('OpenSTAManager')
                        </a>
                    </span>
    </div>
    <div class="right-footer">
        <strong>@lang('Versione')</strong> {{trim(file_get_contents(base_path('VERSION')))}}
        <small>(<code>{{trim(file_get_contents(base_path('REVISION')))}}</code>)</small>
    </div>
</footer>

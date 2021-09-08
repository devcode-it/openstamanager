<mwc-icon-button slot="navigationIcon"><i class="mdi mdi-menu"></i></mwc-icon-button>
<div slot="title" style="display: flex; align-items: center;">
    <img src="{{vite_asset('images/logo.png')}}" alt="@lang('OpenSTAManager')" style="height: 50px; margin-right: 8px;">
    <span>@lang('OpenSTAManager')</span>
</div>

<mwc-icon-button id="navbar-notifications" slot="actionItems" label="@lang('Notifiche')">
    <i class="mdi mdi-bell-outline"></i>
</mwc-icon-button>
<mwc-icon-button id="navbar-print" slot="actionItems" label="@lang('Stampa')">
    <i class="mdi mdi-printer"></i>
</mwc-icon-button>
<mwc-icon-button id="period-switcher" slot="actionItems" label="@lang('Cambia periodo')">
    <i class="mdi mdi-calendar-range-outline"></i>
</mwc-icon-button>
<mwc-icon-button id="user-info-btn" slot="actionItems" label="@lang('Il tuo profilo')">
    @if (Auth::hasUser())
        <img src="{{auth()->user()->picture}}" alt="{{auth()->user()->username}}" style="border-radius: 50%;">
    @else
        <i class="mdi mdi-account-outline"></i>
    @endif
</mwc-icon-button>

<mwc-menu activatable corner="BOTTOM_RIGHT" id="notifications-list" trigger="change-language-btn">
    <p>@lang("{0} Non sono presenti notifiche|{1} C'è una notifica|[2,*] Ci sono :num notifiche", ['num' => 0])</p>
    @php
        $notifications = [];
    @endphp
    @foreach($notifications as $notification)
        <mwc-list-item id="notification_{{$notification->id}}" graphic="icon" value="{{$notification->id}}">
            <i class="mdi mdi-{{$notification->icon}}" slot="graphic"></i>
            <span>{{$notification->name}}</span>
        </mwc-list-item>
    @endforeach
</mwc-menu>
<mwc-menu corner="BOTTOM_LEFT" id="user-info" trigger="user-info-btn">
    @if (Auth::hasUser())
        <img class="mdc-elevation--z2" src="{{auth()->user()->picture}}" alt="{{auth()->user()->username}}" style="border-radius: 50%;">
    @else
        <i class="mdi mdi-account-outline mdc-elevation--z2"></i>
    @endif
    <br>
    <b style="margin-top: 16px;">{{auth()->user()?->getFullName()}}</b>
    <br>
    <span>{{auth()->user()?->email}}</span>
    <br>
    <a href="">
        <mwc-button outlined label="@lang('Il tuo profilo')" class="mwc-button--rounded" style="margin-top: 16px;">
            <i class="mdi mdi-account-circle-outline" slot="icon"></i>
        </mwc-button>
    </a>
    <br>
    <form action="" method="post">
        @csrf
        <mwc-button outlined type="submit" label="@lang('Esci')" style="margin-top: 16px;">
            <i class="mdi mdi-logout-variant" slot="icon"></i>
        </mwc-button>
    </form>
    <hr>
</mwc-menu>

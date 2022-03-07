<top-app-bar>
    <!-- Menu icon and logo -->
    <mwc-icon-button slot="navigationIcon">
        <i class="mdi mdi-menu"></i>
    </mwc-icon-button>
    <div slot="title" style="display: flex; align-items: center;">
        <img src="{{asset('images/logo.png')}}" alt="@lang('OpenSTAManager')" style="height: 50px; margin-right: 8px;">
        <span>@lang('OpenSTAManager')</span>
    </div>

    <x-top-app-bar.actions></x-top-app-bar.actions>

    <!-- Drawer -->
    <x-drawer></x-drawer>

    <!-- Footer -->
    <x-footer></x-footer>
</top-app-bar>

<x-top-app-bar.menus></x-top-app-bar.menus>

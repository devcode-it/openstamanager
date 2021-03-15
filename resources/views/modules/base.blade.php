@extends('layouts.app')

@section('title', $module->title)

@section('content')
    @yield('top_content')

    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs pull-right" id="tabs" role="tablist">
            <li class="pull-left nav-item header">
                <a data-toggle="tab" href="#tab_0">
                    @include('modules.title')
                </a>
            </li>

            @php($hide_sidebar = auth()->check() && setting('Nascondere la barra dei plugin di default'))

            <li class="control-sidebar-toggle">
                <a data-toggle="control-sidebar" style="cursor: pointer">{{ tr('Plugin') }}</a>
            </li>

            <script>
                $(document).ready(function() {
                    @if($hide_sidebar)
                        $(".control-sidebar").removeClass("control-sidebar-shown");
                        $("aside.content-wrapper, .main-footer").toggleClass("with-control-sidebar");
                    @endif

                    $(".control-sidebar-toggle").bind("click", function() {
                        $("aside.content-wrapper, .main-footer").toggleClass("with-control-sidebar");
                        $(".control-sidebar").toggleClass("control-sidebar-shown");
                    });
                });
            </script>
        </ul>
    </div>

    <div class="tab-content">
        <div id="tab_0" class="tab-pane active">
            @yield('module_content')
        </div>
    </div>

    @yield('bottom_content')

    <script>
        $('#main_tab').click(function (e) {
            $('#tabs a[href="#tab_0"]').tab('show').trigger('show.bs.tab');
            removeHash();
        });
    </script>
@endsection

@section('top_content')
    <!-- Widget in alto -->
    {( "name": "widgets", "id_module": "{{ $module->id }}", "id_record": "{{ 1 }}", "position": "top", "place": "controller" )}
@endsection

@section('bottom_content')
    <!-- Widget in alto -->
    {( "name": "widgets", "id_module": "{{ $module->id }}", "id_record": "{{ 1 }}", "position": "right", "place": "controller" )}
@endsection

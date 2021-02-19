@extends('layouts.base')

@section('body_class', 'hold-transition login-page')

@section('error_return')
    {!! tr('Nel frattempo, puoi tornare alla _PAGE_', ['_PAGE_' => '<a href="'.route('login').'">'.tr('pagina principale').'</a>']) !!}
@stop

@section('body')
    <section style="position: absolute; left: 35%; top: 40%;">
        <div class="error-page">
            <h2 class="headline text-@yield('error_color')">@yield('error_header')</h2>

            <div class="error-content">
                <h3><i class="fa fa-exclamation-triangle text-@yield('error_color')"></i> @yield('error_message').</h3>

                <p>@yield('error_info'). @yield('error_return').</p>
            </div>
        </div>
    </section>
@endsection

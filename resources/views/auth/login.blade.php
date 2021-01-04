@extends('layouts.base')

@section('body_class', 'hold-transition login-page')
@section('title', __("Login"))

@section('body')
    <div class="login-box">
        <div class="login-logo">
            <a href="//openstamanager.com" target="_blank">
                <img src="{{ base_url() }}/assets/img/full_logo.png" style="max-width: 360px">
            </a>
        </div>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="box">
                <div class="box-body login-box-body">
                    <h4 class="login-box-msg">{{ __('Accedi') }}</h4>

                    {[ "type": "text", "name": "username", "autocomplete": "username", "placeholder": "{{ __('Username') }}", "value": "{{ old('username') }}", "icon-before": "<i class=\"fa fa-user\"></i>", "required": 1 ]}
                    <div class="mb-3" style="margin-bottom: 1rem !important;"></div>

                    {[ "type": "password", "name": "password", "autocomplete": "current-password", "placeholder": "{{ __('Password') }}", "icon-before": "<i class=\"fa fa-lock\"></i>" ]}
                    <div class="mb-3" style="margin-bottom: 1rem !important;"></div>

                    @if (Route::has('password.request'))
                        <div class="text-right">
                            <a href="{{ route('password.request') }}">{{ __('Dimenticata la password?') }}</a>
                        </div>
                    @endif
                </div>

                <div class="box-footer">
                    <button type="submit" class="btn btn-warning btn-block">{{ __('Accedi') }}</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('before_content')
    @if (Update::isBeta())
        <div class="clearfix">&nbsp;</div>
        <div class="alert alert-warning alert-dismissable col-md-6 col-md-push-3 text-center fade in">
            <i class="fa fa-warning"></i> <b>{{ __('Attenzione!') }}</b> {{ __('Stai utilizzando una versione <b>non stabile</b> di OSM.') }}

            <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
        </div>
    @endif

    @if (false)
        <div class="box box-outline box-danger box-center" id="brute">
            <div class="box-header text-center">
                <h3 class="box-title">{{ __('Attenzione') }}</h3>
            </div>

            <div class="box-body text-center">
                <p>{{ __('Sono stati effettuati troppi tentativi di accesso consecutivi!') }}</p>
                <p>{{ __('Tempo rimanente (in secondi)') }}: <span id="brute-timeout">{{ brute.timeout + 1 }}</span></p>
            </div>
        </div>
        <script>
            $(document).ready(function(){
                $(".login-box").fadeOut();
                brute();
            });

            function brute() {
                var value = parseFloat($("#brute-timeout").html()) - 1;
                $("#brute-timeout").html(value);

                if(value > 0){
                    setTimeout("brute()", 1000);
                } else{
                    $("#brute").fadeOut();
                    $(".login-box").fadeIn();
                }
            }
        </script>
    @endif

    @if (flash()->getMessage('error') !== null)
        <script>
            $(document).ready(function(){
                $(".login-box").effect("shake");
            });
        </script>
    @endif
@endsection

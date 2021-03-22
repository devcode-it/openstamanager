@extends('layouts.base')

@section('body_class', 'bg-light')
@section('title', tr("Login"))

@section('body')
    <div class="login-box">
        <div class="login-logo">
            <a href="//openstamanager.com" target="_blank">
                <img src="{{ url('/') }}/assets/img/full_logo.png" style="max-width: 360px">
            </a>
        </div>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="box">
                <div class="box-body login-box-body">
                    <h4 class="login-box-msg">{{ tr('Accedi') }}</h4>

                    <x-inputs.text :placeholder="tr('Username')" name="username" :value="old('username')" autocomplete="username" required>
                        <x-slot name="before">
                            <span class="input-group-addon before">
                                <i class="fa fa-user"></i>
                            </span>
                        </x-slot>
                    </x-inputs.text>

                    <div class="mb-3" style="margin-bottom: 1rem !important;"></div>

                    <x-inputs.password :placeholder="tr('Password')" name="password" autocomplete="current-password" required>
                        <x-slot name="before">
                            <span class="input-group-addon before">
                                <i class="fa fa-lock"></i>
                            </span>
                        </x-slot>
                    </x-inputs.password>

                    <div class="mb-3" style="margin-bottom: 1rem !important;"></div>

                    @if (Route::has('password.request'))
                        <div class="text-right">
                            <a href="{{ route('password.request') }}">{{ tr('Dimenticata la password?') }}</a>
                        </div>
                    @endif
                </div>

                <div class="box-footer">
                    <button type="submit" class="btn btn-warning btn-block">{{ tr('Accedi') }}</button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('before_content')
    @if (Update::isBeta())
        <div class="clearfix">&nbsp;</div>
        <div class="alert alert-warning alert-dismissable col-md-6 col-md-push-3 text-center fade in">
            <i class="fa fa-warning"></i> <b>{{ tr('Attenzione!') }}</b> {!! tr('Stai utilizzando una versione <b>non stabile</b> di OSM.') !!}

            <button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
        </div>
    @endif

    @if (false)
        <div class="box box-outline box-danger box-center" id="brute">
            <div class="box-header text-center">
                <h3 class="box-title">{{ tr('Attenzione') }}</h3>
            </div>

            <div class="box-body text-center">
                <p>{{ tr('Sono stati effettuati troppi tentativi di accesso consecutivi!') }}</p>
                <p>{{ tr('Tempo rimanente (in secondi)') }}: <span id="brute-timeout">{{ brute.timeout + 1 }}</span></p>
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

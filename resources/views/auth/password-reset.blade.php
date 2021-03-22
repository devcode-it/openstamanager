@extends('layouts.base')

@section('body_class', 'bg-light')
@section('title', tr("Modifica password"))

@section('body')
    <div class="login-box box-center-large">
        <div class="login-logo">
            <a href="//openstamanager.com" target="_blank">
                <img src="{{ url('/') }}/assets/img/full_logo.png" style="max-width: 360px">
            </a>
        </div>

        <form method="POST" action="{{ route('password.save') }}">
            @csrf

            <div class="box box-center-large box-warning">
                <div class="box-header with-border text-center">
                    <h3 class="box-title">{{ tr("Modifica password") }}</h3>
                </div>

                <div class="box-body">
                    <p>{{ tr("Inserisci la nuova password per il tuo account") }}.<p>

                    {[ "type": "password", "label": " {{ tr('Password') }}", "name": "password", "required": 1, "strength": "#submit-button", "icon-before": "<i class=\"fa fa-lock\"></i>" ]}';

                    <div class="box-footer">
                        <button type="submit" id="submit-button" class="btn btn-success btn-block">
                            <i class="fa fa-arrow-right"></i>  {{ tr('Conferma') }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

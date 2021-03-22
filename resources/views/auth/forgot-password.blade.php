@extends('layouts.base')

@section('body_class', 'bg-light')
@section('title', tr("Reimpostazione password"))

@section('body')
    <div class="login-box box-center-large">
        <div class="login-logo">
            <a href="//openstamanager.com" target="_blank">
                <img src="{{ url('/') }}/assets/img/full_logo.png" style="max-width: 360px">
            </a>
        </div>

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="box box-center-large box-warning">
                <div class="box-header with-border text-center">
                    <a href="{{ route('login') }}">
                        <i  class="fa fa-arrow-left btn btn-xs btn-warning pull-left tip" title="{{ tr("Torna indietro") }}"></i>
                    </a>
                    <h3 class="box-title">{{ tr("Reimpostazione password") }}</h3>
                </div>

                <div class="box-body">
                    <p>{{ tr("Per reimpostare password, inserisci l'username con cui hai accesso al gestionale e l'indirizzo email associato all'utente") }}.<p>
                    <p>{{ tr("Se i dati inseriti risulteranno corretti riceverai un'email dove sarà indicato il link da cui potrai reimpostare la tua password") }}.</p>

                    {[ "type": "text", "label": "{{ tr('Username') }}", "placeholder": "{{ tr('Username') }}", "name": "username", "icon-before": "<i class=\"fa fa-user\"></i>", "required": 1 ]}

                    {[ "type": "email", "label": "{{ tr('Email') }}", "placeholder": "{{ tr('Email') }}", "name": "email", "icon-before": "<i class=\"fa fa-envelope\"></i>", "required": 1 ]}

                    <div class="box-footer">
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fa fa-arrow-right"></i>  {{ tr('Invia richiesta') }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

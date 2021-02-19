@extends('layouts.base')

@section('body_class', 'bg-light')
@section('title', tr("Inizializzazione"))

@section('body')
<div class="container pb-5">
    <div class="py-5 text-center">
        <img class="d-block mx-auto mb-4" src="{{ base_url() }}/assets/img/full_logo.png" alt="{{ tr('Logo OpenSTAManager') }}">
        <h2>{!! tr('Inizializzazione di _NAME_!', ['_NAME_' => '<strong>'.tr('OpenSTAManager').'</strong>']) !!}</h2>
        <p class="lead">{{ tr("Completa la configurazione del gestionale inserendo le ultime informazioni di base") }}. </p>
    </div>

    <!-- Visualizzazione dell'interfaccia di impostazione iniziale, nel caso il file di configurazione sia mancante oppure i paramentri non siano sufficienti -->
    <form action="" method="post" id="init-form" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="action" value="init">

        @if(!$has_user)
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ tr('Amministrazione') }}</h3>
            </div>

            <div class="box-body">
                <div class="row">
                    <div class="col-md-6">
                        {[ "type": "text", "label": "{{ tr('Username') }}", "name": "admin_username", "placeholder": "{{ tr("Digita l'username dell'amministratore") }}", "required": 1 ]}
                    </div>

                    <div class="col-md-6">
                        {[ "type": "password", "label": "{{ tr('Password') }}", "id": "password", "name": "admin_password", "placeholder": "{{ tr("Digita la password dell'amministratore") }}", "required": 1, "icon-after": "<i onclick=\"togglePassword(this)\" title=\"{{ tr('Visualizza password') }}\" class=\"fa fa-eye clickable\"></i>" ]}
                    </div>

                    <div class="col-md-6">
                        {[ "type": "email", "label": "{{ tr('Email') }}", "name": "admin_email", "placeholder": "{{ tr("Digita l'indirizzo email dell'amministratore") }}" ]}
                    </div>
                </div>
            </div>
        </div>

        <script>
            function togglePassword(btn) {
                let button = $(btn);

                if (button.hasClass("fa-eye")) {
                    $("#password").attr("type", "text");
                    button.removeClass("fa-eye").addClass("fa-eye-slash");
                    button.attr("title", "Nascondi password");
                } else {
                    $("#password").attr("type", "password");
                    button.removeClass("fa-eye-slash").addClass("fa-eye");
                    button.attr("title", "Visualizza password");
                }
            }

        </script>
        @endif

        @if(!$has_azienda)
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ tr('Azienda predefinita') }}</h3>
            </div>

            <div class="box-body">
                {!! azienda_form !!}

                <div class="box box-outline box-success collapsed-box">
                    <div class="box-header with-border">
                        <h3 class="box-title">{{ tr('Logo stampe') }}</h3>
                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-box-tool" data-widget="collapse">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="box-body">

                        <div class="col-md-12">
                            {[ "type": "file", "placeholder": "{{ tr('File') }}", "name": "blob" ]}
                        </div>

                        <p>&nbsp;</p><div class="col-md-12 alert alert-info text-center">{{ tr('Per impostare il logo delle stampe, caricare un file ".jpg". Risoluzione consigliata 302x111 pixel') }}.</div>

                    </div>
                </div>

            </div>
        </div>
        @endif

        @if(!$has_settings)
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ tr('Impostazioni di base') }}</h3>
            </div>

            <div class="box-body row">

            @foreach($settings as $setting)
                <div class="col-md-6">
                    {!! $setting !!}
                </div>
            @endforeach

            </div>
        </div>
        @endif

        <!-- PULSANTI -->
        <div class="row">
            <div class="col-md-4">
                <span>*<small><small>{{ tr('Campi obbligatori') }}</small></small></span>
            </div>
            <div class="col-md-4 text-right">
                <button type="submit" id="config" class="btn btn-success btn-block">
                    <i class="fa fa-cog"></i> {{ tr('Configura') }}
                </button>
            </div>
        </div>

    </form>

    <script>
        $(document).ready(function(){
            init();

            $("button[type=submit]").not("#config").remove();
        });
    </script>
</div>
@endsection

@extends('layouts.app')

@section('title', tr("Utenza corrente"))

@section('content')
<div class="box box-widget widget-user">
    <div class="widget-user-header bg-primary">
        <h3 class="widget-user-username">{{ $user->username }}</h3>
        <h5 class="widget-user-desc">{{ $user->gruppo }}</h5>
    </div>

    <div class="widget-user-image">
        @if($user->photo)
            <img src="{{ $user->photo }}" class="img-circle" alt="{{ $user->username }}" />
        @else
            <i class="fa fa-user-circle-o fa-4x pull-left"></i>
        @endif
    </div>

    <div class="box-footer">
        <div class="row">
            <div class="col-sm-4 border-right">
                <div class="description-block">
                    <h5 class="description-header">{{ tr('Anagrafica associata') }}</h5>
                    <span class="description-text">{{ $user->anagrafica['ragione_sociale'] ? $user->anagrafica['ragione_sociale'] : tr('Nessuna') }}</span>
                </div>
            </div>

            <div class="col-sm-4 border-right">
                <div class="description-block">
                    <a class="btn btn-info btn-block tip" data-href="" data-toggle="modal" data-title="{{ tr('Cambia foto utente') }}">
                        <i class="fa fa-picture-o"></i> {{ tr('Cambia foto utente') }}
                    </a>
                </div>
            </div>

            <div class="col-sm-4 border-right">
                <div class="description-block">
                    <a class="btn btn-warning btn-block tip" data-href="{{ route('user-password') }}" data-toggle="modal" data-title="{{ tr('Cambia password') }}">
                        <i class="fa fa-unlock-alt"></i> {{ tr('Cambia password') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">

        <div class="box box-outline box-success">
            <div class="box-header">
                <h3 class="box-title">{{ tr('API') }}</h3>
            </div>

            <div class="box-body">
                <p>{{ tr("Puoi utilizzare il token per accedere all'API del gestionale e per visualizzare il calendario su applicazioni esterne") }}.</p>

                <p>{{ tr('Token personale') }}: <b>{{ $token }}</b></p>
                <p>{{ tr("URL dell'API") }}: <a href="{{ $api }}" target="_blank">{{ $api }}</a></p>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="box box-outline box-info">
            <div class="box-header">
                <h3 class="box-title">{{ tr('Calendario interventi') }}</h3>
            </div>

            <div class="box-body">
                <p>{{ tr("Per accedere al calendario eventi attraverso l'API, accedi al seguente link") }}:</p>
                <a href="{{ $sync_link }}" target="_blank">{{ $sync_link }}</a>

                <hr>
                <h5>{{ tr('Configurazione') }}</h5>
                <p>{!! tr("Per _ANDROID_, scarica un'applicazione dedicata dal _LINK_", [
                    '_ANDROID_' => '<b>'.tr('Android').'</b>',
                    '_LINK_' => '<a href="https://play.google.com/store/search?q=iCalSync&c=apps" target="_blank">'.tr('Play Store').'</a>',
                ]) !!}.</p>

                <p>{!! tr("Per _APPLE_, puoi configurare un nuovo calendario dall'app standard del calendario", [
                    '_APPLE_' => '<b>'.tr('Apple').'</b>',
                ]) !!}.</p>

                <p>{!! tr('Per _PC_ e altri client di posta, considerare le relative funzionalità o eventuali plugin', [
                    '_PC_' => '<b>'.tr('PC').'</b>',
                ]) !!}.</p>
            </div>
        </div>

    </div>
</div>
<script>$(document).ready(init)</script>
@endsection

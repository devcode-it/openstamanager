@extends('layouts.guest')

@section('title', tr("Requisiti"))
@section('box_class', 'box-info')
@section('box_header')
<h3 class="box-title">{{ tr('Requisiti del software OpenSTAManager') }}</h3>
@endsection

@section('content')
<p>{!! tr('Benvenuto in _NAME_!', ['_NAME_' => '<strong>'.tr('OpenSTAManager').'</strong>']) !!}</p>
<p>{{ tr("Prima di procedere alla configurazione e all'installazione del software, sono necessari alcuni accorgimenti per garantire il corretto funzionamento del gestionale") }}.</p>
<br>

<p>{!! tr('Le estensioni e impostazioni PHP possono essere personalizzate nel file di configurazione _FILE_', ['_FILE_' => '<b>php.ini</b>']) !!}.</p>
<hr>

    @include('config.requirements-list')

<button type="button" class="btn btn-info center-block" onclick="window.location.reload()">
    <i class="fa fa-refresh"></i> {{ tr("Ricarica") }}
</button>
@endsection

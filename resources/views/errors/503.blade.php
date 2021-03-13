@extends('errors.base')

@section('title', tr("Servizio non disponibile"))

@section('error_color', 'info')
@section('error_header', '503')

@section('error_message', tr('Servizio non disponibile!'))
@section('error_info', tr("E' in corso un processo di manutenzione dell'applicazione"))
@section('error_return', tr("Torneremo attivi il più presto possibile"))

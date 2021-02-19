@extends('errors.base')

@section('title', tr("Errore del server!"))

@section('error_color', 'danger')
@section('error_header', '500')

@section('error_message', tr('Oops! Qualcosa è andato storto'))
@section('error_info', tr('Cercheremo di risolvere il problema il presto possibile'))
